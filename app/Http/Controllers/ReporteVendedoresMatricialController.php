<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteVendedoresMatricialController extends Controller
{
    /**
     * Mostrar la vista matricial principal
     */
    public function index(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
        }

        // Fechas por defecto
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $vendedor = $request->input('vendedor', '');

        // Listas para filtros (limitadas por el filtro del usuario)
        $plazasQuery = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza');

        $tiendasQuery = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('clave_tienda')
            ->orderBy('clave_tienda');

        // Aplicar filtro de plazas asignadas al usuario
        $plazasAsignadas = $userFilter['plazas_asignadas'] ?? [];
        $tiendasAsignadas = $userFilter['tiendas_asignadas'] ?? [];

        if (! empty($plazasAsignadas)) {
            $plazasQuery->whereIn('id_plaza', $plazasAsignadas);
            $tiendasQuery->whereIn('id_plaza', $plazasAsignadas);
        }

        if (! empty($tiendasAsignadas)) {
            $tiendasQuery->whereIn('clave_tienda', $tiendasAsignadas);
        }

        $plazas = $plazasQuery->pluck('id_plaza')->filter()->values();
        $tiendas = $tiendasQuery->pluck('clave_tienda')->filter()->values();

        // Procesar valores del request, validando contra asignaciones del usuario
        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');

        // Si tiene plazas/tiendas asignadas, validar que los valores estén permitidos
        if (! empty($plazasAsignadas)) {
            if (empty($plazaInput)) {
                $plazaInput = $plazasAsignadas;
            } else {
                $plazaValues = is_array($plazaInput) ? $plazaInput : explode(',', $plazaInput);
                $plazaValues = array_filter($plazaValues, fn ($p) => in_array($p, $plazasAsignadas));
                $plazaInput = ! empty($plazaValues) ? array_values($plazaValues) : $plazasAsignadas;
            }
        }

        if (! empty($tiendasAsignadas)) {
            if (empty($tiendaInput)) {
                $tiendaInput = $tiendasAsignadas;
            } else {
                $tiendaValues = is_array($tiendaInput) ? $tiendaInput : explode(',', $tiendaInput);
                $tiendaValues = array_filter($tiendaValues, fn ($t) => in_array($t, $tiendasAsignadas));
                $tiendaInput = ! empty($tiendaValues) ? array_values($tiendaValues) : $tiendasAsignadas;
            }
        }

        // Convertir arrays a strings
        $plaza = is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput;
        $tienda = is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput;

        // Convertir fechas
        $f_inicio = str_replace('-', '', $fecha_inicio);
        $f_fin = str_replace('-', '', $fecha_fin);

        $vendedores_data = [];
        $dias = [];
        $error_msg = '';
        $tiempo_carga = 0;

        // Solo procesar si hay fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $inicio_tiempo = microtime(true);

            try {
                $filtros = [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                    'plaza' => $plaza,
                    'tienda' => $tienda,
                    'vendedor' => $vendedor,
                ];

                $datos_matriciales = ReportService::getVendedoresMatricialReport($filtros);
                $vendedores_data = $datos_matriciales['vendedores_info'];
                $dias = $datos_matriciales['dias'];

                $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            } catch (\Exception $e) {
                $error_msg = 'Error en la consulta: '.$e->getMessage();
            }
        }

        // Calcular totales
        $total_por_vendedor = [];
        $total_por_dia = [];
        $total_general = 0;

        foreach ($vendedores_data as $vendedor_id => $data) {
            $total_por_vendedor[$vendedor_id] = 0;
            foreach ($data['ventas'] as $dia_key => $venta) {
                $total_por_vendedor[$vendedor_id] += $venta;
                if (! isset($total_por_dia[$dia_key])) {
                    $total_por_dia[$dia_key] = 0;
                }
                $total_por_dia[$dia_key] += $venta;
                $total_general += $venta;
            }
        }

        return view('reportes.vendedores_matricial.index', compact(
            'fecha_inicio',
            'fecha_fin',
            'plaza',
            'tienda',
            'vendedor',
            'vendedores_data',
            'dias',
            'total_por_vendedor',
            'total_por_dia',
            'total_general',
            'error_msg',
            'tiempo_carga',
            'plazas',
            'tiendas'
        ));
    }

    /**
     * Exportar a Excel (vista matricial) - Usando PhpSpreadsheet (Sin Laravel Excel)
     */
    public function exportExcel(Request $request)
    {
        try {
            // Obtener datos
            $datos = $this->obtenerDatosParaExportar($request);

            // Verificar si hay datos
            if (empty($datos['vendedores_info'])) {
                return back()->with('error', 'No hay datos para exportar');
            }

            // Exportar con PhpSpreadsheet
            return $this->exportWithPhpSpreadsheet($datos);

        } catch (\Exception $e) {
            // Fallback a HTML si hay error con PhpSpreadsheet
            error_log('Error PhpSpreadsheet: '.$e->getMessage());
            try {
                $datos = $this->obtenerDatosParaExportar($request);

                return $this->exportWithHTML($datos);
            } catch (\Exception $ex) {
                return back()->with('error', 'Error al exportar: '.$e->getMessage());
            }
        }
    }

    /**
     * Exportar a CSV (vista matricial) - SOLUCIÓN SIMPLE
     */
    public function exportCsv(Request $request)
    {
        try {
            // Obtener datos
            $datos = $this->obtenerDatosParaExportar($request);

            // Verificar si hay datos
            if (empty($datos['vendedores_info'])) {
                return back()->with('error', 'No hay datos para exportar');
            }

            $filename = 'Reporte_Vendedores_Matricial_'.date('Ymd_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($datos) {
                $file = fopen('php://output', 'w');

                // Escribir encabezados
                $encabezados = ['Descripción'];
                foreach (array_keys($datos['vendedores_info']) as $vendedor_id) {
                    $encabezados[] = $vendedor_id;
                }
                $encabezados[] = 'TOTAL DÍA';
                fputcsv($file, $encabezados, ',');

                // Fila de nombres
                $filaNombres = ['NOMBRE'];
                foreach ($datos['vendedores_info'] as $data) {
                    $filaNombres[] = $data['nombre'];
                }
                $filaNombres[] = '-';
                fputcsv($file, $filaNombres, ',');

                // Fila de tipo
                $filaTipo = ['TIPO'];
                foreach ($datos['vendedores_info'] as $data) {
                    $filaTipo[] = $data['tipo'];
                }
                $filaTipo[] = '-';
                fputcsv($file, $filaTipo, ',');

                // Fila de tiendas
                $filaTiendas = ['TIENDAS'];
                foreach ($datos['vendedores_info'] as $data) {
                    $filaTiendas[] = implode(', ', $data['tiendas']);
                }
                $filaTiendas[] = '-';
                fputcsv($file, $filaTiendas, ',');

                // Fila de plazas
                $filaPlazas = ['PLAZA'];
                foreach ($datos['vendedores_info'] as $data) {
                    $filaPlazas[] = implode(', ', $data['plazas']);
                }
                $filaPlazas[] = '-';
                fputcsv($file, $filaPlazas, ',');

                // Filas de días
                foreach ($datos['dias'] as $dia_key => $dia_formatted) {
                    $filaDia = [$dia_formatted];
                    $total_dia = 0;

                    foreach ($datos['vendedores_info'] as $vendedor_id => $data) {
                        $venta = $data['ventas'][$dia_key] ?? 0;
                        $total_dia += $venta;
                        $filaDia[] = $venta > 0 ? $venta : '-';
                    }

                    $filaDia[] = $total_dia;
                    fputcsv($file, $filaDia, ',');
                }

                // Fila de totales por vendedor
                $filaTotales = ['TOTAL VENDEDOR'];
                $total_general = 0;

                foreach ($datos['vendedores_info'] as $vendedor_id => $data) {
                    $total_vendedor = array_sum($data['ventas']);
                    $total_general += $total_vendedor;
                    $filaTotales[] = $total_vendedor;
                }

                $filaTotales[] = $total_general;
                fputcsv($file, $filaTotales, ',');

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al exportar CSV: '.$e->getMessage());
        }
    }

    /**
     * Exportar a PDF (vista matricial) - Con una página por plaza
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
            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('reportes.vendedores_matricial.export_pdf', $datos);

            // Configurar papel horizontal
            $pdf->setPaper('landscape', 'letter');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial',
                'dpi' => 150,
            ]);

            // Nombre del archivo
            $filename = 'Reporte_Vendedores_Matricial_'.date('Ymd_His').'.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar PDF: '.$e->getMessage());
        }
    }

    /**
     * Obtener datos para exportar
     */
    private function obtenerDatosParaExportar(Request $request)
    {
        // Obtener datos del request
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');
        $vendedor = $request->input('vendedor', '');

        // Convertir arrays a strings
        $plaza = is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput;
        $tienda = is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput;

        $f_inicio = str_replace('-', '', $fecha_inicio);
        $f_fin = str_replace('-', '', $fecha_fin);

        // Consulta SQL
        $sql = "
        SELECT 
            c.vend_clave,
            a.nombre,
            a.tipo,
            c.ctienda,
            c.cplaza,
            c.nota_fecha,
            SUM(c.nota_impor) AS venta_total,
            COALESCE(( 
                SELECT SUM(v.total_brut + v.impuesto)
                FROM venta v
                WHERE v.f_emision = c.nota_fecha
                  AND v.clave_vend = c.vend_clave
                  AND v.cplaza = c.cplaza
                  AND v.ctienda = c.ctienda
                  AND v.tipo_doc = 'DV'
                  AND v.estado NOT LIKE '%C%'
                  AND EXISTS (
                      SELECT 1 FROM partvta p 
                      WHERE v.no_referen = p.no_referen 
                        AND v.cplaza = p.cplaza 
                        AND v.ctienda = p.ctienda
                        AND p.clave_art NOT LIKE '%CAMBIODOC%'
                        AND p.totxpart IS NOT NULL
                  )
            ), 0) AS devolucion
        FROM canota c 
        JOIN asesores_vvt a ON (a.plaza = c.cplaza AND a.asesor = c.vend_clave)
        WHERE c.ban_status <> 'C' 
          AND c.nota_fecha BETWEEN :f_inicio AND :f_fin
          AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
          AND c.ctienda NOT LIKE '%DESC%' 
          AND c.ctienda NOT LIKE '%CEDI%' ";

        // Agregar filtros
        $params = ['f_inicio' => $f_inicio, 'f_fin' => $f_fin];

        if (! empty($plaza)) {
            $sql .= ' AND c.cplaza = :plaza';
            $params['plaza'] = $plaza;
        }

        if (! empty($tienda)) {
            $sql .= ' AND c.ctienda = :tienda';
            $params['tienda'] = $tienda;
        }

        if (! empty($vendedor)) {
            $sql .= ' AND c.vend_clave = :vendedor';
            $params['vendedor'] = $vendedor;
        }

        $sql .= ' GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave, a.nombre, a.tipo
                  ORDER BY c.vend_clave, c.nota_fecha';

        $resultados_raw = DB::select($sql, $params);

        // Procesar datos en nueva estructura
        $vendedores_info = [];
        $dias = [];

        // Crear array de días entre fechas
        $start = new \DateTime($fecha_inicio);
        $end = new \DateTime($fecha_fin);
        $end->modify('+1 day'); // Para incluir el último día
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);

        foreach ($dateRange as $date) {
            $dia_key = $date->format('Ymd');
            $dias[$dia_key] = $date->format('Y-m-d');
        }

        foreach ($resultados_raw as $row) {
            $vendedor_id = $row->vend_clave;
            $nombre = $row->nombre;
            $tipo = $row->tipo;
            $tienda_val = $row->ctienda;
            $plaza_val = $row->cplaza;
            $fecha_key = $row->nota_fecha;

            // Formatear fecha para clave
            if (strlen($fecha_key) == 8) {
                $fecha_key = $fecha_key; // Mantener formato Ymd
            } else {
                $fecha_key = str_replace('-', '', $fecha_key);
            }

            // Inicializar info del vendedor si no existe
            if (! isset($vendedores_info[$vendedor_id])) {
                $vendedores_info[$vendedor_id] = [
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'tiendas' => [],
                    'plazas' => [],
                    'ventas' => [],
                ];
            }

            // Agregar tienda única
            if (! in_array($tienda_val, $vendedores_info[$vendedor_id]['tiendas'])) {
                $vendedores_info[$vendedor_id]['tiendas'][] = $tienda_val;
            }

            // Agregar plaza única
            if (! in_array($plaza_val, $vendedores_info[$vendedor_id]['plazas'])) {
                $vendedores_info[$vendedor_id]['plazas'][] = $plaza_val;
            }

            // Calcular venta neta
            $venta_total = floatval($row->venta_total);
            $devolucion = floatval($row->devolucion);
            $venta_neta = $venta_total - $devolucion;

            // Acumular venta por día
            if (! isset($vendedores_info[$vendedor_id]['ventas'][$fecha_key])) {
                $vendedores_info[$vendedor_id]['ventas'][$fecha_key] = 0;
            }
            $vendedores_info[$vendedor_id]['ventas'][$fecha_key] += $venta_neta;
        }

        // Preparar datos para exportación
        return [
            'vendedores_info' => $vendedores_info,
            'dias' => $dias,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => $plaza,
            'tienda' => $tienda,
            'vendedor' => $vendedor,
        ];
    }

    /**
     * Obtener datos agrupados por plaza para PDF
     */
    private function obtenerDatosAgrupadosPorPlaza(Request $request)
    {
        // Obtener datos básicos
        $datos = $this->obtenerDatosParaExportar($request);

        // Agrupar vendedores por plaza
        $plazas = [];

        foreach ($datos['vendedores_info'] as $vendedor_id => $info) {
            // Cada vendedor puede tener múltiples plazas
            foreach ($info['plazas'] as $plaza_val) {
                if (! isset($plazas[$plaza_val])) {
                    $plazas[$plaza_val] = [
                        'vendedores' => [],
                        'dias' => $datos['dias'],
                        'total_plaza' => 0,
                    ];
                }

                // Agregar vendedor a la plaza
                $plazas[$plaza_val]['vendedores'][$vendedor_id] = $info;

                // Calcular total de la plaza
                $plazas[$plaza_val]['total_plaza'] += array_sum($info['ventas']);
            }
        }

        // Ordenar plazas alfabéticamente
        ksort($plazas);

        // Agregar las plazas al array de datos
        $datos['plazas'] = $plazas;

        return $datos;
    }

    /**
     * Función para exportar con PhpSpreadsheet
     */
    private function exportWithPhpSpreadsheet($datos)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Título
        $sheet->setTitle('Reporte Vendedores');
        $sheet->setCellValue('A1', 'REPORTE DE VENDEDORES - VISTA MATRICIAL');

        // Calcular cuántas columnas necesitamos (vendedores + 2 columnas)
        $num_vendedores = count($datos['vendedores_info']);
        $last_col_index = $num_vendedores + 2;
        $last_col_letter = Coordinate::stringFromColumnIndex($last_col_index);

        $sheet->mergeCells('A1:'.$last_col_letter.'1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Información del reporte
        $sheet->setCellValue('A2', 'Fecha exportación:');
        $sheet->setCellValue('B2', date('d/m/Y H:i:s'));
        $sheet->setCellValue('A3', 'Periodo:');
        $sheet->setCellValue('B3', $datos['fecha_inicio'].' al '.$datos['fecha_fin']);

        $fila = 5;

        if ($datos['plaza']) {
            $sheet->setCellValue('A'.$fila, 'Plaza:');
            $sheet->setCellValue('B'.$fila, $datos['plaza']);
            $fila++;
        }

        if ($datos['tienda']) {
            $sheet->setCellValue('A'.$fila, 'Tienda:');
            $sheet->setCellValue('B'.$fila, $datos['tienda']);
            $fila++;
        }

        if ($datos['vendedor']) {
            $sheet->setCellValue('A'.$fila, 'Vendedor:');
            $sheet->setCellValue('B'.$fila, $datos['vendedor']);
            $fila++;
        }

        $fila += 2; // Espacio antes de la tabla

        // Encabezados de columnas (vendedores)
        $col = 1; // Columna A=1
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'Descripción');

        $vendedor_keys = array_keys($datos['vendedores_info']);
        foreach ($vendedor_keys as $vendedor_id) {
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, $vendedor_id);
        }

        // Columna de total por día
        $col++;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'TOTAL DÍA');

        // Estilo encabezados
        $headerStart = 'A'.$fila;
        $headerEnd = $col_letter.$fila;
        $sheet->getStyle($headerStart.':'.$headerEnd)->getFont()->setBold(true);
        $sheet->getStyle($headerStart.':'.$headerEnd)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF343A40');
        $sheet->getStyle($headerStart.':'.$headerEnd)->getFont()->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerStart.':'.$headerEnd)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fila++;

        // Fila 1: Nombres
        $col = 1;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'NOMBRE');

        foreach ($datos['vendedores_info'] as $data) {
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, $data['nombre']);
            $sheet->getStyle($col_letter.$fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $col++;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, '-');

        // Estilo de la fila de nombres
        $rowStart = 'A'.$fila;
        $rowEnd = $col_letter.$fila;
        $sheet->getStyle($rowStart.':'.$rowEnd)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF8F9FA');

        $fila++;

        // Fila 2: Tipo
        $col = 1;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'TIPO');

        foreach ($datos['vendedores_info'] as $data) {
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, $data['tipo']);
            $sheet->getStyle($col_letter.$fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $col++;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, '-');

        // Estilo de la fila de tipo
        $rowStart = 'A'.$fila;
        $rowEnd = $col_letter.$fila;
        $sheet->getStyle($rowStart.':'.$rowEnd)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE3F2FD');
        $sheet->getStyle($rowStart.':'.$rowEnd)->getFont()->setBold(true);

        $fila++;

        // Fila 3: Tiendas
        $col = 1;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'TIENDAS');

        foreach ($datos['vendedores_info'] as $data) {
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, implode(', ', $data['tiendas']));
            $sheet->getStyle($col_letter.$fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $col++;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, '-');

        // Estilo de la fila de tiendas
        $rowStart = 'A'.$fila;
        $rowEnd = $col_letter.$fila;
        $sheet->getStyle($rowStart.':'.$rowEnd)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFF8F9FA');

        $fila++;

        // Fila 4: Plazas
        $col = 1;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'PLAZA');

        foreach ($datos['vendedores_info'] as $data) {
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, implode(', ', $data['plazas']));
            $sheet->getStyle($col_letter.$fila)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $col++;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, '-');

        // Estilo de la fila de plazas
        $rowStart = 'A'.$fila;
        $rowEnd = $col_letter.$fila;
        $sheet->getStyle($rowStart.':'.$rowEnd)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE9ECEF');
        $sheet->getStyle($rowStart.':'.$rowEnd)->getFont()->setBold(true);

        $fila++;

        // Filas de días
        foreach ($datos['dias'] as $dia_key => $dia_formatted) {
            $col = 1;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, $dia_formatted);
            $sheet->getStyle($col_letter.$fila)->getFont()->setBold(true);

            $total_dia = 0;

            foreach ($datos['vendedores_info'] as $vendedor_id => $data) {
                $col++;
                $col_letter = Coordinate::stringFromColumnIndex($col);
                $venta = $data['ventas'][$dia_key] ?? 0;
                $total_dia += $venta;

                if ($venta > 0) {
                    $sheet->setCellValue($col_letter.$fila, $venta);
                    $sheet->getStyle($col_letter.$fila)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFD1E7DD');
                } else {
                    $sheet->setCellValue($col_letter.$fila, '-');
                }

                // Formato numérico
                $sheet->getStyle($col_letter.$fila)
                    ->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Total del día
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue($col_letter.$fila, $total_dia);
            $sheet->getStyle($col_letter.$fila)->getFont()->setBold(true);
            $sheet->getStyle($col_letter.$fila)
                ->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle($col_letter.$fila)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFCFE2FF');

            $fila++;
        }

        // Fila de totales por vendedor
        $col = 1;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, 'TOTAL VENDEDOR');
        $sheet->getStyle($col_letter.$fila)->getFont()->setBold(true);

        $total_general = 0;

        foreach ($datos['vendedores_info'] as $vendedor_id => $data) {
            $col++;
            $col_letter = Coordinate::stringFromColumnIndex($col);
            $total_vendedor = array_sum($data['ventas']);
            $total_general += $total_vendedor;

            $sheet->setCellValue($col_letter.$fila, $total_vendedor);
            $sheet->getStyle($col_letter.$fila)->getFont()->setBold(true);
            $sheet->getStyle($col_letter.$fila)
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Total general
        $col++;
        $col_letter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($col_letter.$fila, $total_general);
        $sheet->getStyle($col_letter.$fila)->getFont()->setBold(true);
        $sheet->getStyle($col_letter.$fila)
            ->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A'.$fila.':'.$col_letter.$fila)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFCFE2FF');

        // Ajustar ancho de columnas
        $sheet->getColumnDimension('A')->setWidth(15);
        for ($i = 2; $i <= $num_vendedores + 2; $i++) {
            $col_letter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col_letter)->setWidth(12);
        }

        // Nombre del archivo
        $filename = 'Reporte_Vendedores_Matricial_'.date('Ymd_His').'.xlsx';

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$filename.'"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Función alternativa para exportar HTML/Excel (fallback)
     */
    private function exportWithHTML($datos)
    {
        $filename = 'Reporte_Vendedores_Matricial_'.date('Ymd_His').'.xls';

        return response(view('reportes.vendedores_matricial.export_excel', $datos))
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
