<?php

namespace App\Http\Controllers;

use App\Jobs\SyncRbfFileHashesJob;
use App\Models\RbfFileHash;
use App\Models\RbfPlazaTimeConfig;
use Illuminate\Http\Request;

class RbfPlazaTimeConfigController extends Controller
{
    public function index()
    {
        $plazas = RbfPlazaTimeConfig::query()
            ->select('plaza')
            ->pluck('plaza')
            ->merge(
                RbfFileHash::query()
                    ->select('plaza')
                    ->distinct()
                    ->whereNotNull('plaza')
                    ->where('plaza', '!=', '')
                    ->orderBy('plaza')
                    ->pluck('plaza')
            )
            ->map(fn ($plaza) => strtolower(trim($plaza)))
            ->unique()
            ->sort()
            ->values();

        return view('admin.rbf-plaza-time-configs.index', compact('plazas'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $config = RbfPlazaTimeConfig::create($validated);

        $mensaje = "Configuración creada para la plaza {$validated['plaza']}.".$this->forzarSyncSuffix($request);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $mensaje, 'data' => $config]);
        }

        return redirect()->back()->with('success', $mensaje);
    }

    public function update(Request $request, RbfPlazaTimeConfig $rbfPlazaTimeConfig)
    {
        $validated = $this->validatePayload($request, $rbfPlazaTimeConfig->id);

        $rbfPlazaTimeConfig->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Configuración actualizada para la plaza {$rbfPlazaTimeConfig->plaza}.".$this->forzarSyncSuffix($request),
            'data' => $rbfPlazaTimeConfig->fresh(),
        ]);
    }

    public function sincronizar()
    {
        SyncRbfFileHashesJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => 'Sincronización de hashes encolada. Los last_modified se actualizarán en breve.',
        ]);
    }

    private function forzarSyncSuffix(Request $request): string
    {
        if (! $request->boolean('forzar_sync')) {
            return '';
        }

        SyncRbfFileHashesJob::dispatch();

        return ' Sincronización de hashes forzada: los datos se actualizarán en menos de 1 minuto.';
    }

    public function destroy(RbfPlazaTimeConfig $rbfPlazaTimeConfig)
    {
        $plaza = $rbfPlazaTimeConfig->plaza;
        $rbfPlazaTimeConfig->delete();

        return response()->json([
            'success' => true,
            'message' => "Configuración de la plaza {$plaza} eliminada. El próximo sync dejará su last_modified sin ajuste.",
        ]);
    }

    public function data(Request $request)
    {
        $query = RbfPlazaTimeConfig::query();

        $searchInput = $request->input('search');
        $search = is_array($searchInput) ? ($searchInput['value'] ?? '') : (string) $searchInput;

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('plaza', 'ILIKE', '%'.$search.'%')
                    ->orWhere('meridiano', 'ILIKE', '%'.$search.'%')
                    ->orWhere('zona_horaria', 'ILIKE', '%'.$search.'%');
            });
        }

        $recordsTotal = RbfPlazaTimeConfig::count();
        $recordsFiltered = $query->count();

        $columns = ['id', 'plaza', 'meridiano', 'zona_horaria', 'created_at'];
        $orderColumn = $columns[(int) ($request->input('order.0.column', 0))] ?? 'id';
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
            'data' => $records->map(fn (RbfPlazaTimeConfig $config) => [
                'id' => $config->id,
                'plaza' => $config->plaza,
                'meridiano' => $config->meridiano,
                'zona_horaria' => $config->zona_horaria > 0 ? '+'.$config->zona_horaria : (string) $config->zona_horaria,
                'total_horas' => ($config->zona_horaria - $config->meridiano < 0 ? '-' : '+').abs($config->zona_horaria - $config->meridiano).' h',
                'created_at' => $config->created_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'plaza' => [
                'required',
                'string',
                'max:50',
                'unique:rbf_plaza_time_configs,plaza'.($ignoreId !== null ? ','.$ignoreId : ''),
                function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId) {
                    $exists = RbfPlazaTimeConfig::query()
                        ->where('plaza', strtolower(trim((string) $value)))
                        ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe una configuración para esa plaza.');
                    }
                },
            ],
            'meridiano' => ['required', 'integer', 'min:0', 'max:23'],
            'zona_horaria' => ['required', 'integer', 'min:-12', 'max:14'],
        ], [
            'plaza.required' => 'La plaza es obligatoria.',
            'plaza.unique' => 'Ya existe una configuración para esa plaza.',
            'meridiano.required' => 'El meridiano es obligatorio.',
            'meridiano.integer' => 'El meridiano debe ser un número entero.',
            'meridiano.min' => 'El meridiano debe estar entre 0 y 23.',
            'meridiano.max' => 'El meridiano debe estar entre 0 y 23.',
            'zona_horaria.required' => 'La zona horaria es obligatoria.',
            'zona_horaria.integer' => 'La zona horaria debe ser un número entero.',
            'zona_horaria.min' => 'La zona horaria debe estar entre -12 y 14.',
            'zona_horaria.max' => 'La zona horaria debe estar entre -12 y 14.',
        ]);

        $validated['plaza'] = strtolower(trim($validated['plaza']));

        return $validated;
    }
}
