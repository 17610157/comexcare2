<?php
namespace App\Exports\Reportes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;

class CarteraAbonosExport implements FromCollection, WithHeadings
{
    protected $start;
    protected $end;

    public function __construct(string $start, string $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function collection()
    {
        $sql = "".
            " SELECT ".
            "   c.cplaza AS Plaza, ".
            "   c.ctienda AS Tienda, ".
            "   c.fecha AS Fecha, ".
            "   c2.dfechafac AS Fecha_vta, ".
            "   c.concepto AS Concepto, ".
            "   c.tipo_ref AS Tipo, ".
            "   c.no_ref AS Factura, ".
            "   cl.clie_clave AS Clave, ".
            "   cl.clie_rfc AS RFC, ".
            "   cl.clie_nombr AS Nombre, ".
            "   CASE WHEN c.tipo_ref = 'FA' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_fa, ".
            "   CASE WHEN c.tipo_ref = 'FA' AND c.concepto = 'DV' THEN c.IMPORTE ELSE 0 END AS monto_dv, ".
            "   CASE WHEN c.tipo_ref = 'CD' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_cd, ".
            "   COALESCE(cl.clie_credi, 0) AS Dias_Cred, ".
            "   (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS Dias_Vencidos ".
            " FROM cobranza c ".
            " LEFT JOIN ( ".
            "   SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl ".
            "   FROM cobranza co WHERE co.cargo_ab = 'C' ".
            " ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl) ".
            " LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda ".
            " LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave) ".
            " WHERE c.cargo_ab='A' AND c.estado='S' AND c.cborrado<>'1' AND c.fecha >= :start AND c.fecha <= :end".
            " ORDER BY Plaza, Tienda, Fecha";

        $rows = DB::select($sql, ['start' => $this->start, 'end' => $this->end]);
        // Convert to collection of arrays
        return collect(array_map(function($row){
            return (array)$row;
        }, $rows));
    }

    public function headings(): array
    {
        return [
            'Plaza','Tienda','Fecha','Fecha_vta','Concepto','Tipo','Factura','Clave','RFC','Nombre','monto_fa','monto_dv','monto_cd','Dias_Cred','Dias_Vencidos'
        ];
    }
}
