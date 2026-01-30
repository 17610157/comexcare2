<?php
namespace App\Models\Reportes;

use Illuminate\Database\Eloquent\Model;

class CarteraAbonos extends Model
{
    protected $table = 'cartera_clie_abonos';
    protected $primaryKey = 'Factura';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = [
        'Plaza','Tienda','Fecha','Fecha_vta','Concepto','Tipo','Factura','Clave','RFC','Nombre',
        'monto_fa','monto_dv','monto_cd','Dias_Cred','Dias_Vencidos'
    ];
}
