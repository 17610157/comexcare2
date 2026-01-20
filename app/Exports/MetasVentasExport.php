<?php

namespace App\Exports;

use App\Models\ReporteMetasVentas;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MetasVentasExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
        $contador = 0;
        
        foreach ($this->resultados as $item) {
            $contador++;
            
            $porcentaje = floatval($item->porcentaje);
            $porcentaje_acumulado = floatval($item->porcentaje_acumulado);
            
            $data[] = [
                $contador,
                $item->id_plaza ?? '',
                $item->clave_tienda ?? '',
                $item->sucursal ?? '',
                \Carbon\Carbon::parse($item->fecha)->format('d/m/Y'),
                $item->zona ?? '',
                number_format($item->meta_total ?? 0, 2),
                $item->dias_total ?? 0,
                number_format($item->valor_dia ?? 0, 2),
                number_format($item->meta_dia ?? 0, 2),
                number_format($item->venta_del_dia ?? 0, 2),
                number_format($item->venta_acumulada ?? 0, 2),
                number_format($porcentaje, 2) . '%',
                number_format($porcentaje_acumulado, 2) . '%',
            ];
        }
        
        // Agregar fila de totales CON CÁLCULO CORRECTO
        if (count($data) > 0) {
            $data[] = [
                'TOTALES',
                '',
                '',
                '',
                '',
                '',
                number_format($this->estadisticas['total_meta_total'], 2),
                round($this->estadisticas['total_registros'] > 0 ? 
                    array_sum(array_column($this->resultados, 'dias_total')) / $this->estadisticas['total_registros'] : 0, 2),
                number_format($this->estadisticas['total_registros'] > 0 ? 
                    array_sum(array_column($this->resultados, 'valor_dia')) / $this->estadisticas['total_registros'] : 0, 2),
                number_format($this->estadisticas['total_meta_dia'], 2),
                number_format($this->estadisticas['total_venta_dia'], 2),
                number_format($this->estadisticas['total_venta_acumulada'], 2),
                number_format($this->estadisticas['porcentaje_promedio'], 2) . '%',
                // ¡IMPORTANTE! Aquí calculamos el % acumulado TOTAL de la tabla
                number_format($this->estadisticas['porcentaje_acumulado_global'], 2) . '%',
            ];
        }
        
        return $data;
    }
    
    public function headings(): array
    {
        return [
            '#',
            'Plaza',
            'Tienda',
            'Sucursal',
            'Fecha',
            'Zona',
            'Meta Total',
            'Días Total',
            'Valor Día',
            'Meta Día',
            'Venta del Día',
            'Venta Acumulada',
            '% Cumplimiento',
            '% Acumulado',
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->resultados) + 2; // +1 para encabezado, +1 para totales
        
        $styles = [
            // Estilo para el encabezado
            1 => [
                'font' => [
                    'bold' => true, 
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '343A40']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            
            // Estilo para columnas numéricas
            'G:N' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ],
            
            // Estilo para la fila de totales
            $lastRow => [
                'font' => [
                    'bold' => true,
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
        
        // Aplicar colores de fondo y condicionales
        for ($row = 2; $row <= $lastRow - 1; $row++) {
            // Meta Día (columna J) - bg-info
            $sheet->getStyle("J{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('E6F3FF');
            
            // Venta del Día (columna K) - bg-warning
            $sheet->getStyle("K{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF3CD');
            
            // Venta Acumulada (columna L) - bg-success
            $sheet->getStyle("L{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('D4EDDA');
            
            // % Cumplimiento (columna M) - colores condicionales
            $percentageCell = $sheet->getCell("M{$row}")->getValue();
            $percentage = floatval(str_replace(['%', ','], '', $percentageCell));
            
            if ($percentage >= 100) {
                $sheet->getStyle("M{$row}")->getFont()->getColor()->setARGB('28A745');
            } elseif ($percentage >= 80) {
                $sheet->getStyle("M{$row}")->getFont()->getColor()->setARGB('FFC107');
            } else {
                $sheet->getStyle("M{$row}")->getFont()->getColor()->setARGB('DC3545');
            }
            
            // % Acumulado (columna N) - bg-secondary y colores condicionales
            $sheet->getStyle("N{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('E2E3E5');
            
            $percentageAcumCell = $sheet->getCell("N{$row}")->getValue();
            $percentageAcum = floatval(str_replace(['%', ','], '', $percentageAcumCell));
            
            if ($percentageAcum >= 100) {
                $sheet->getStyle("N{$row}")->getFont()->getColor()->setARGB('28A745');
            } elseif ($percentageAcum >= 80) {
                $sheet->getStyle("N{$row}")->getFont()->getColor()->setARGB('FFC107');
            } else {
                $sheet->getStyle("N{$row}")->getFont()->getColor()->setARGB('DC3545');
            }
        }
        
        // Aplicar bordes a todas las celdas
        $sheet->getStyle("A1:N{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Ajustar altura de filas
        $sheet->getRowDimension(1)->setRowHeight(25);
        
        return $styles;
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 8,    // #
            'B' => 12,   // Plaza
            'C' => 12,   // Tienda
            'D' => 20,   // Sucursal
            'E' => 12,   // Fecha
            'F' => 12,   // Zona
            'G' => 12,   // Meta Total
            'H' => 12,   // Días Total
            'I' => 12,   // Valor Día
            'J' => 12,   // Meta Día
            'K' => 15,   // Venta del Día
            'L' => 15,   // Venta Acumulada
            'M' => 15,   // % Cumplimiento
            'N' => 15,   // % Acumulado
        ];
    }
}