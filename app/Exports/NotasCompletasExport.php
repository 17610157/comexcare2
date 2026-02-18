<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotasCompletasExport implements FromQuery, WithHeadings
{
    protected $start;
    protected $end;
    protected $plaza;
    protected $tienda;
    protected $vendedor;

    public function __construct($start, $end, $plaza = '', $tienda = '', $vendedor = '')
    {
        $this->start = $start;
        $this->end = $end;
        $this->plaza = $plaza;
        $this->tienda = $tienda;
        $this->vendedor = $vendedor;
    }

    public function query()
    {
        $query = DB::table('notas_completas_cache')
            ->whereBetween('fecha_vta', [$this->start, $this->end]);

        if (!empty($this->plaza)) {
            $query->where('plaza_ajustada', trim($this->plaza));
        }

        if (!empty($this->tienda)) {
            $query->where('ctienda', trim($this->tienda));
        }

        if (!empty($this->vendedor)) {
            $query->where('vend_clave', trim($this->vendedor));
        }

        return $query->orderBy('fecha_vta')->orderBy('ctienda')->orderBy('num_referencia');
    }

    public function headings(): array
    {
        return [
            'Plaza',
            'Tienda',
            'Num Referencia',
            'Vendedor',
            'Factura',
            'Nota Club',
            'Club TR',
            'Club ID',
            'Fecha Vta',
            'Producto',
            'Descripcion',
            'Piezas',
            'Descuento',
            'Precio Venta',
            'Costo',
            'Total con IVA',
            'Total sin IVA'
        ];
    }
}
