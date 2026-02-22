<?php
namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListasController extends Controller
{
    // Plazas distintas
    public function plazas()
    {
        $list = DB::table('cobranza')->distinct()->pluck('cplaza');
        return response()->json($list);
    }

    // Tiendas disponibles para una Plaza dada
    public function tiendas(Request $request)
    {
        $plaza = $request->input('plaza');
        $list = DB::table('cobranza')
            ->where('cplaza', $plaza)
            ->distinct()
            ->pluck('ctienda');
        return response()->json($list);
    }
}
