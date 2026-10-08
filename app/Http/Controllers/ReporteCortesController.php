<?php

namespace App\Http\Controllers;

use App\Models\XcorteApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteCortesController extends Controller
{
    public function index()
    {
        $plazas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values();

        $tiendas = XcorteApi::query()
            ->distinct()
            ->whereNotNull('clave_tienda')
            ->orderBy('clave_tienda')
            ->pluck('clave_tienda')
            ->filter()
            ->values();

        return view('reportes.cortes.index', compact('plazas', 'tiendas'));
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 50);
        $search = (string) $request->input('search.value', '');

        try {
            $query = XcorteApi::query();

            if ($request->filled('plaza')) {
                $plazas = is_array($request->plaza) ? $request->plaza : [$request->plaza];
                $query->whereIn('plaza', $plazas);
            }

            if ($request->filled('tienda')) {
                $tiendas = is_array($request->tienda) ? $request->tienda : [$request->tienda];
                $query->whereIn('clave_tienda', $tiendas);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_corte', '>=', $request->input('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_corte', '<=', $request->input('fecha_hasta'));
            }

            if ($search !== '') {
                $term = '%'.mb_strtolower($search).'%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(clave_tienda) LIKE ?', [$term])
                        ->orWhereRaw("LOWER(COALESCE(plaza, '')) LIKE ?", [$term]);
                });
            }

            $total = $query->count();

            $cortes = $query->orderByDesc('fecha_corte')
                ->orderByDesc('id')
                ->offset($startIdx)
                ->limit($length)
                ->get();

            $data = $cortes->map(function (XcorteApi $corte) {
                $contado = (float) $corte->monto_contado;
                $credito = (float) $corte->monto_credito;

                return [
                    'id' => $corte->id,
                    'fecha_corte' => $corte->fecha_corte,
                    'clave_tienda' => $corte->clave_tienda ?? '',
                    'plaza' => $corte->plaza ?? '',
                    'monto_contado' => number_format($contado, 2, '.', ''),
                    'monto_credito' => number_format($credito, 2, '.', ''),
                    'total' => number_format($contado + $credito, 2, '.', ''),
                    'fecha_registro' => $corte->fecha_registro ? $corte->fecha_registro->format('Y-m-d H:i:s') : '',
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Cortes data error: '.$e->getMessage());

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
