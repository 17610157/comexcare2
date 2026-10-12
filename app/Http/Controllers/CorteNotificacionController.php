<?php

namespace App\Http\Controllers;

use App\Models\CortePlazaEmailConfig;
use App\Services\CortesFaltantesService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CorteNotificacionController extends Controller
{
    public function __construct(private readonly CortesFaltantesService $service) {}

    public function index()
    {
        $plazas = $this->service->plazasDisponibles();

        return view('admin.cortes-notificaciones.index', compact('plazas'));
    }

    public function data(Request $request)
    {
        $query = CortePlazaEmailConfig::query();

        $searchInput = $request->input('search');
        $search = is_array($searchInput) ? ($searchInput['value'] ?? '') : (string) $searchInput;

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('plaza', 'like', '%'.$search.'%')
                    ->orWhere('hora_envio', 'like', '%'.$search.'%');
            });
        }

        $recordsTotal = CortePlazaEmailConfig::count();
        $recordsFiltered = $query->count();

        $columns = ['id', 'plaza', 'emails', 'envio_activo', 'hora_envio', 'ultimo_envio_at'];
        $orderColumn = $columns[(int) $request->input('order.0.column', 1)] ?? 'plaza';
        $orderDirection = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $length = $length < 0 ? 100000 : $length;

        $records = $query->orderBy($orderColumn, $orderDirection)
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $records->map(fn (CortePlazaEmailConfig $config) => [
                'id' => $config->id,
                'plaza' => $config->plaza,
                'emails' => implode(', ', $config->emails ?? []),
                'envio_activo' => $config->envio_activo,
                'hora_envio' => sprintf('%02d:00', $config->hora_envio),
                'hora_envio_valor' => $config->hora_envio,
                'ultimo_envio_at' => $config->ultimo_envio_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $config = CortePlazaEmailConfig::create($validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "Configuración creada para la plaza {$config->plaza}.", 'data' => $config]);
        }

        return redirect()->back()->with('success', "Configuración creada para la plaza {$config->plaza}.");
    }

    public function update(Request $request, CortePlazaEmailConfig $corteNotificacion)
    {
        $validated = $this->validatePayload($request, $corteNotificacion->id);

        $corteNotificacion->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Configuración actualizada para la plaza {$corteNotificacion->plaza}.",
            'data' => $corteNotificacion->fresh(),
        ]);
    }

    public function destroy(CortePlazaEmailConfig $corteNotificacion)
    {
        $plaza = $corteNotificacion->plaza;
        $corteNotificacion->delete();

        return response()->json([
            'success' => true,
            'message' => "Configuración de la plaza {$plaza} eliminada.",
        ]);
    }

    /**
     * Lista de plazas con destinatarios, horario y conteo de faltantes del día.
     */
    public function plazas(Request $request)
    {
        $fecha = $this->fecha($request);
        $clasificacion = $this->service->porPlaza($fecha);
        $configs = CortePlazaEmailConfig::query()->get()->keyBy('plaza');

        $data = $this->service->plazasDisponibles()->map(function (string $plaza) use ($clasificacion, $configs) {
            $buckets = $clasificacion->get($plaza, ['sin_corte' => [], 'regla' => []]);
            $config = $configs->get($plaza);

            return [
                'plaza' => $plaza,
                'emails' => $this->service->destinatarios($plaza),
                'envio_activo' => (bool) ($config?->envio_activo ?? false),
                'hora_envio' => $config?->hora_envio,
                'sin_corte' => count($buckets['sin_corte']),
                'regla' => count($buckets['regla']),
            ];
        })->values();

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'data' => $data,
        ]);
    }

    /**
     * Previsualiza la clasificación detallada de una plaza (sin enviar).
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'plaza' => ['required', 'string', 'max:50'],
            'fecha' => ['nullable', 'date'],
        ]);

        $fecha = $this->fecha($request);
        $data = $this->service->porPlaza($fecha, [$validated['plaza']])->get($validated['plaza'])
            ?? ['plaza' => $validated['plaza'], 'sin_corte' => [], 'regla' => []];

        return response()->json([
            'fecha' => $fecha->toDateString(),
            'destinatarios' => $this->service->destinatarios($validated['plaza']),
            'data' => $data,
        ]);
    }

    /**
     * Envío manual de una plaza (sin límite de veces).
     */
    public function enviar(Request $request)
    {
        $validated = $request->validate([
            'plaza' => ['required', 'string', 'max:50'],
            'fecha' => ['nullable', 'date'],
        ]);

        $result = $this->service->enviar($this->fecha($request), $validated['plaza']);

        return response()->json([
            'success' => $result['enviado'],
            'message' => $result['enviado']
                ? "Correo enviado a la plaza {$result['plaza']}.".($result['sin_corte'] + $result['regla'] === 0 ? ' (sin faltantes)' : '')
                : ($result['motivo'] ?? 'No se envió el correo.'),
            'result' => $result,
        ]);
    }

    /**
     * Envío manual de todas las plazas (sin límite de veces).
     */
    public function enviarTodas(Request $request)
    {
        $request->validate(['fecha' => ['nullable', 'date']]);

        $fecha = $this->fecha($request);
        $resultados = $this->service->plazasDisponibles()
            ->map(fn (string $plaza) => $this->service->enviar($fecha, $plaza))
            ->values();

        $enviados = $resultados->where('enviado', true)->count();

        return response()->json([
            'success' => true,
            'message' => "Se enviaron {$enviados} de {$resultados->count()} plazas.",
            'enviados' => $enviados,
            'total' => $resultados->count(),
            'resultados' => $resultados,
        ]);
    }

    /**
     * Activa/desactiva y programa la hora del envío automático de una plaza.
     */
    public function guardarAutomatico(Request $request)
    {
        $validated = $request->validate([
            'plaza' => ['required', 'string', 'max:50'],
            'envio_activo' => ['required', 'boolean'],
            'hora_envio' => ['required', 'integer', 'min:0', 'max:23'],
        ]);

        $config = CortePlazaEmailConfig::updateOrCreate(
            ['plaza' => trim($validated['plaza'])],
            [
                'envio_activo' => $validated['envio_activo'] ? 1 : 0,
                'hora_envio' => $validated['hora_envio'],
            ]
        );

        $hora = sprintf('%02d:00', $config->hora_envio);

        return response()->json([
            'success' => true,
            'message' => $config->envio_activo
                ? "Envío automático activado para {$config->plaza} a las {$hora}."
                : "Envío automático desactivado para {$config->plaza}.",
            'data' => [
                'plaza' => $config->plaza,
                'envio_activo' => (bool) $config->envio_activo,
                'hora_envio' => $config->hora_envio,
            ],
        ]);
    }

    private function fecha(Request $request): Carbon
    {
        return $request->filled('fecha')
            ? Carbon::parse((string) $request->input('fecha'))
            : now()->subDay();
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'plaza' => [
                'required',
                'string',
                'max:50',
                Rule::unique('corte_plaza_email_configs', 'plaza')->ignore($ignoreId),
            ],
            'emails' => ['nullable'],
            'envio_activo' => ['nullable', 'boolean'],
            'hora_envio' => ['required', 'integer', 'min:0', 'max:23'],
        ], [
            'plaza.required' => 'La plaza es obligatoria.',
            'plaza.unique' => 'Ya existe una configuración para esa plaza.',
            'hora_envio.required' => 'La hora de envío es obligatoria.',
            'hora_envio.min' => 'La hora debe estar entre 0 y 23.',
            'hora_envio.max' => 'La hora debe estar entre 0 y 23.',
        ]);

        $emails = $this->parseEmails($request->input('emails'));

        $invalidos = collect($emails)->reject(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        if ($invalidos->isNotEmpty()) {
            throw ValidationException::withMessages([
                'emails' => 'Correos inválidos: '.$invalidos->implode(', '),
            ]);
        }

        $validated['emails'] = $emails;
        $validated['envio_activo'] = $request->boolean('envio_activo');
        $validated['plaza'] = trim($validated['plaza']);

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function parseEmails(mixed $raw): array
    {
        $items = is_array($raw)
            ? $raw
            : preg_split('/[,;\s]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);

        return collect($items)
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
