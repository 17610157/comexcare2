<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetasPublishController extends Controller
{
    public function publishMetas(Request $request)
    {
        $periodo = $request->input('periodo');
        if (!$periodo) {
            return response()->json(['error' => 'Periodo no definido'], 400);
        }
        // Evita duplicados
        $exists = DB::table('metas')->where('periodo', $periodo)->exists();
        if ($exists) {
            return response()->json(['error' => 'Ya existen metas para este periodo'], 400);
        }
        try {
            DB::statement("INSERT INTO metas (plaza,tienda,fecha,meta,dias_mes,valor_dia,computed) SELECT m.plaza, m.tienda, f.fecha, m.meta, f.dias_mes, f.valor_dia, (m.meta/f.dias_mes)*f.valor_dia FROM metas_mensual m JOIN metas_dias f ON (m.periodo=f.periodo) WHERE f.periodo = ?", [$periodo]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        $count = DB::table('metas')->where('periodo', $periodo)->count();
        return response()->json(['message' => 'Metas publicadas para periodo '.$periodo, 'count' => $count]);
    }
}
