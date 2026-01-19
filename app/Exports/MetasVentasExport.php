<?php

namespace App\Exports;

use App\Models\AsesoresVvt;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MetasVentasExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $filtros;
    
    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }
    
    public function collection()
    {
        $resultados = AsesoresVvt::obtenerReporteMetasVentas($this->filtros);
        
        // Transformar los datos para Excel
        $data = collect();
        
        foreach ($resultados as $item) {
            $data->push([
                'Plaza' => $item->plaza ?? '',
                'Tienda' => $item->tienda ?? '',
                'Zona' => $item->zona ?? '',
                'Sucursal' => $item->sucursal ?? '',
                'Fecha' => $item->fecha ?? '',
                'Meta Total' => $item->meta_total ?? 0,
                'Días Total' => $item->dias_total ?? 0,
                'Valor Día' => $item->valor_dia ?? 0,
                'Meta Día' => $item->meta_dia ?? 0,
                'Total Vendido' => $item->total_vendido ?? 0,
                '% Cumplimiento' => round($item->porcentaje_cumplimiento ?? 0, 2) . '%',
            ]);
        }
        
        return $data;
    }
    
    public function headings(): array
    {
        return [
            'Plaza',
            'Tienda',
            'Zona',
            'Sucursal',
            'Fecha',
            'Meta Total',
            'Días Total',
            'Valor Día',
            'Meta Día',
            'Total Vendido',
            '% Cumplimiento',
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para el encabezado
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '343A40']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                ],
            ],
            
            // Estilo para columnas numéricas
            'F:K' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 15, // Plaza
            'B' => 10, // Tienda
            'C' => 15, // Zona
            'D' => 25, // Sucursal
            'E' => 12, // Fecha
            'F' => 12, // Meta Total
            'G' => 12, // Días Total
            'H' => 12, // Valor Día
            'I' => 12, // Meta Día
            'J' => 12, // Total Vendido
            'K' => 15, // % Cumplimiento
        ];
    }
}