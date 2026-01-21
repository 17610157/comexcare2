<?php

namespace App\Exports;

use App\Services\ReportService;
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
        $resultados_collection = ReportService::getVendedoresReport($this->filtros);

        return $resultados_collection->map(function ($item, $index) {
            return [
                'No.' => $index + 1,
                'Tienda-Vendedor' => $item['tienda_vendedor'],
                'Vendedor-Día' => $item['vendedor_dia'],
                'Plaza Ajustada' => $item['plaza_ajustada'],
                'Tienda' => $item['ctienda'],
                'Vendedor' => $item['vend_clave'],
                'Fecha' => $item['fecha'],
                'Venta Total' => $item['venta_total'],
                'Devolución' => $item['devolucion'],
                'Venta Neta' => $item['venta_neta']
            ];
        });
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