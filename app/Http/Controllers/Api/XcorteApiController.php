<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreXcorteApiBatchRequest;
use App\Http\Requests\StoreXcorteApiRequest;
use App\Models\Computer;
use App\Models\XcorteApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XcorteApiController extends Controller
{
    public function store(StoreXcorteApiRequest $request)
    {
        $data = $request->validated();
        $origen = $request->attributes->get('corte_origen');

        $attributes = [
            'fecha_corte' => $data['fecha_corte'],
            'clave_tienda' => $data['clave_tienda'],
        ];

        $values = $this->buildValues($data, $origen);

        $exists = XcorteApi::query()->where($attributes)->exists();
        $corte = XcorteApi::updateOrCreate($attributes, $values);

        Log::channel('single')->info('XCORTE API REGISTRO', [
            'origen' => $origen,
            'accion' => $exists ? 'actualizado' : 'creado',
            'corte_id' => $corte->id,
            'clave_tienda' => $corte->clave_tienda,
            'fecha_corte' => $corte->fecha_corte,
        ]);

        return response()->json([
            'message' => $exists ? 'Corte actualizado correctamente' : 'Corte registrado correctamente',
            'accion' => $exists ? 'actualizado' : 'creado',
            'corte' => $corte,
        ], $exists ? 200 : 201);
    }

    public function storeBatch(StoreXcorteApiBatchRequest $request)
    {
        $items = $request->validated()['cortes'];
        $origen = $request->attributes->get('corte_origen');

        $created = [];
        $updated = [];
        $errors = [];

        foreach ($items as $index => $item) {
            try {
                $attributes = [
                    'fecha_corte' => $item['fecha_corte'],
                    'clave_tienda' => $item['clave_tienda'],
                ];

                $values = $this->buildValues($item, $origen);

                $exists = XcorteApi::query()->where($attributes)->exists();
                $corte = XcorteApi::updateOrCreate($attributes, $values);

                if ($exists) {
                    $updated[] = $corte;
                } else {
                    $created[] = $corte;
                }
            } catch (\Throwable $e) {
                Log::channel('single')->error('Error registrando corte: '.$e->getMessage(), [
                    'index' => $index,
                    'item' => $item,
                ]);
                $errors[] = ['index' => $index, 'error' => 'DB Error: '.$e->getMessage()];
            }
        }

        Log::channel('single')->info('XCORTE API LOTE', [
            'origen' => $origen,
            'created_count' => count($created),
            'updated_count' => count($updated),
            'error_count' => count($errors),
        ]);

        return response()->json([
            'message' => 'Operación por lote completada',
            'created_count' => count($created),
            'updated_count' => count($updated),
            'error_count' => count($errors),
            'cortes' => array_merge($created, $updated),
            'errors' => $errors,
        ], count($errors) > 0 ? 207 : 201);
    }

    public function index(Request $request)
    {
        $query = XcorteApi::query();

        if ($request->filled('fecha_corte')) {
            $query->whereDate('fecha_corte', $request->input('fecha_corte'));
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_corte', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_corte', '<=', $request->input('fecha_fin'));
        }

        if ($request->filled('clave_tienda')) {
            $query->where('clave_tienda', $request->input('clave_tienda'));
        }

        if ($request->filled('plaza')) {
            $query->where('plaza', $request->input('plaza'));
        }

        $perPage = (int) $request->input('per_page', 50);
        $perPage = min(max($perPage, 1), 500);

        $cortes = $query
            ->orderByDesc('fecha_corte')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($cortes);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildValues(array $data, ?string $origen): array
    {
        $values = [
            'monto_contado' => $data['monto_contado'],
            'monto_credito' => $data['monto_credito'],
            'fecha_registro' => now(),
        ];

        if ($origen === 'agente') {
            $computer = Computer::query()
                ->where('short_key', strtoupper((string) $data['clave_tienda']))
                ->first();

            $values['plaza'] = $computer?->plaza;
            $values['computer_id'] = $computer?->id;
        }

        return $values;
    }
}
