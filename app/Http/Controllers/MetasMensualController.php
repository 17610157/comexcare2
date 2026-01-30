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
        // Build period list and fetch metas for current period
        $periodos = DB::table('metas_mensual')->select('periodo')->distinct()->orderBy('periodo','desc')->get();
        // Determine current period from request, otherwise latest period in table
        $currentPeriodo = $request->get('periodo');
        if (!$currentPeriodo) {
            $currentPeriodo = DB::table('metas_mensual')->orderBy('periodo','desc')->value('periodo');
        }
        // Get rows for the current period
        $rows = DB::table('metas_mensual')->where('periodo', $currentPeriodo)->get();
        // Get days for the current period from metas_dias (if any)
        $dias = DB::table('metas_dias')->where('periodo', $currentPeriodo)->orderBy('fecha')->get();
        return view('metas_mensual.index', [
            'rows' => $rows,
            'dias' => $dias,
            'periodos' => $periodos,
            'currentPeriodo' => $currentPeriodo,
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

    // Generate metas_dias for a given period and insert derived data into metas (and metas_dias)
    public function generateDias(Request $request)
    {
        $periodo = $request->input('periodo');
        if (!$periodo) {
            return response()->json(['error' => 'Periodo no definido'], 400);
        }

        // Check if data already exists for this periodo in metas_dias
        $exists = DB::table('metas_dias')->where('periodo', $periodo)->exists();
        if ($exists) {
            return response()->json(['error' => 'Ya existen datos para este periodo'], 400);
        }

        // Parse year and month
        $year = intval(substr($periodo, 0, 4));
        $month = intval(substr($periodo, 5, 2));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $firstDate = "$year-$month-01";
        $weekFirst = intval(date('W', strtotime($firstDate)));

        // First pass: compute total value for the month
        $totalValue = 0.0;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow = intval(date('N', strtotime("$year-$month-$d"))); // 1=Mon .. 7=Sun
            $val = ($dow >= 1 && $dow <= 5) ? 1.0 : (($dow == 6) ? 0.5 : 0.0);
            $totalValue += $val;
        }

        // Second pass: insert metas_dias per day (Enfoque A: semanas basadas en domingo) - calcular semana correctamente
        $firstOfMonth = \DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-01', $year, $month));
        $firstDow = intval($firstOfMonth->format('N')); // 1=Mon, 7=Sun
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateObj = \DateTime::createFromFormat('Y-m-d', sprintf('%04d-%02d-%02d', $year, $month, $d));
            $diaSem = intval($dateObj->format('N'));
            $valorDia = ($diaSem >= 1 && $diaSem <= 5) ? 1.0 : (($diaSem == 6) ? 0.5 : 0.0);
            // Compute week by Enfoque A: start of first week depends on first day
            if ($firstDow == 7) {
                $weekOfMonth = ($d == 1) ? 1 : intdiv($d - 2, 7) + 2;
            } else {
                $offset = 7 - $firstDow + 1;
                $weekOfMonth = intdiv($d - 1 + $offset, 7) + 1;
            }
            DB::table('metas_dias')->insert([
                'fecha' => $dateObj->format('Y-m-d'),
                'periodo' => $periodo,
                'dia_semana' => $diaSem,
                'dias_mes' => 0, // will be set after totalValue is known
                'valor_dia' => $valorDia,
                'anio' => $year,
                'mes_friedman' => $month,
                'semana_friedman' => $weekOfMonth,
            ]);
        }
        // Set dias_mes for all inserted days to the final total for the month
        DB::table('metas_dias')->where('periodo', $periodo)->update(['dias_mes' => $totalValue]);

        // Final insertion into metas (if metas table exists) based on the given query logic
        // Ensure the metas table exists; if not, return a success for days creation only
        try {
            DB::statement("INSERT INTO metas (plaza,tienda,fecha,meta,dias_mes,valor_dia,computed) SELECT m.plaza, m.tienda, f.fecha, m.meta, f.dias_mes, f.valor_dia, (m.meta/f.dias_mes)*f.valor_dia FROM metas_mensual m JOIN metas_dias f ON (m.periodo=f.periodo) WHERE f.periodo = ?", [$periodo]);
        } catch (\Exception $e) {
            // If metas table doesn't exist or query fails, ignore and just return success of dias generation
            // Log error if needed
        }

        // Fetch data to return for UI rendering (dias y metas para ese periodo)
        $diasData = DB::table('metas_dias')->where('periodo', $periodo)->orderBy('fecha')->get();
        $metasData = null;
        try {
            $metasData = DB::table('metas')->where('periodo', $periodo)->get();
        } catch (\Exception $e) {
            $metasData = null;
        }
        // Summary data
        $daysWorkable = DB::table('metas_dias')->where('periodo', $periodo)->where('valor_dia','>',0)->count();
        $totals = DB::table('metas_mensual')
            ->select('plaza','tienda', DB::raw('SUM(meta) as total_meta'))
            ->where('periodo', $periodo)
            ->groupBy('plaza','tienda')
            ->get();
        $totalMetaPeriod = DB::table('metas_mensual')->where('periodo', $periodo)->sum('meta');
        $totalDiasPeriod = DB::table('metas_dias')->where('periodo', $periodo)->sum('dias_mes');
        $avgMetaPerDay = $totalDiasPeriod > 0 ? $totalMetaPeriod / $totalDiasPeriod : 0;
        return response()->json([
            'message' => 'Dias generados para periodo '.$periodo,
            'dias' => $diasData,
            'metas' => $metasData,
            'summary' => [
                'days_workable' => $daysWorkable,
                'totals' => $totals,
                'total_meta' => $totalMetaPeriod,
                'total_days' => $totalDiasPeriod,
                'avg_meta_per_day' => $avgMetaPerDay,
            ],
        ]);
    }
}
