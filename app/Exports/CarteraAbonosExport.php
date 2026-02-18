<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class CarteraAbonosExport implements FromQuery, WithHeadings
{
    protected $start;
    protected $end;
    protected $plaza;
    protected $tienda;

    public function __construct($start, $end, $plaza = '', $tienda = '')
    {
        $this->start = $start;
        $this->end = $end;
        $this->plaza = $plaza;
        $this->tienda = $tienda;
    }

    public function query()
    {
        $query = DB::table('cartera_abonos_cache')
            ->whereBetween('fecha', [$this->start, $this->end]);

        if (!empty($this->plaza)) {
            $query->where('plaza', trim($this->plaza));
        }

        if (!empty($this->tienda)) {
            $query->where('tienda', trim($this->tienda));
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
            'Días Vencidos'
        ];
    }
}
