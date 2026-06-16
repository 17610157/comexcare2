<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Vale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ValeController extends Controller
{
    // Endpoint para resetear last_sync_key (enviar comando al agente)
    public function resetSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer|exists:computers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $computerId = $request->computer_id;

        // Crear comando para resetear sync en el agente
        Command::create([
            'computer_id' => $computerId,
            'type' => 'reset_sync',
            'data' => json_encode(['action' => 'reset_last_sync_key']),
            'status' => 'pending',
        ]);

        Log::info('Reset sync command sent', ['computer_id' => $computerId]);

        return response()->json([
            'message' => 'Reset sync command sent to agent',
            'computer_id' => $computerId,
        ]);
    }

    public function store(Request $request)
    {
        Log::channel('single')->info('VALE API REQUEST', [
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'raw_content' => substr($request->getContent(), 0, 2000),
        ]);

        $data = json_decode($request->getContent(), true) ?? $request->all();

        Log::channel('single')->info('VALE API DATA', ['data' => $data]);

        $validator = Validator::make($data, [
            'tipo_movim' => 'nullable|string|max:2',
            'no_consec' => 'nullable|string|max:6',
            'fecha' => 'nullable|date',
            'cve_pro_cl' => 'nullable|string|max:5',
            'desc_mov' => 'nullable|string|max:40',
            'ent_sal' => 'nullable|string|max:1',
            'observinv' => 'nullable|string|max:6',
            'almacen' => 'nullable|string|max:2',
            'afectado' => 'nullable|string|max:1',
            'estado' => 'nullable|string|max:1',
            'fechacort' => 'nullable|date',
            'precio_tot' => 'nullable|numeric',
            'impues_tot' => 'nullable|numeric',
            'cost_tot' => 'nullable|numeric',
            'no_partida' => 'nullable|numeric',
            'mov_origen' => 'nullable|string|max:13',
            'ya_exporta' => 'nullable|string|max:1',
            'clave_usu' => 'nullable|string|max:5',
            'campo1c' => 'nullable|string|max:5',
            'campo2n' => 'nullable|numeric',
            'folio_ref' => 'nullable|string|max:6',
            'lista_prec' => 'nullable|string|max:1',
            'ccampo1' => 'nullable|string|max:16',
            'ncampo2' => 'nullable|numeric',
            'dcampo3' => 'nullable|date',
            'lcampo4' => 'nullable|boolean',
            'tienda' => 'nullable|string|max:5',
            'modhora' => 'nullable|string|max:8',
            'modfecha' => 'nullable|date',
            'moduser' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            Log::channel('single')->warning('VALE API VALIDATION FAILED', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()->toArray(),
            ], 422);
        }

        $vale = Vale::create($data);

        Log::channel('single')->info('VALE API CREATED', ['vale_id' => $vale->id]);

        return response()->json([
            'message' => 'Vale created successfully',
            'vale' => $vale,
        ], 201);
    }

    public function storeBatch(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (! is_array($data)) {
            $data = $request->all();
        }

        if (empty($data)) {
            return response()->json(['error' => 'No data provided'], 422);
        }

        if (isset($data['vales']) && is_array($data['vales'])) {
            $items = $data['vales'];
        } else {
            $items = is_array(array_values($data)[0] ?? null) ? $data : [$data];
        }

        $computerId = $data['computer_id'] ?? null;
        $plazaFromRequest = $data['plaza'] ?? null;
        $shortKeyFromRequest = $data['short_key'] ?? null;

        // Determinar plaza desde la computadora que envía
        $plazaFromComputer = null;
        if ($computerId) {
            $computer = Computer::find($computerId);
            if ($computer && $computer->plaza) {
                $plazaFromComputer = $computer->plaza;
            }
        }

        // Buscar por short_key si no hay computer_id
        if (! $computerId && $shortKeyFromRequest) {
            $computer = Computer::where('short_key', $shortKeyFromRequest)->first();
            if ($computer && $computer->plaza) {
                $plazaFromComputer = $computer->plaza;
            }
        }

        $fieldMapping = [
            'TipoMovim' => 'tipo_movim',
            'NoConsec' => 'no_consec',
            'Fecha' => 'fecha',
            'CveProCl' => 'cve_pro_cl',
            'DescMov' => 'desc_mov',
            'EntSal' => 'ent_sal',
            'Almacen' => 'almacen',
            'Tienda' => 'tienda',
            'ObservInv' => 'observinv',
            'Afectado' => 'afectado',
            'Estado' => 'estado',
            'FechaCort' => 'fechacort',
            'PrecioTot' => 'precio_tot',
            'ImpuesTot' => 'impues_tot',
            'CostTot' => 'cost_tot',
            'NoPartida' => 'no_partida',
            'MovOrigen' => 'mov_origen',
            'YaExporta' => 'ya_exporta',
            'ClaveUsu' => 'clave_usu',
            'Campo1c' => 'campo1c',
            'Campo2n' => 'campo2n',
            'FolioRef' => 'folio_ref',
            'ListaPrec' => 'lista_prec',
            'cCampo1' => 'ccampo1',
            'nCampo2' => 'ncampo2',
            'dCampo3' => 'dcampo3',
            'lCampo4' => 'lcampo4',
            'ModFecha' => 'modfecha',
            'ModHora' => 'modhora',
            'ModUser' => 'moduser',
        ];

        $normalizedItems = [];
        foreach ($items as $item) {
            $normalized = [];
            foreach ($item as $key => $value) {
                $normalizedKey = $fieldMapping[$key] ?? strtolower($key);
                $normalized[$normalizedKey] = $value;
            }
            $normalizedItems[] = $normalized;
        }
        $items = $normalizedItems;

        Log::channel('single')->info('VALE BATCH ITEMS', [
            'count' => count($items),
            'first_item_keys' => isset($items[0]) ? array_keys($items[0]) : [],
            'computer_id' => $computerId,
            'plaza_from_computer' => $plazaFromComputer,
            'plaza_from_request' => $plazaFromRequest,
            'short_key_from_request' => $shortKeyFromRequest,
        ]);

        $created = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $validator = Validator::make($item, [
                'tipo_movim' => 'nullable|string|max:2',
                'no_consec' => 'nullable|string|max:15',
                'fecha' => 'nullable|date',
                'cve_pro_cl' => 'nullable|string|max:10',
                'desc_mov' => 'nullable|string|max:40',
                'ent_sal' => 'nullable|string|max:1',
                'observinv' => 'nullable|string|max:6',
                'almacen' => 'nullable|string|max:10',
                // Campos booleanos: quitar validación estricta, el modelo convierte 0/1 a true/false
                'afectado' => 'nullable',
                'estado' => 'nullable',
                'fechacort' => 'nullable|date',
                'precio_tot' => 'nullable|numeric',
                'impues_tot' => 'nullable|numeric',
                'cost_tot' => 'nullable|numeric',
                'no_partida' => 'nullable|numeric',
                'mov_origen' => 'nullable|string|max:13',
                'ya_exporta' => 'nullable',
                'clave_usu' => 'nullable|string|max:5',
                'campo1c' => 'nullable|string|max:5',
                'campo2n' => 'nullable|numeric',
                'folio_ref' => 'nullable|string|max:6',
                'lista_prec' => 'nullable|string|max:1',
                'ccampo1' => 'nullable|string|max:16',
                'ncampo2' => 'nullable|numeric',
                'dcampo3' => 'nullable|date',
                'lcampo4' => 'nullable',
                'tienda' => 'nullable|string|max:5',
                'modhora' => 'nullable|string|max:8',
                'modfecha' => 'nullable|date',
                'moduser' => 'nullable|string|max:5',
            ]);

            if ($validator->fails()) {
                $errors[] = ['index' => $index, 'errors' => $validator->errors()->toArray()];

                continue;
            }

            $noConsec = $item['no_consec'] ?? '';
            $cveProCl = $item['cve_pro_cl'] ?? '';
            $fecha = $item['fecha'] ?? '';
            $tienda = $item['tienda'] ?? '';

            if ($noConsec && $cveProCl && $fecha && $tienda) {
                $existe = Vale::where('no_consec', $noConsec)
                    ->where('cve_pro_cl', $cveProCl)
                    ->whereDate('fecha', $fecha)
                    ->where('tienda', $tienda)
                    ->exists();

                if ($existe) {
                    $errors[] = ['index' => $index, 'error' => 'Registro duplicado: '.$noConsec.' - '.$cveProCl.' - '.$tienda];

                    continue;
                }
            }

            $item['computer_id'] = $computerId;

            // Convertir valores numéricos a booleanos para PostgreSQL
            $booleanFields = ['lcampo4', 'afectado', 'estado', 'ya_exporta'];
            foreach ($booleanFields as $field) {
                if (isset($item[$field])) {
                    // Convertir: 0/null/'' -> false, 1/'1'/true -> true
                    $originalValue = $item[$field];

                    // Convertir explícitamente a booleano (no string)
                    if ($item[$field] === '1' || $item[$field] === 1 || $item[$field] === true || $item[$field] === 'true') {
                        $item[$field] = true;
                    } else {
                        $item[$field] = false;
                    }

                    if ($originalValue != $item[$field]) {
                        Log::channel('single')->debug("Converted {$field}: {$originalValue} -> ".var_export($item[$field], true));
                    }
                } else {
                    $item[$field] = false; // Valor por defecto
                }
            }

            if ($computerId) {
                $computer = Computer::find($computerId);
                if ($computer && $computer->plaza) {
                    $item['plaza'] = $computer->plaza;
                }
            } elseif ($plazaFromComputer) {
                $item['plaza'] = $plazaFromComputer;
            } elseif ($plazaFromRequest) {
                $item['plaza'] = $plazaFromRequest;
            } else {
                $tienda = $item['tienda'] ?? '';
                if (empty($tienda)) {
                    $tienda = isset($item['desc_mov']) ? substr($item['desc_mov'], 0, 5) : '';
                }
                if ($tienda) {
                    $tiendaInfo = DB::table('bi_sys_tiendas')
                        ->where('clave_tienda', $tienda)
                        ->orWhere('clave_alterna', $tienda)
                        ->first();
                    if ($tiendaInfo && $tiendaInfo->id_plaza) {
                        $item['plaza'] = $tiendaInfo->id_plaza;
                    }
                }
            }

            try {
                $created[] = Vale::create($item);
            } catch (\Exception $e) {
                Log::channel('single')->error('Error creating vale: '.$e->getMessage(), [
                    'item_keys' => array_keys($item),
                    'item_sample' => array_slice($item, 0, 5, true),
                ]);
                $errors[] = ['index' => $index, 'error' => 'DB Error: '.$e->getMessage()];
            }
        }

        Log::channel('single')->info('VALE BATCH COMPLETED', [
            'created_count' => count($created),
            'error_count' => count($errors),
            'computer_id' => $computerId,
            'first_errors' => array_slice($errors, 0, 5),
        ]);

        return response()->json([
            'message' => 'Batch operation completed',
            'created_count' => count($created),
            'error_count' => count($errors),
            'vales' => $created,
            'errors' => $errors,
        ], count($errors) > 0 ? 207 : 201);
    }

    public function index(Request $request)
    {
        $query = Vale::query();

        if ($request->has('tienda')) {
            $query->where('tienda', $request->tienda);
        }

        if ($request->has('almacen')) {
            $query->where('almacen', $request->almacen);
        }

        if ($request->has('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->has('no_consec')) {
            $query->where('no_consec', $request->no_consec);
        }

        $perPage = $request->input('per_page', 50);
        $vales = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($vales);
    }

    public function show($id)
    {
        $vale = Vale::find($id);

        if (! $vale) {
            return response()->json(['error' => 'Vale not found'], 404);
        }

        return response()->json($vale);
    }

    public function update(Request $request, $id)
    {
        $vale = Vale::find($id);

        if (! $vale) {
            return response()->json(['error' => 'Vale not found'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? $request->all();

        $validator = Validator::make($data, [
            'tipo_movim' => 'nullable|string|max:2',
            'no_consec' => 'nullable|string|max:6',
            'fecha' => 'nullable|date',
            'cve_pro_cl' => 'nullable|string|max:5',
            'desc_mov' => 'nullable|string|max:40',
            'ent_sal' => 'nullable|string|max:1',
            'observinv' => 'nullable|string|max:6',
            'almacen' => 'nullable|string|max:2',
            'afectado' => 'nullable|string|max:1',
            'estado' => 'nullable|string|max:1',
            'fechacort' => 'nullable|date',
            'precio_tot' => 'nullable|numeric',
            'impues_tot' => 'nullable|numeric',
            'cost_tot' => 'nullable|numeric',
            'no_partida' => 'nullable|numeric',
            'mov_origen' => 'nullable|string|max:13',
            'ya_exporta' => 'nullable|string|max:1',
            'clave_usu' => 'nullable|string|max:5',
            'campo1c' => 'nullable|string|max:5',
            'campo2n' => 'nullable|numeric',
            'folio_ref' => 'nullable|string|max:6',
            'lista_prec' => 'nullable|string|max:1',
            'ccampo1' => 'nullable|string|max:16',
            'ncampo2' => 'nullable|numeric',
            'dcampo3' => 'nullable|date',
            'lcampo4' => 'nullable|boolean',
            'tienda' => 'nullable|string|max:5',
            'modhora' => 'nullable|string|max:8',
            'modfecha' => 'nullable|date',
            'moduser' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()->toArray(),
            ], 422);
        }

        $vale->update($data);

        return response()->json([
            'message' => 'Vale updated successfully',
            'vale' => $vale->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $vale = Vale::find($id);

        if (! $vale) {
            return response()->json(['error' => 'Vale not found'], 404);
        }

        $vale->delete();

        return response()->json(['message' => 'Vale deleted successfully']);
    }
}
