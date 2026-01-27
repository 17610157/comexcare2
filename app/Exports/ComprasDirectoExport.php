<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ComprasDirectoExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents, WithColumnFormatting
{
    protected $filtros;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $fecha_inicio = str_replace('-', '', $this->filtros['fecha_inicio']);
        $fecha_fin = str_replace('-', '', $this->filtros['fecha_fin']);
        $plaza = $this->filtros['plaza'] ?? '';
        $tienda = $this->filtros['tienda'] ?? '';
        $proveedor = $this->filtros['proveedor'] ?? '';

        $sql = "
            SELECT
                c.cplaza,
                c.ctienda,
                c.tipo_doc,
                c.no_referen,
                c.tipo_doc_a,
                c.no_fact_pr,
                c.clave_pro,
                por.nombre,
                c.cuenta,
                c.f_emision,
                ''''||p.clave_art AS clave_art,
                pr.descripcio,
                p.cantidad,
                p.precio_uni,
                pr.k_agrupa,
                pr.k_familia,
                pr.k_subfam,
                p.cantidad * p.precio_uni as total
            FROM compras c
            JOIN partcomp p ON c.ctienda=p.ctienda AND c.cplaza=p.cplaza AND c.tipo_doc=p.tipo_doc AND c.no_referen=p.no_referen
            JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda=por.ctienda AND c.cplaza=por.cplaza
            JOIN grupos pr ON p.clave_art=pr.clave 
            WHERE c.f_emision BETWEEN ? AND ?
        ";

        $params = [$fecha_inicio, $fecha_fin];

        if (!empty($plaza)) {
            $sql .= " AND c.cplaza = ?";
            $params[] = $plaza;
        }
        if (!empty($tienda)) {
            $sql .= " AND c.ctienda = ?";
            $params[] = $tienda;
        }
        if (!empty($proveedor)) {
            $sql .= " AND c.clave_pro = ?";
            $params[] = $proveedor;
        }

        $sql .= " ORDER BY c.cplaza, c.ctienda, c.f_emision";

        $resultados = DB::select($sql, $params);

        // Convertir a array con numeración
        $collection = collect($resultados)->map(function ($item, $index) {
            return [
                'No.' => $index + 1,
                'Plaza' => $item->cplaza,
                'Tienda' => $item->ctienda,
                'Tipo Doc' => $item->tipo_doc,
                'No. Referencia' => $item->no_referen,
                'Tipo Doc A' => $item->tipo_doc_a,
                'No. Factura' => $item->no_fact_pr,
                'Clave Proveedor' => $item->clave_pro,
                'Nombre Proveedor' => $item->nombre,
                'Cuenta' => $item->cuenta,
                'Fecha Emisión' => $item->f_emision,
                'Clave Artículo' => $item->clave_art,
                'Descripción' => $item->descripcio,
                'Cantidad' => floatval($item->cantidad),
                'Precio Unitario' => floatval($item->precio_uni),
                'K Agrupa' => $item->k_agrupa,
                'K Familia' => $item->k_familia,
                'K Subfam' => $item->k_subfam,
                'Total' => floatval($item->total)
            ];
        });

        // Agregar fila de totales
        $totalCantidad = $collection->sum('Cantidad');
        $totalCompras = $collection->sum('Total');

        $collection->push([
            'No.' => '',
            'Plaza' => 'TOTALES',
            'Tienda' => '',
            'Tipo Doc' => '',
            'No. Referencia' => '',
            'Tipo Doc A' => '',
            'No. Factura' => '',
            'Clave Proveedor' => '',
            'Nombre Proveedor' => '',
            'Cuenta' => '',
            'Fecha Emisión' => '',
            'Clave Artículo' => '',
            'Descripción' => '',
            'Cantidad' => $totalCantidad,
            'Precio Unitario' => '',
            'K Agrupa' => '',
            'K Familia' => '',
            'K Subfam' => '',
            'Total' => $totalCompras
        ]);

        return $collection;
    }

    public function headings(): array
    {
        return [
            '#',
            'Plaza',
            'Tienda',
            'Tipo Doc',
            'No. Referencia',
            'Tipo Doc A',
            'No. Factura',
            'Clave Proveedor',
            'Nombre Proveedor',
            'Cuenta',
            'Fecha Emisión',
            'Clave Artículo',
            'Descripción',
            'Cantidad',
            'Precio Unitario',
            'K Agrupa',
            'K Familia',
            'K Subfam',
            'Total'
        ];
    }

    public function title(): string
    {
        return 'Reporte Compras Directo';
    }

    public function columnFormats(): array
    {
        return [
            'N' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
            'S' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Formato para encabezados
                $sheet->getStyle('A1:S1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '007bff'],
                    ],
                ]);

                // Ajustar altura de filas
                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Auto-filter
                $sheet->setAutoFilter('A1:S1');

                // Congelar filas
                $sheet->freezePane('A2');
            },
        ];
    }
    
}