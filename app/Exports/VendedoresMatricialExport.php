<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class VendedoresMatricialExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    protected $filtros;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        // Obtener datos usando la misma lógica que el controlador
        $f_inicio = str_replace('-', '', $this->filtros['fecha_inicio']);
        $f_fin = str_replace('-', '', $this->filtros['fecha_fin']);
        $plaza = $this->filtros['plaza'] ?? '';
        $tienda = $this->filtros['tienda'] ?? '';
        $vendedor = $this->filtros['vendedor'] ?? '';

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
          AND c.nota_fecha BETWEEN ? AND ?
          AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
          AND c.ctienda NOT LIKE '%DESC%' 
          AND c.ctienda NOT LIKE '%CEDI%' ";

        $params = [$f_inicio, $f_fin];
        
        if (!empty($plaza)) {
            $sql .= " AND c.cplaza = ?";
            $params[] = $plaza;
        }
        
        if (!empty($tienda)) {
            $sql .= " AND c.ctienda = ?";
            $params[] = $tienda;
        }
        
        if (!empty($vendedor)) {
            $sql .= " AND c.vend_clave = ?";
            $params[] = $vendedor;
        }
        
        $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave, a.nombre, a.tipo
                  ORDER BY c.vend_clave, c.nota_fecha";

        $resultados = DB::select($sql, $params);
        
        // Procesar datos en estructura matricial
        $datos_matriz = [];
        $contador = 0;
        
        // Organizar por vendedor y fecha
        $vendedores = [];
        $fechas = [];
        
        foreach ($resultados as $row) {
            $vendedor_id = $row->vend_clave;
            $fecha_key = $row->nota_fecha;
            
            if (strlen($fecha_key) == 8) {
                $fecha_formatted = substr($fecha_key, 0, 4) . '-' . substr($fecha_key, 4, 2) . '-' . substr($fecha_key, 6, 2);
            } else {
                $fecha_formatted = $fecha_key;
            }
            
            if (!in_array($fecha_formatted, $fechas)) {
                $fechas[] = $fecha_formatted;
            }
            
            if (!isset($vendedores[$vendedor_id])) {
                $vendedores[$vendedor_id] = [
                    'nombre' => $row->nombre,
                    'tipo' => $row->tipo,
                    'tienda' => $row->ctienda,
                    'plaza' => $row->cplaza,
                    'ventas' => []
                ];
            }
            
            $venta_total = floatval($row->venta_total);
            $devolucion = floatval($row->devolucion);
            $venta_neta = $venta_total - $devolucion;
            
            $vendedores[$vendedor_id]['ventas'][$fecha_formatted] = $venta_neta;
        }
        
        sort($fechas);
        
        // Crear matriz de datos
        foreach ($fechas as $fecha) {
            $contador++;
            $fila = [
                'No.' => $contador,
                'Fecha' => $fecha,
                'Descripción' => 'VENTA DEL DÍA'
            ];
            
            foreach ($vendedores as $vendedor_id => $info) {
                $fila[$vendedor_id] = $info['ventas'][$fecha] ?? 0;
            }
            
            $datos_matriz[] = $fila;
        }
        
        // Agregar fila de totales
        $fila_totales = [
            'No.' => '',
            'Fecha' => '',
            'Descripción' => 'TOTAL VENDEDOR'
        ];
        
        foreach ($vendedores as $vendedor_id => $info) {
            $fila_totales[$vendedor_id] = array_sum($info['ventas']);
        }
        
        $datos_matriz[] = $fila_totales;
        
        return collect($datos_matriz);
    }

    public function headings(): array
    {
        // Obtener vendedores para encabezados dinámicos
        $f_inicio = str_replace('-', '', $this->filtros['fecha_inicio']);
        $f_fin = str_replace('-', '', $this->filtros['fecha_fin']);
        $plaza = $this->filtros['plaza'] ?? '';
        $tienda = $this->filtros['tienda'] ?? '';
        $vendedor = $this->filtros['vendedor'] ?? '';

        $sql = "
        SELECT DISTINCT c.vend_clave
        FROM canota c 
        JOIN asesores_vvt a ON (a.plaza = c.cplaza AND a.asesor = c.vend_clave)
        WHERE c.ban_status <> 'C' 
          AND c.nota_fecha BETWEEN ? AND ?
          AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
          AND c.ctienda NOT LIKE '%DESC%' 
          AND c.ctienda NOT LIKE '%CEDI%' ";

        $params = [$f_inicio, $f_fin];
        
        if (!empty($plaza)) {
            $sql .= " AND c.cplaza = ?";
            $params[] = $plaza;
        }
        
        if (!empty($tienda)) {
            $sql .= " AND c.ctienda = ?";
            $params[] = $tienda;
        }
        
        if (!empty($vendedor)) {
            $sql .= " AND c.vend_clave = ?";
            $params[] = $vendedor;
        }
        
        $sql .= " ORDER BY c.vend_clave";

        $vendedores = DB::select($sql, $params);
        
        $headings = ['No.', 'Fecha', 'Descripción'];
        
        foreach ($vendedores as $row) {
            $headings[] = $row->vend_clave;
        }
        
        return $headings;
    }

    public function title(): string
    {
        return 'Reporte Vendedores Matricial';
    }

    public function columnFormats(): array
    {
        // Obtener número de columnas dinámicamente
        $num_vendedores = count($this->headings()) - 3;
        $formats = [];
        
        // Las primeras 3 columnas son texto
        for ($i = 0; $i < 3; $i++) {
            $col = chr(65 + $i);
            $formats[$col] = NumberFormat::FORMAT_TEXT;
        }
        
        // Las siguientes columnas son números
        for ($i = 3; $i < 3 + $num_vendedores; $i++) {
            $col = chr(65 + $i);
            $formats[$col] = NumberFormat::FORMAT_NUMBER_00;
        }
        
        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Estilo para encabezados
                $event->sheet->getStyle('A1:' . $event->sheet->getHighestColumn() . '1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ]
                ]);

                // Agregar bordes
                $event->sheet->getStyle('A1:' . $event->sheet->getHighestColumn() . $event->sheet->getHighestRow())
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
                    
                // Estilo para fila de totales
                $lastRow = $event->sheet->getHighestRow();
                $event->sheet->getStyle('A' . $lastRow . ':' . $event->sheet->getHighestColumn() . $lastRow)
                    ->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'C6E0B4']
                        ]
                    ]);
            },
        ];
    }
}