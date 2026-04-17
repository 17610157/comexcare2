<?php

namespace App\Http\Controllers;

use App\Models\Vale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteValesController extends Controller
{
    public function index(Request $request)
    {
        $plazas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values();

        $procedencias = Vale::distinct()
            ->whereNotNull('cve_pro_cl')
            ->orderBy('cve_pro_cl')
            ->pluck('cve_pro_cl')
            ->filter()
            ->values();

        $tiendas = Vale::distinct()
            ->whereNotNull('desc_mov')
            ->selectRaw('LEFT(desc_mov, 5) as tienda')
            ->orderBy('tienda')
            ->pluck('tienda')
            ->filter()
            ->unique()
            ->values();

        $tiposMovim = Vale::distinct()
            ->whereNotNull('tipo_movim')
            ->orderBy('tipo_movim')
            ->pluck('tipo_movim')
            ->filter()
            ->values();

        $today = now()->format('Y-m-d');

        return view('reportes.vales.index', compact('plazas', 'procedencias', 'tiendas', 'tiposMovim', 'today'));
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 50);
        $search = $request->input('search.value', '');
        $offsetInt = (int) $startIdx;
        $lengthInt = (int) $length;

        try {
            $query = Vale::query();

            $today = now()->format('Y-m-d');

            if ($request->filled('plaza')) {
                $plazas = is_array($request->plaza) ? $request->plaza : [$request->plaza];
                $tiendasEnPlaza = DB::table('bi_sys_tiendas')
                    ->whereIn('id_plaza', $plazas)
                    ->pluck('clave_tienda');
                $query->whereIn('cve_pro_cl', $tiendasEnPlaza);
            }

            if ($request->filled('procedencia')) {
                $procedencias = is_array($request->procedencia) ? $request->procedencia : [$request->procedencia];
                $query->whereIn('cve_pro_cl', $procedencias);
            }

            if ($request->filled('almacen')) {
                $tiendas = is_array($request->almacen) ? $request->almacen : [$request->almacen];
                $query->where(function ($q) use ($tiendas) {
                    foreach ($tiendas as $t) {
                        $q->orWhere('desc_mov', 'LIKE', $t.'%');
                    }
                });
            }

            if ($request->filled('tipo_movim')) {
                $query->where('tipo_movim', $request->tipo_movim);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha', '>=', $request->fecha_desde);
            } else {
                $query->whereDate('fecha', '>=', $today);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha', '<=', $request->fecha_hasta);
            } else {
                $query->whereDate('fecha', '<=', $today);
            }

            if ($request->filled('no_consec')) {
                $query->where('no_consec', $request->no_consec);
            }

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('no_consec', 'ILIKE', '%'.$search.'%')
                        ->orWhere('desc_mov', 'ILIKE', '%'.$search.'%')
                        ->orWhere('cve_pro_cl', 'ILIKE', '%'.$search.'%')
                        ->orWhere('folio_ref', 'ILIKE', '%'.$search.'%');
                });
            }

            $total = $query->count();

            $vales = $query->orderBy('fecha', 'desc')
                ->orderBy('no_consec', 'desc')
                ->offset($offsetInt)
                ->limit($lengthInt)
                ->get();

            $data = $vales->map(function ($vale) {
                $tienda = $vale->desc_mov ? substr($vale->desc_mov, 0, 5) : '';
                $plaza = $vale->plaza ?? '';

                if (! $plaza && $vale->computer_id) {
                    $computer = \App\Models\Computer::find($vale->computer_id);
                    if ($computer && $computer->plaza) {
                        $plaza = $computer->plaza;
                    }
                }

                return [
                    'id' => $vale->id,
                    'tipo_movim' => $vale->tipo_movim ?? '',
                    'no_consec' => $vale->no_consec ?? '',
                    'fecha' => $vale->fecha ? $vale->fecha->format('Y-m-d') : '',
                    'cve_pro_cl' => $vale->cve_pro_cl ?? '',
                    'desc_mov' => $vale->desc_mov ?? '',
                    'ent_sal' => $vale->ent_sal ?? '',
                    'tienda' => $tienda,
                    'plaza' => $plaza,
                    'estado' => $vale->estado ?? '',
                    'precio_tot' => $vale->precio_tot,
                    'impues_tot' => $vale->impues_tot,
                    'cost_tot' => $vale->cost_tot,
                    'folio_ref' => $vale->folio_ref ?? '',
                    'mov_origen' => $vale->mov_origen ?? '',
                    'ya_exporta' => $vale->ya_exporta ?? '',
                    'clave_usu' => $vale->clave_usu ?? '',
                    'modfecha' => $vale->modfecha ? $vale->modfecha->format('Y-m-d') : '',
                    'modhora' => $vale->modhora ?? '',
                    'created_at' => $vale->created_at ? $vale->created_at->format('Y-m-d H:i:s') : '',
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Vales data error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $query = Vale::query();

            $today = now()->format('Y-m-d');

            if ($request->filled('plaza')) {
                $plazas = is_array($request->plaza) ? $request->plaza : [$request->plaza];
                $tiendasEnPlaza = DB::table('bi_sys_tiendas')
                    ->whereIn('id_plaza', $plazas)
                    ->pluck('clave_tienda');
                $query->whereIn('cve_pro_cl', $tiendasEnPlaza);
            }

            if ($request->filled('procedencia')) {
                $procedencias = is_array($request->procedencia) ? $request->procedencia : [$request->procedencia];
                $query->whereIn('cve_pro_cl', $procedencias);
            }

            if ($request->filled('almacen')) {
                $tiendas = is_array($request->almacen) ? $request->almacen : [$request->almacen];
                $query->where(function ($q) use ($tiendas) {
                    foreach ($tiendas as $t) {
                        $q->orWhere('desc_mov', 'LIKE', $t.'%');
                    }
                });
            }

            if ($request->filled('tipo_movim')) {
                $query->where('tipo_movim', $request->tipo_movim);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha', '>=', $request->fecha_desde);
            } else {
                $query->whereDate('fecha', '>=', $today);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha', '<=', $request->fecha_hasta);
            } else {
                $query->whereDate('fecha', '<=', $today);
            }

            $vales = $query->orderBy('fecha', 'desc')
                ->orderBy('no_consec', 'desc')
                ->get();

            $filename = 'Reporte_Vales_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ];

            $callback = function () use ($vales) {
                $output = fopen('php://output', 'w');

                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'ID', 'Tipo Mov', 'No. Consec', 'Fecha', 'Cve Pro/Cl', 'Desc Mov',
                    'Ent/Sal', 'Tienda', 'Plaza', 'Estado', 'Precio Tot', 'Impues Tot',
                    'Cost Tot', 'Folio Ref', 'Mov Origen', 'Ya Exporta', 'Clave Usu',
                    'Mod Fecha', 'Mod Hora', 'Fecha Alta',
                ]);

                foreach ($vales as $vale) {
                    $tienda = $vale->desc_mov ? substr($vale->desc_mov, 0, 5) : '';
                    $plaza = $vale->plaza ?? '';

                    if (! $plaza && $vale->computer_id) {
                        $computer = \App\Models\Computer::find($vale->computer_id);
                        if ($computer && $computer->plaza) {
                            $plaza = $computer->plaza;
                        }
                    }
                    fputcsv($output, [
                        $vale->id,
                        $vale->tipo_movim ?? '',
                        $vale->no_consec ?? '',
                        $vale->fecha ? $vale->fecha->format('Y-m-d') : '',
                        $vale->cve_pro_cl ?? '',
                        $vale->desc_mov ?? '',
                        $vale->ent_sal ?? '',
                        $tienda,
                        $plaza,
                        $vale->estado ?? '',
                        $vale->precio_tot,
                        $vale->impues_tot,
                        $vale->cost_tot,
                        $vale->folio_ref ?? '',
                        $vale->mov_origen ?? '',
                        $vale->ya_exporta ?? '',
                        $vale->clave_usu ?? '',
                        $vale->modfecha ? $vale->modfecha->format('Y-m-d') : '',
                        $vale->modhora ?? '',
                        $vale->created_at ? $vale->created_at->format('Y-m-d H:i:s') : '',
                    ]);
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Vales export error: '.$e->getMessage());

            return redirect()->route('reportes.vales')
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
