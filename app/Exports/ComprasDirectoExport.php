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
    protected $start;
    protected $end;
    protected $plaza;
    protected $tienda;
    protected $proveedor;

    public function __construct($start, $end, $plaza = '', $tienda = '', $proveedor = '')
    {
        $this->start = $start;
        $this->end = $end;
        $this->plaza = $plaza;
        $this->tienda = $tienda;
        $this->proveedor = $proveedor;
    }

    public function collection()
    {
        $query = DB::table('compras_directo_cache')
            ->whereBetween('f_emision', [$this->start, $this->end]);

        if (!empty($this->plaza)) {
            $query->where('cplaza', trim($this->plaza));
        }

        if (!empty($this->tienda)) {
            $query->where('ctienda', trim($this->tienda));
        }

        if (!empty($this->proveedor)) {
            $query->where('clave_pro', trim($this->proveedor));
        }

        $rows = $query->orderBy('f_emision')->orderBy('ctienda')->orderBy('no_referen')->get();

        $collection = $rows->map(function ($item, $index) {
            return [
                'No.' => $index + 1,
                'Plaza' => $item->cplaza,
                'Tienda' => $item->ctienda,
                'Tipo Doc' => $item->tipo_doc,
                'No. Referencia' => $item->no_referen,
                'Tipo Doc A' => $item->tipo_doc_a,
                'No. Factura' => $item->no_fact_pr,
                'Clave Proveedor' => $item->clave_pro,
                'Nombre Proveedor' => $item->nombre_proveedor,
                'Cuenta' => $item->cuenta,
                'Fecha Emisión' => $item->f_emision,
                'Clave Artículo' => $item->clave_art,
                'Descripción' => $item->descripcion,
                'Cantidad' => floatval($item->cantidad),
                'Precio Unitario' => floatval($item->precio_uni),
                'K Agrupa' => $item->k_agrupa,
                'K Familia' => $item->k_familia,
                'K Subfam' => $item->k_subfam,
                'Total' => floatval($item->total)
            ];
        });

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

                $sheet->getDefaultRowDimension()->setRowHeight(20);
                $sheet->getRowDimension(1)->setRowHeight(25);

                $sheet->setAutoFilter('A1:S1');

                $sheet->freezePane('A2');
            },
        ];
    }
}
