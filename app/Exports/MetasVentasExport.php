<?php

namespace App\Exports;

use App\Models\ReporteMetasVentas;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MetasVentasExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    protected $filtros;

    protected $resultados;

    protected $estadisticas;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
        $this->resultados = ReporteMetasVentas::obtenerReporte($filtros);
        $this->estadisticas = ReporteMetasVentas::obtenerEstadisticas($this->resultados);
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->resultados as $item) {
            $porcentaje = floatval($item->porcentaje);

            $data[] = [
                $item->clave_tienda ?? '',
                $item->sucursal ?? '',
                number_format($item->meta_total ?? 0, 2),
                number_format($item->dias_mes ?? 0, 1),
                number_format($item->dias_agotados ?? 0, 2),
                number_format($item->meta_parcial ?? 0, 2),
                number_format($item->venta_real ?? 0, 2),
                number_format($porcentaje, 2).'%',
            ];
        }

        if (count($data) > 0) {
            $data[] = [
                'TOTALES',
                '',
                number_format($this->estadisticas['total_meta_total'], 2),
                '',
                '',
                number_format($this->estadisticas['total_meta_parcial'], 2),
                number_format($this->estadisticas['total_venta_real'], 2),
                number_format($this->estadisticas['porcentaje_promedio'], 2).'%',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'CLAVE',
            'NOMBRE',
            'META',
            'DIAS MES',
            'DIAS AGOTADOS',
            'META PARCIAL',
            'VENTA REAL',
            'PORCENTAJE',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->resultados) + 2;

        $styles = [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '343A40'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],

            $lastRow => [
                'font' => [
                    'bold' => true,
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];

        for ($row = 2; $row <= $lastRow - 1; $row++) {
            $percentageCell = $sheet->getCell("H{$row}")->getValue();
            $percentage = floatval(str_replace(['%', ','], '', $percentageCell));

            if ($percentage >= 100) {
                $sheet->getStyle("H{$row}")->getFont()->getColor()->setARGB('28A745');
            } elseif ($percentage >= 80) {
                $sheet->getStyle("H{$row}")->getFont()->getColor()->setARGB('FFC107');
            } else {
                $sheet->getStyle("H{$row}")->getFont()->getColor()->setARGB('DC3545');
            }
        }

        $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(25);

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,
            'B' => 25,
            'C' => 15,
            'D' => 12,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 12,
        ];
    }
}
