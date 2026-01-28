<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\MetasMensualImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class MetasMensualController extends Controller
{
    public function index(Request $request)
    {
        $periodo = $request->get('periodo');
        if (!$periodo) {
            $periodo = DB::table('metas_mensual')->max('periodo');
        }
        $rows = DB::table('metas_mensual')->where('periodo', $periodo)->get();
        $periodos = DB::table('metas_mensual')->select('periodo')->distinct()->orderBy('periodo','desc')->get();
        return view('metas_mensual.index', [
            'rows' => $rows,
            'currentPeriodo' => $periodo,
            'periodos' => $periodos,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new MetasMensualImport, $request->file('excel'));
            return back()->with('success', 'Datos importados correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar: '.$e->getMessage());
        }
    }

    // Create a new meta (without using an id PK)
    public function store(Request $request)
    {
        $request->validate([
            'plaza' => 'required|string',
            'tienda' => 'required|string',
            'periodo' => 'required|string',
            'meta' => 'required|numeric',
        ]);

        $plaza = (string) $request->input('plaza');
        $tienda = (string) $request->input('tienda');
        $periodo = (string) $request->input('periodo');
        $meta = (float) $request->input('meta');

        $exists = DB::table('metas_mensual')
            ->where('plaza', $plaza)
            ->where('tienda', $tienda)
            ->where('periodo', $periodo)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ya existe un registro para esta combinación (plaza, tienda, periodo).');
        }

        DB::table('metas_mensual')->insert([
            'plaza' => $plaza,
            'tienda' => $tienda,
            'periodo' => $periodo,
            'meta' => $meta,
        ]);

        return back()->with('success', 'Registro creado correctamente.');
    }

    // Update an existing meta (composite key: plaza/tienda/periodo)
    public function update(Request $request)
    {
        $request->validate([
            'old_plaza' => 'required|string',
            'old_tienda' => 'required|string',
            'old_periodo' => 'required|string',
            'plaza' => 'required|string',
            'tienda' => 'required|string',
            'periodo' => 'required|string',
            'meta' => 'required|numeric',
        ]);

        DB::table('metas_mensual')
            ->where('plaza', $request->input('old_plaza'))
            ->where('tienda', $request->input('old_tienda'))
            ->where('periodo', $request->input('old_periodo'))
            ->update([
                'plaza' => $request->input('plaza'),
                'tienda' => $request->input('tienda'),
                'periodo' => $request->input('periodo'),
                'meta' => $request->input('meta'),
            ]);

        return back()->with('success', 'Registro actualizado correctamente.');
    }

    // Delete a meta row
    public function destroy(Request $request)
    {
        $request->validate([
            'plaza' => 'required|string',
            'tienda' => 'required|string',
            'periodo' => 'required|string',
        ]);

        DB::table('metas_mensual')
            ->where('plaza', $request->input('plaza'))
            ->where('tienda', $request->input('tienda'))
            ->where('periodo', $request->input('periodo'))
            ->delete();

        return back()->with('success', 'Registro eliminado.');
    }
}
