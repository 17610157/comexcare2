<?php

namespace App\Http\Controllers;

use App\Exports\VendedoresExport;
use App\Helpers\RoleHelper;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReporteVendedoresController extends Controller
{
    /**
     * Mostrar la vista principal del reporte
     */
    public function index(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (!$userFilter['allowed']) {
            return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
        }

        $startDefault = Carbon::parse('first day of previous month')->toDateString();
        $endDefault = Carbon::parse('last day of previous month')->toDateString();

        $listas = RoleHelper::getListasParaFiltros();
        
        $plazas = $listas['plazas'];
        $tiendas = $listas['tiendas'];

        return view('reportes.vendedores.index', compact('plazas', 'tiendas', 'startDefault', 'endDefault'));
    }

    /**
     * Data para DataTable
     */
    public function data(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (!$userFilter['allowed']) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('Vendedores data request', ['url' => $request->fullUrl(), 'params' => $request->all(), 'filter' => $userFilter]);

        $start = Carbon::parse('first day of previous month')->toDateString();
        $end = Carbon::parse('last day of previous month')->toDateString();
        if ($request->filled('period_start')) {
            $start = $request->input('period_start');
        }
        if ($request->filled('period_end')) {
            $end = $request->input('period_end');
        }

        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value', '');
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;

        $tiendasPermitidas = RoleHelper::getTiendasAcceso();
        $plazasPermitidas = $userFilter['plazas_asignadas'] ?? [];

        try {
            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => '',
                'tienda' => '',
                'vendedor' => '',
            ];

            if (!empty($plazasPermitidas)) {
                $filtros['plaza'] = implode(',', $plazasPermitidas);
            }

            if (!empty($tiendasPermitidas)) {
                $filtros['tienda'] = implode(',', $tiendasPermitidas);
            }

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $plazaFilter = $request->input('plaza');
                if (is_array($plazaFilter) && count($plazaFilter) > 0) {
                    $filtros['plaza'] = implode(',', $plazaFilter);
                } elseif (!is_array($plazaFilter)) {
                    $filtros['plaza'] = trim($plazaFilter);
                }
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $tiendaFilter = $request->input('tienda');
                if (is_array($tiendaFilter) && count($tiendaFilter) > 0) {
                    $filtros['tienda'] = implode(',', $tiendaFilter);
                } elseif (!is_array($tiendaFilter)) {
                    $filtros['tienda'] = trim($tiendaFilter);
                }
            }

            if ($request->filled('vendedor') && $request->input('vendedor') !== '') {
                $filtros['vendedor'] = trim($request->input('vendedor'));
            }

            $resultados = ReportService::getVendedoresReport($filtros);

            if (!empty($search)) {
                $resultados = $resultados->filter(function ($item) use ($search) {
                    $searchLower = strtolower($search);
                    return str_contains(strtolower($item['tienda_vendedor'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['vendedor_dia'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['plaza_ajustada'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['ctienda'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['vend_clave'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['fecha'] ?? ''), $searchLower);
                });
            }

            $total = $resultados->count();

            $data = $resultados->slice($offsetInt, $lengthInt)->map(function ($item, $index) use ($offsetInt) {
                return [
                    'no' => $offsetInt + $index + 1,
                    'tienda_vendedor' => $item['tienda_vendedor'] ?? '',
                    'vendedor_dia' => $item['vendedor_dia'] ?? '',
                    'plaza_ajustada' => $item['plaza_ajustada'] ?? '',
                    'ctienda' => $item['ctienda'] ?? '',
                    'vend_clave' => $item['vend_clave'] ?? '',
                    'fecha' => $item['fecha'] ?? '',
                    'venta_total' => $item['venta_total'] ?? 0,
                    'devolucion' => $item['devolucion'] ?? 0,
                    'venta_neta' => $item['venta_neta'] ?? 0,
                ];
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Vendedores data error: '.$e->getMessage());

            return response()->json(['draw' => (int) $request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        try {
            $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
            $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => is_array($plaza) ? implode(',', $plaza) : $plaza,
                'tienda' => is_array($tienda) ? implode(',', $tienda) : $tienda,
                'vendedor' => $vendedor,
            ];

            return Excel::download(new VendedoresExport($filtros),
                'Reporte_Vendedores_'.date('Ymd_His').'.xlsx'
            );

        } catch (\Exception $e) {
            Log::error('Error en export: '.$e->getMessage());

            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }

    /**
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
            $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => is_array($plaza) ? implode(',', $plaza) : $plaza,
                'tienda' => is_array($tienda) ? implode(',', $tienda) : $tienda,
                'vendedor' => $vendedor,
            ];

            $resultados = ReportService::getVendedoresReport($filtros);

            $filename = 'Reporte_Vendedores_'.date('Ymd_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($resultados) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'Tienda-Vendedor', 'Vendedor-Día', 'Plaza Ajustada', 'Tienda', 'Vendedor', 'Fecha', 'Venta Total', 'Devolución', 'Venta Neta'
                ]);

                foreach ($resultados as $row) {
                    fputcsv($file, [
                        $row['tienda_vendedor'] ?? '',
                        $row['vendedor_dia'] ?? '',
                        $row['plaza_ajustada'] ?? '',
                        $row['ctienda'] ?? '',
                        $row['vend_clave'] ?? '',
                        $row['fecha'] ?? '',
                        $row['venta_total'] ?? 0,
                        $row['devolucion'] ?? 0,
                        $row['venta_neta'] ?? 0,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error en exportCsv: '.$e->getMessage());

            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al exportar CSV: '.$e->getMessage());
        }
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
            $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => is_array($plaza) ? implode(',', $plaza) : $plaza,
                'tienda' => is_array($tienda) ? implode(',', $tienda) : $tienda,
                'vendedor' => $vendedor,
            ];

            $resultados = ReportService::getVendedoresReport($filtros);
            $estadisticas = ReportService::calcularEstadisticasVendedores($resultados);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.vendedores.pdf', compact('resultados', 'estadisticas', 'filtros'))
                ->setPaper('a4', 'landscape');

            return $pdf->download('Reporte_Vendedores_'.date('Ymd_His').'.pdf');

        } catch (\Exception $e) {
            Log::error('Error en exportPdf: '.$e->getMessage());

            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al exportar PDF: '.$e->getMessage());
        }
    }
}
