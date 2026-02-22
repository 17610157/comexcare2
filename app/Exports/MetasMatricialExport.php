<?php

namespace App\Exports;

use App\Services\ReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MetasMatricialExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    protected $filtros;

    public function __construct($filtros)
    {
        $this->filtros = $filtros;
    }

    public function collection()
    {
        $datos = ReportService::getMetasMatricialReport($this->filtros);

        $rows = [];

        // Fila 1: Plazas
        $row = ['Categoría'];
        foreach ($datos['tiendas'] as $tienda) {
            $row[] = $datos['matriz']['info'][$tienda]['plaza'];
        }
        $row[] = '-';
        $rows[] = $row;

        // Fila 2: Zonas
        $row = ['Zona'];
        foreach ($datos['tiendas'] as $tienda) {
            $row[] = $datos['matriz']['info'][$tienda]['zona'];
        }
        $row[] = '-';
        $rows[] = $row;

        // Filas de totales por día
        foreach ($datos['fechas'] as $fecha) {
            $row = ["Total $fecha"];
            $suma = 0;
            foreach ($datos['tiendas'] as $tienda) {
                $total = $datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0;
                $row[] = $total;
                $suma += $total;
            }
            $row[] = $suma;
            $rows[] = $row;
        }

        // Suma de los días consultados
        $row = ['Suma de los Días Consultados'];
        $suma = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $total = $datos['matriz']['totales'][$tienda]['total'] ?? 0;
            $row[] = $total;
            $suma += $total;
        }
        $row[] = $suma;
        $rows[] = $row;

        // Objetivo
        $row = ['Objetivo'];
        $suma = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $meta_total = $datos['matriz']['info'][$tienda]['meta_total'] ?? 0;
            if ($meta_total > 0) {
                $objetivo = $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
                $row[] = $objetivo;
                $suma += $objetivo;
            } else {
                $row[] = '-';
            }
        }
        $row[] = $suma;
        $rows[] = $row;

        // Suma Valor Día
        $row = ['Suma Valor Día'];
        $suma = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $valor_dia = $datos['matriz']['info'][$tienda]['suma_valor_dia'] ?? 0;
            $row[] = $valor_dia;
            $suma += $valor_dia;
        }
        $row[] = $suma;
        $rows[] = $row;

        // Días Totales
        $row = ['Días Totales'];
        foreach ($datos['tiendas'] as $tienda) {
            $row[] = $datos['matriz']['info'][$tienda]['dias_totales'] ?? $datos['dias_totales'];
        }
        $row[] = '-';
        $rows[] = $row;

        // Porcentaje Total
        $row = ['Porcentaje Total'];
        // Calcular porcentaje global
        $suma_totales_global = 0;
        $suma_objetivos_global = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $meta_total = $datos['matriz']['info'][$tienda]['meta_total'] ?? 0;
            $suma_totales_global += $datos['matriz']['totales'][$tienda]['total'] ?? 0;
            if ($meta_total > 0) {
                $porcentaje = $datos['matriz']['totales'][$tienda]['porcentaje_total'] ?? 0;
                $row[] = $porcentaje;
                $suma_objetivos_global += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
            } else {
                $row[] = '-';
            }
        }
        $porcentaje_global = $suma_objetivos_global > 0 ? ($suma_totales_global / $suma_objetivos_global) * 100 : 0;
        $row[] = $porcentaje_global;
        $rows[] = $row;

        // Meta total
        $row = ['Meta Total'];
        $suma = 0;
        foreach ($datos['tiendas'] as $tienda) {
            $meta = $datos['matriz']['info'][$tienda]['meta_total'] ?? 0;
            $row[] = $meta;
            $suma += $meta;
        }
        $row[] = $suma;
        $rows[] = $row;



        return collect($rows);
    }

    public function headings(): array
    {
        return ['Categoría / Fecha'];
    }

    public function title(): string
    {
        return 'Metas Matricial';
    }
}