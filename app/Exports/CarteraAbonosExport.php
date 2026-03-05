<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CarteraAbonosExport implements FromQuery, WithHeadings
{
    protected $start;

    protected $end;

    protected $plaza;

    protected $tienda;

    protected $plazasPermitidas;

    protected $tiendasPermitidas;

    public function __construct($start, $end, $plaza = '', $tienda = '', $plazasPermitidas = [], $tiendasPermitidas = [])
    {
        $this->start = $start;
        $this->end = $end;
        $this->plaza = $plaza;
        $this->tienda = $tienda;
        $this->plazasPermitidas = $plazasPermitidas;
        $this->tiendasPermitidas = $tiendasPermitidas;
    }

    public function query()
    {
        $query = DB::table('cartera_abonos_cache')
            ->whereBetween('fecha', [$this->start, $this->end]);

        if (! empty($this->plazasPermitidas)) {
            $query->whereIn('plaza', $this->plazasPermitidas);
        }

        if (! empty($this->tiendasPermitidas)) {
            $query->whereIn('tienda', $this->tiendasPermitidas);
        }

        if (! empty($this->plaza)) {
            if (is_array($this->plaza)) {
                $query->whereIn('plaza', $this->plaza);
            } else {
                $query->where('plaza', trim($this->plaza));
            }
        }

        if (! empty($this->tienda)) {
            if (is_array($this->tienda)) {
                $query->whereIn('tienda', $this->tienda);
            } else {
                $query->where('tienda', trim($this->tienda));
            }
        }

        return $query->orderBy('plaza')->orderBy('tienda')->orderBy('fecha');
    }

    public function headings(): array
    {
        return [
            'Plaza',
            'Tienda',
            'Fecha',
            'Fecha Vta',
            'Concepto',
            'Tipo',
            'Factura',
            'Clave',
            'RFC',
            'Nombre',
            'Vendedor',
            'Monto FA',
            'Monto DV',
            'Monto CD',
            'Días Crédito',
            'Días Vencidos',
        ];
    }
}
