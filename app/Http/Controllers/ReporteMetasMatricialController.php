<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MetasMatricialExport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteMetasMatricialController extends Controller
{
    /**
     * Mostrar reporte metas matricial
     */
    public function index(Request $request)
    {
        // Sin verificación de permisos - versión básica
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $zona = $request->input('zona', '');

        $filtros = [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => $plaza,
            'tienda' => $tienda,
            'zona' => $zona
        ];

        try {
            $inicio_tiempo = microtime(true);
            $datos = ReportService::getMetasMatricialReport($filtros);
            $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            return view('reportes.metas_matricial.index', compact(
                'fecha_inicio',
                'fecha_fin',
                'plaza',
                'tienda',
                'zona',
                'datos',
                'tiempo_carga'
            ));

        } catch (\Exception $e) {
            Log::error('Error en reporte metas matricial: ' . $e->getMessage());
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }

    /**
     * Exportar a Excel usando PhpSpreadsheet
     */
    public function exportExcel(Request $request)
    {
        try {
            // Obtener datos
            $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'plaza', 'tienda', 'zona']);
            $filtros['fecha_inicio'] = $filtros['fecha_inicio'] ?? date('Y-m-01');
            $filtros['fecha_fin'] = $filtros['fecha_fin'] ?? date('Y-m-d');

            $datos = ReportService::getMetasMatricialReport($filtros);

            // Verificar si hay datos
            if (empty($datos['tiendas'])) {
                return back()->with('error', 'No hay datos para exportar');
            }

            // Exportar con PhpSpreadsheet
            return $this->exportWithPhpSpreadsheet($datos, $filtros);

        } catch (\Exception $e) {
            error_log("Error PhpSpreadsheet: " . $e->getMessage());
            try {
                $datos = ReportService::getMetasMatricialReport($filtros);
                return $this->exportWithHTML($datos, $filtros);
            } catch (\Exception $ex) {
                return back()->with('error', 'Error al exportar: ' . $e->getMessage());
            }
        }
    }

    /**
     * Función para exportar con PhpSpreadsheet
     */
    private function exportWithPhpSpreadsheet($datos, $filtros)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Título
        $sheet->setTitle('Metas Matricial');
        $sheet->setCellValue('A1', 'REPORTE METAS MATRICIAL');
        $num_cols = count($datos['tiendas']) + 1; // tiendas + total
        $last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($num_cols);
        $sheet->mergeCells('A1:' . $last_col . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Información
        $sheet->setCellValue('A2', 'Fecha exportación:');
        $sheet->setCellValue('B2', date('d/m/Y H:i:s'));
        $sheet->setCellValue('A3', 'Periodo:');
        $sheet->setCellValue('B3', $filtros['fecha_inicio'] . ' al ' . $filtros['fecha_fin']);

        $fila = 5;

        // Filtros
        if ($filtros['plaza']) {
            $sheet->setCellValue('A' . $fila, 'Plaza:');
            $sheet->setCellValue('B' . $fila, $filtros['plaza']);
            $fila++;
        }
        if ($filtros['tienda']) {
            $sheet->setCellValue('A' . $fila, 'Tienda:');
            $sheet->setCellValue('B' . $fila, $filtros['tienda']);
            $fila++;
        }
        if ($filtros['zona']) {
            $sheet->setCellValue('A' . $fila, 'Zona:');
            $sheet->setCellValue('B' . $fila, $filtros['zona']);
            $fila++;
        }

        $fila += 2;

        // Encabezados
        $col = 1;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Categoría / Fecha');

        foreach ($datos['tiendas'] as $tienda) {
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $tienda);
        }

        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Total');

        // Estilo encabezados
        $header_range = 'A' . $fila . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila;
        $sheet->getStyle($header_range)->getFont()->setBold(true);
        $sheet->getStyle($header_range)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF343A40');
        $sheet->getStyle($header_range)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($header_range)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $fila++;

        // Fila Plaza
        $col = 1;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Plaza');
        foreach ($datos['tiendas'] as $tienda) {
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $datos['matriz']['info'][$tienda]['plaza']);
        }
        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, '-');
        $fila++;

        // Fila Zona
        $col = 1;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Zona');
        foreach ($datos['tiendas'] as $tienda) {
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $datos['matriz']['info'][$tienda]['zona']);
        }
        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, '-');
        $fila++;

        // Filas de totales diarios
        foreach ($datos['fechas'] as $fecha) {
            $col = 1;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Total ' . \Carbon\Carbon::parse($fecha)->format('d/m'));
            $suma = 0;
            foreach ($datos['tiendas'] as $tienda) {
                $col++;
                $total = $datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $total);
                $suma += $total;
            }
            $col++;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $suma);
            $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila)->getFont()->setBold(true);
            $fila++;
        }

        // Suma Totales
        $col = 1;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Suma de los Días Consultados');
        $suma = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $col++;
            $total = $datos['matriz']['totales'][$tienda]['total'] ?? 0;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $total);
            $suma += $total;
        }
        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $suma);
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila)->getFont()->setBold(true);
        $fila++;

        // Objetivo
        $col = 1;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, 'Objetivo');
        $suma = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $col++;
            $meta_total = $datos['matriz']['info'][$tienda]['meta_total'] ?? 0;
            if ($meta_total > 0) {
                $objetivo = $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $objetivo);
                $suma += $objetivo;
            } else {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, '-');
            }
        }
        $col++;
        $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila, $suma);
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $fila)->getFont()->setBold(true);
        $fila++;

        // Agregar más filas similares para las demás categorías...

        // Ajustar anchos
        $sheet->getColumnDimension('A')->setWidth(20);
        for ($i = 2; $i <= $num_cols; $i++) {
            $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setWidth(15);
        }

        // Descargar
        $filename = 'Metas_Matricial_' . date('Ymd_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Obtener datos agrupados por plaza para PDF
     */
    private function obtenerDatosAgrupadosPorPlaza(Request $request)
    {
        // Obtener datos básicos
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'plaza', 'tienda', 'zona']);
        $filtros['fecha_inicio'] = $filtros['fecha_inicio'] ?? date('Y-m-01');
        $filtros['fecha_fin'] = $filtros['fecha_fin'] ?? date('Y-m-d');

        $datos = ReportService::getMetasMatricialReport($filtros);

        // Agrupar vendedores por plaza
        $plazas = [];

        foreach ($datos['matriz']['info'] as $tienda => $info) {
            $plaza_val = $info['plaza'];
            if (!isset($plazas[$plaza_val])) {
                $plazas[$plaza_val] = [
                    'tiendas' => [],
                    'totales' => [
                        'total' => 0,
                        'objetivo' => 0,
                        'porcentaje_total' => 0,
                        'meta_total' => 0,
                        'suma_valor_dia' => 0
                    ],
                    'datos_diarios' => []
                ];
            }

            $plazas[$plaza_val]['tiendas'][$tienda] = [
                'info' => $info,
                'datos' => $datos['matriz']['datos'][$tienda] ?? [],
                'totales' => $datos['matriz']['totales'][$tienda] ?? []
            ];

            // Sumar totales por plaza
            $plazas[$plaza_val]['totales']['total'] += $datos['matriz']['totales'][$tienda]['total'] ?? 0;
            if (($info['meta_total'] ?? 0) > 0) {
                $plazas[$plaza_val]['totales']['objetivo'] += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
            }
            $plazas[$plaza_val]['totales']['meta_total'] += $info['meta_total'] ?? 0;
            $plazas[$plaza_val]['totales']['suma_valor_dia'] += $info['suma_valor_dia'] ?? 0;

            // Datos diarios por plaza
            foreach ($datos['fechas'] as $fecha) {
                if (!isset($plazas[$plaza_val]['datos_diarios'][$fecha])) {
                    $plazas[$plaza_val]['datos_diarios'][$fecha] = 0;
                }
                $plazas[$plaza_val]['datos_diarios'][$fecha] += $datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0;
            }
        }

        // Calcular porcentaje por plaza
        foreach ($plazas as $plaza => $data) {
            $plazas[$plaza]['totales']['porcentaje_total'] = $data['totales']['objetivo'] > 0 ?
                ($data['totales']['total'] / $data['totales']['objetivo']) * 100 : 0;
        }

        // Ordenar plazas alfabéticamente
        ksort($plazas);

        // Agregar las plazas al array de datos
        $datos['plazas'] = $plazas;

        return $datos;
    }

    /**
     * Función alternativa para exportar HTML/Excel (fallback)
     */
    private function exportWithHTML($datos, $filtros)
    {
        $filename = 'Metas_Matricial_' . date('Ymd_His') . '.xls';

        return response(view('reportes.metas_matricial.export_excel', compact('datos', 'filtros')))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            // Obtener datos agrupados por plaza
            $datos = $this->obtenerDatosAgrupadosPorPlaza($request);

            // Verificar si hay datos
            if (empty($datos['plazas'])) {
                return back()->with('error', 'No hay datos para exportar');
            }

            // Generar PDF con configuración para evitar timeouts
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $pdf = Pdf::loadView('reportes.metas_matricial.export_pdf', $datos);

            // Configurar papel horizontal
            $pdf->setPaper('landscape', 'letter');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'dpi' => 150
            ]);

            // Nombre del archivo
            $filename = 'Metas_Matricial_' . date('Ymd_His') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }
}