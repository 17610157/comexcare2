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

class VendedoresExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    protected $filtros;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $f_inicio = str_replace('-', '', $this->filtros['fecha_inicio']);
        $f_fin = str_replace('-', '', $this->filtros['fecha_fin']);
        $plaza = $this->filtros['plaza'];
        $tienda = $this->filtros['tienda'];
        $vendedor = $this->filtros['vendedor'];

        $sql = "
        SELECT 
            c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
            c.vend_clave || '-' || EXTRACT(DAY FROM c.nota_fecha::date) AS vendedor_dia,
            CASE
                WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA'
                WHEN c.vend_clave = '14379' THEN 'MANZA'
                ELSE c.cplaza
            END AS plaza_ajustada,
            c.ctienda, 
            c.vend_clave, 
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

        $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave
                  ORDER BY c.ctienda || '-' || c.vend_clave, 
                           c.vend_clave || '-' || TO_CHAR(TO_DATE(c.nota_fecha::text, 'YYYYMMDD'), 'DD')";

        $resultados = DB::select($sql, $params);
        
        $datos = [];
        $contador = 0;
        
        foreach ($resultados as $row) {
            $contador++;
            
            $fecha_int = $row->nota_fecha;
            if (strlen($fecha_int) == 8) {
                $fecha = substr($fecha_int, 0, 4) . '-' . substr($fecha_int, 4, 2) . '-' . substr($fecha_int, 6, 2);
            } else {
                $fecha = $fecha_int;
            }
            
            $venta_total = floatval($row->venta_total);
            $devolucion = floatval($row->devolucion);
            $venta_neta = $venta_total - $devolucion;
            
            $vendedor_dia = $row->vendedor_dia;
            if (strpos($vendedor_dia, '-') !== false && strlen($fecha_int) == 8) {
                $partes = explode('-', $vendedor_dia);
                if (count($partes) == 2 && (strlen($partes[1]) == 0 || $partes[1] == '0' || $partes[1] == '1')) {
                    $dia = substr($fecha_int, 6, 2);
                    $vendedor_dia = $partes[0] . '-' . $dia;
                }
            }
            
            $datos[] = [
                'No.' => $contador,
                'Tienda-Vendedor' => $row->tienda_vendedor,
                'Vendedor-Día' => $vendedor_dia,
                'Plaza Ajustada' => $row->plaza_ajustada,
                'Tienda' => $row->ctienda,
                'Vendedor' => $row->vend_clave,
                'Fecha' => $fecha,
                'Venta Total' => $venta_total,
                'Devolución' => $devolucion,
                'Venta Neta' => $venta_neta
            ];
        }
        
        return collect($datos);
    }

    public function headings(): array
    {
        return [
            'No.',
            'Tienda-Vendedor',
            'Vendedor-Día',
            'Plaza Ajustada',
            'Tienda',
            'Vendedor',
            'Fecha',
            'Venta Total',
            'Devolución',
            'Venta Neta'
        ];
    }

    public function title(): string
    {
        return 'Reporte Vendedores';
    }

    public function columnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER_00,
            'I' => NumberFormat::FORMAT_NUMBER_00,
            'J' => NumberFormat::FORMAT_NUMBER_00,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Estilo para encabezados
                $event->sheet->getStyle('A1:J1')->applyFromArray([
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
                $event->sheet->getStyle('A1:J' . ($event->sheet->getHighestRow()))
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }
}