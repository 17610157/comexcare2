<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteCarteraAbonosController extends Controller
{
    public function index()
    {
        // Cargar la vista localizada en resources/views/reportes/cartera_abonos/cartera_abonos.blade.php
        return view('reportes.cartera_abonos.cartera_abonos');
    }

    public function data(Request $request)
    {
        // Placeholder simple response para evitar errores si se llama sin lógica aún
        return response()->json(['data' => []]);
    }
}
