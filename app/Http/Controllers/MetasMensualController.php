<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\MetasMensualImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetasMensualController extends Controller
{
    public function index(Request $request)
    {
        // Solo cargar datos si hay período específico (lazy loading)
        $hasPeriodoFilter = $request->has('periodo') && !empty($request->input('periodo'));
        
        // Build period list and fetch metas for current period
        $periodos = DB::table('metas_mensual')->select('periodo')->distinct()->orderBy('periodo','desc')->get();
        
        // Determine current period from request, otherwise latest period in table
        $currentPeriodo = $request->get('periodo');
        if (!$currentPeriodo) {
            $currentPeriodo = DB::table('metas_mensual')->orderBy('periodo','desc')->value('periodo');
        }
        
        // Performance logging
        $inicio_general = microtime(true);
        
        if ($hasPeriodoFilter) {
            // Cargar datos solo si hay período específico
            $periodo_inicio = microtime(true);
            
            // Get rows for current period (Metas Mensual - Tabla 1)
            $metas_mensual = DB::table('metas_mensual')->where('periodo', $currentPeriodo)->get();
            $tiempo_mensual = round((microtime(true) - $periodo_inicio) * 1000, 2);
            
            // Get days for current period from metas_dias (Metas Diarias - Tabla 2)
            $periodo_inicio = microtime(true);
            $metas_dias = DB::table('metas_dias')->where('periodo', $currentPeriodo)->orderBy('fecha')->get();
            $tiempo_dias = round((microtime(true) - $periodo_inicio) * 1000, 2);
            
            // Get details from metas table for current period (Metas Detalles - Tabla 3)
            $periodo_inicio = microtime(true);
            $metas_detalles = DB::table('metas')
                ->select('plaza','tienda','fecha','meta_total','dias_total','valor_dia','meta_dia')
                ->whereRaw('DATE_TRUNC(\'month\', fecha) = DATE_TRUNC(\'month\', ?::date)', [$currentPeriodo . '-01'])
                ->orderBy('plaza')->orderBy('tienda')->orderBy('fecha')
                ->get();
            $tiempo_detalles = round((microtime(true) - $periodo_inicio) * 1000, 2);
            
            // Performance logging
            if ($tiempo_mensual > 1000) {
                Log::warning("METAS_MENSUAL_LENTA: {$tiempo_mensual}ms para período {$currentPeriodo}");
            }
            if ($tiempo_dias > 500) {
                Log::warning("METAS_DIAS_LENTA: {$tiempo_dias}ms para período {$currentPeriodo}");
            }
            if ($tiempo_detalles > 2000) {
                Log::warning("METAS_DETALLES_LENTA: {$tiempo_detalles}ms para período {$currentPeriodo}");
            }
            
        } else {
            // Sin período específico: datos vacíos (carga rápida inicial)
            $metas_mensual = collect([]);
            $metas_dias = collect([]);
            $metas_detalles = collect([]);
            $tiempo_mensual = 0;
            $tiempo_dias = 0;
            $tiempo_detalles = 0;
        }
        
        $tiempo_total = round((microtime(true) - $inicio_general) * 1000, 2);
        
        return view('metas_mensual.index', [
            'metas_mensual' => $metas_mensual,
            'metas_dias' => $metas_dias,
            'metas_detalles' => $metas_detalles,
            'periodos' => $periodos,
            'currentPeriodo' => $currentPeriodo,
            'hasPeriodoFilter' => $hasPeriodoFilter,
            // Performance metrics for view
            'performance' => [
                'tiempo_mensual' => $tiempo_mensual,
                'tiempo_dias' => $tiempo_dias,
                'tiempo_detalles' => $tiempo_detalles,
                'tiempo_total' => $tiempo_total,
            ]
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

        return back()->with('success', 'Meta creada correctamente.');
    }

    // Update existing meta
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

        $old_plaza = (string) $request->input('old_plaza');
        $old_tienda = (string) $request->input('old_tienda');
        $old_periodo = (string) $request->input('old_periodo');
        $plaza = (string) $request->input('plaza');
        $tienda = (string) $request->input('tienda');
        $periodo = (string) $request->input('periodo');
        $meta = (float) $request->input('meta');

        $affected = DB::table('metas_mensual')
            ->where('plaza', $old_plaza)
            ->where('tienda', $old_tienda)
            ->where('periodo', $old_periodo)
            ->update([
                'plaza' => $plaza,
                'tienda' => $tienda,
                'periodo' => $periodo,
                'meta' => $meta,
            ]);

        return back()->with('success', "{$affected} registros actualizados correctamente.");
    }

    // Delete existing meta
    public function destroy(Request $request)
    {
        $request->validate([
            'plaza' => 'required|string',
            'tienda' => 'required|string',
            'periodo' => 'required|string',
        ]);

        $plaza = (string) $request->input('plaza');
        $tienda = (string) $request->input('tienda');
        $periodo = (string) $request->input('periodo');

        $affected = DB::table('metas_mensual')
            ->where('plaza', $plaza)
            ->where('tienda', $tienda)
            ->where('periodo', $periodo)
            ->delete();

        return back()->with('success', "{$affected} registros eliminados correctamente.");
    }

    /**
     * Generar Metas para un período específico
     */
    public function generarMetas(Request $request)
    {
        $periodo = trim($request->input('periodo', ''));
        
        // Validar formato del período
        if (empty($periodo) || !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return response()->json([
                'success' => false,
                'message' => 'Período inválido. Use formato YYYY-MM'
            ], 400);
        }
        
        // Validar que existan datos en metas_mensual
        $countMensual = DB::table('metas_mensual')->where('periodo', $periodo)->count();
        if ($countMensual == 0) {
            return response()->json([
                'success' => false,
                'message' => "No existen datos en metas_mensual para el período {$periodo}"
            ], 400);
        }
        
        // Validar datos existentes en metas_dias
        $countDias = DB::table('metas_dias')->where('periodo', $periodo)->count();
        if ($countDias == 0) {
            return response()->json([
                'success' => false,
                'message' => "No existen datos en metas_dias para el período {$periodo}"
            ], 400);
        }
        
        try {
            // Ejecutar la query de generación de metas
            $query = "
                INSERT INTO metas (plaza, tienda, fecha, meta_total, dias_total, valor_dia, meta_dia)
                SELECT 
                    m.plaza, 
                    m.tienda, 
                    f.fecha, 
                    m.meta as meta_total,
                    f.dias_mes as dias_total, 
                    f.valor_dia, 
                    CASE 
                        WHEN f.dias_mes > 0 THEN (m.meta/f.dias_mes)*f.valor_dia 
                        ELSE 0 
                    END as meta_dia
                FROM metas_mensual m 
                JOIN metas_dias f ON (m.periodo=f.periodo) 
                WHERE f.periodo = ?
            ";
            
            DB::insert($query, [$periodo]);
            
            // Logging de la operación
            Log::info("Metas generadas exitosamente para período {$periodo}");
            
            return response()->json([
                'success' => true,
                'message' => "Metas generadas exitosamente para el período {$periodo}",
                'periodo' => $periodo
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error generando metas para período {$periodo}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar metas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar dias para un período
     */
    public function generateDias(Request $request)
    {
        $periodo = $request->input('periodo');
        $feriados = $request->input('feriados', []);
        
        // Si no se proporciona período, usar el actual o el más reciente
        if (!$periodo) {
            $periodo = DB::table('metas_mensual')->orderBy('periodo','desc')->value('periodo');
            if (!$periodo) {
                $periodo = date('Y-m');
            }
        }
        
        // Extraer año y mes del período
        $year = substr($periodo, 0, 4);
        $month = substr($periodo, 5, 2);
        
        // Calcular el total de días en el mes
        $total_dias = date('t', mktime(0, 0, 0, $month, 1, $year));
        
        // Calcular la suma de valores_dia para el mes (días trabajables/valorados)
        $suma_valores_dia = 0;
        for ($dia = 1; $dia <= $total_dias; $dia++) {
            $dia_semana = date('N', mktime(0, 0, 0, $month, $dia, $year));
            if ($dia_semana >= 1 && $dia_semana <= 5) {
                $suma_valores_dia += 1.0; // Lunes a Viernes
            } elseif ($dia_semana == 6) {
                $suma_valores_dia += 0.5; // Sábado
            }
            // Domingo (7) = 0.0, no suma
        }
        
        try {
            // Limpiar cualquier día existente para ese período
            DB::table('metas_dias')->where('periodo', $periodo)->delete();
            
            // Convertir array de feriados a formato fácil de buscar
            $feriados_map = [];
            foreach ($feriados as $feriado) {
                $feriados_map[$feriado['fecha']] = $feriado['valor'];
            }
            
            // Recalcular suma de valores_dia considerando feriados
            $suma_valores_dia_con_feriados = 0;
            for ($dia = 1; $dia <= $total_dias; $dia++) {
                $fecha = date('Y-m-d', mktime(0, 0, 0, $month, $dia, $year));
                $dia_semana = date('N', mktime(0, 0, 0, $month, $dia, $year));
                
                // Verificar si es feriado
                if (isset($feriados_map[$fecha])) {
                    $suma_valores_dia_con_feriados += $feriados_map[$fecha];
                } else {
                    // Lógica normal
                    if ($dia_semana >= 1 && $dia_semana <= 5) {
                        $suma_valores_dia_con_feriados += 1.0; // Lunes a Viernes
                    } elseif ($dia_semana == 6) {
                        $suma_valores_dia_con_feriados += 0.5; // Sábado
                    }
                    // Domingo (7) = 0.0, no suma
                }
            }
            
            // Insertar todos los días del mes
            $insert_data = [];
            for ($dia = 1; $dia <= $total_dias; $dia++) {
                $fecha = date('Y-m-d', mktime(0, 0, 0, $month, $dia, $year));
                $dia_semana = date('N', mktime(0, 0, 0, $month, $dia, $year)); // 1=Lunes, 7=Domingo
                $semana_friedman = ceil($dia / 7); // Simplificado
                
                // Verificar si es feriado primero
                if (isset($feriados_map[$fecha])) {
                    $valor_dia = $feriados_map[$fecha];
                } else {
                    // Lógica normal para valor_dia:
                    // Lunes (1) a Viernes (5) = 1.0
                    // Sábado (6) = 0.5  
                    // Domingo (7) = 0.0
                    if ($dia_semana >= 1 && $dia_semana <= 5) {
                        $valor_dia = 1.0; // Lunes a Viernes
                    } elseif ($dia_semana == 6) {
                        $valor_dia = 0.5; // Sábado
                    } else {
                        $valor_dia = 0.0; // Domingo
                    }
                }
                
                $insert_data[] = [
                    'fecha' => $fecha,
                    'periodo' => $periodo,
                    'dia_semana' => $dia_semana,
                    'dias_mes' => $suma_valores_dia_con_feriados, // CORRECCIÓN: Suma con feriados considerados
                    'valor_dia' => $valor_dia,
                    'anio' => intval($year),
                    'mes_friedman' => intval($month),
                    'semana_friedman' => $semana_friedman,
                ];
            }
            
            // Insertar todos los registros
            if (!empty($insert_data)) {
                DB::table('metas_dias')->insert($insert_data);
            }
            
            // Fetch data to return
            $diasData = DB::table('metas_dias')->where('periodo', $periodo)->orderBy('fecha')->get();
            
            // Summary data
            $daysWorkable = DB::table('metas_dias')->where('periodo', $periodo)->where('valor_dia','>',0)->count();
            $totals = DB::table('metas_mensual')
                ->select('plaza','tienda', DB::raw('SUM(meta) as total_meta'))
                ->where('periodo', $periodo)
                ->groupBy('plaza','tienda')
                ->get();
            $totalMetaPeriod = DB::table('metas_mensual')->where('periodo', $periodo)->sum('meta');
            $totalDiasPeriod = DB::table('metas_dias')->where('periodo', $periodo)->sum('valor_dia');
            $avgMetaPerDay = $totalDiasPeriod > 0 ? $totalMetaPeriod / $totalDiasPeriod : 0;
            
            return response()->json([
                'message' => 'Dias generados para periodo '.$periodo,
                'dias' => $diasData,
                'metas' => null,
                'summary' => [
                    'days_workable' => $daysWorkable,
                    'totals' => $totals,
                    'total_meta' => $totalMetaPeriod,
                    'total_days' => $totalDiasPeriod,
                    'avg_meta_per_day' => $avgMetaPerDay,
                ],
            ]);
        } catch (\Exception $e) {
            // Si la tabla metas no existe o hay error en query, ignorar y solo return success de días generation
            // Log error if needed
            // Log error if needed
            
            $diasData = DB::table('metas_dias')->where('periodo', $periodo)->orderBy('fecha')->get();
            
            return response()->json([
                'message' => 'Dias generados para periodo '.$periodo,
                'dias' => $diasData,
                'metas' => null,
                'summary' => [
                    'days_workable' => DB::table('metas_dias')->where('periodo', $periodo)->where('valor_dia','>',0)->count(),
                    'totals' => [],
                    'total_meta' => 0,
                    'total_days' => DB::table('metas_dias')->where('periodo', $periodo)->sum('valor_dia'),
                    'avg_meta_per_day' => 0,
                ],
            ]);
        }
    }

    /**
     * Obtener días del período para el modal de feriados
     */
    public function getDiasPeriodo(Request $request)
    {
        $periodo = $request->input('periodo');
        
        if (!$periodo || !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            return response()->json(['error' => 'Período inválido'], 400);
        }
        
        $year = substr($periodo, 0, 4);
        $month = substr($periodo, 5, 2);
        $total_dias = date('t', mktime(0, 0, 0, $month, 1, $year));
        
        $dias = [];
        $nombres_dia = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        
        for ($dia = 1; $dia <= $total_dias; $dia++) {
            $fecha = date('Y-m-d', mktime(0, 0, 0, $month, $dia, $year));
            $dia_semana = date('N', mktime(0, 0, 0, $month, $dia, $year));
            
            // Determinar valor normal
            if ($dia_semana >= 1 && $dia_semana <= 5) {
                $valor_dia = 1.0;
            } elseif ($dia_semana == 6) {
                $valor_dia = 0.5;
            } else {
                $valor_dia = 0.0;
            }
            
            $dias[] = [
                'fecha' => $fecha,
                'dia_semana' => $dia_semana,
                'nombre_dia' => $nombres_dia[$dia_semana],
                'valor_dia' => $valor_dia
            ];
        }
        
        return response()->json(['dias' => $dias]);
    }

    /**
     * Pruebas unitarias de velocidad para metas
     */
    public function performanceTest(Request $request)
    {
        $periodo = $request->input('periodo', date('Y-m'));
        
        $resultados = [];
        
        // Test 1: Velocidad de carga de períodos
        $inicio = microtime(true);
        $periodos = DB::table('metas_mensual')->select('periodo')->distinct()->orderBy('periodo','desc')->get();
        $tiempo_periodos = (microtime(true) - $inicio) * 1000;
        $resultados['periodos'] = [
            'tiempo_ms' => $tiempo_periodos,
            'cantidad' => $periodos->count(),
            'status' => $tiempo_periodos < 500 ? 'OK' : 'LENTA'
        ];
        
        // Test 2: Velocidad de carga de metas mensual
        if (!empty($periodo)) {
            $inicio = microtime(true);
            $metas_mensual = DB::table('metas_mensual')->where('periodo', $periodo)->get();
            $tiempo_mensual = (microtime(true) - $inicio) * 1000;
            $resultados['metas_mensual'] = [
                'tiempo_ms' => $tiempo_mensual,
                'cantidad' => $metas_mensual->count(),
                'status' => $tiempo_mensual < 1000 ? 'OK' : 'LENTA'
            ];
            
            // Test 3: Velocidad de carga de metas diarias
            $inicio = microtime(true);
            $metas_dias = DB::table('metas_dias')->where('periodo', $periodo)->orderBy('fecha')->get();
            $tiempo_dias = (microtime(true) - $inicio) * 1000;
            $resultados['metas_dias'] = [
                'tiempo_ms' => $tiempo_dias,
                'cantidad' => $metas_dias->count(),
                'status' => $tiempo_dias < 500 ? 'OK' : 'LENTA'
            ];
            
            // Test 4: Velocidad de carga de metas detalles
            $inicio = microtime(true);
            $metas_detalles = DB::table('metas')
                ->select('plaza','tienda','fecha','meta_total','dias_total','valor_dia','meta_dia')
                ->whereRaw('DATE_TRUNC(\'month\', fecha) = DATE_TRUNC(\'month\', ?::date)', [$periodo . '-01'])
                ->orderBy('plaza')->orderBy('tienda')->orderBy('fecha')
                ->get();
            $tiempo_detalles = (microtime(true) - $inicio) * 1000;
            $resultados['metas_detalles'] = [
                'tiempo_ms' => $tiempo_detalles,
                'cantidad' => $metas_detalles->count(),
                'status' => $tiempo_detalles < 2000 ? 'OK' : 'LENTA'
            ];
            
            // Test 5: Velocidad de generación de metas
            $inicio = microtime(true);
            
            // Query de generación (sin ejecutarla realmente)
            $query = "
                SELECT COUNT(*) as count
                FROM metas_mensual m 
                JOIN metas_dias f ON (m.periodo=f.periodo) 
                WHERE f.periodo = ?
            ";
            $count = DB::select($query, [$periodo])[0]->count;
            $tiempo_generacion = (microtime(true) - $inicio) * 1000;
            $resultados['generacion_test'] = [
                'tiempo_ms' => $tiempo_generacion,
                'registros_a_generar' => $count,
                'status' => $tiempo_generacion < 1500 ? 'OK' : 'LENTA'
            ];
        }
        
        // Resumen general
        $total_tiempos = array_sum(array_column($resultados, 'tiempo_ms'));
        $resultados['resumen'] = [
            'tiempo_total_ms' => $total_tiempos,
            'promedio_tiempo_ms' => $total_tiempos / count(array_filter($resultados)),
            'status_general' => $total_tiempos < 5000 ? 'OK' : 'NECESITA_OPTIMIZACION'
        ];
        
        return response()->json([
            'success' => true,
            'periodo' => $periodo,
            'pruebas' => $resultados,
            'timestamp' => date('Y-m-d H:i:s'),
            'recomendaciones' => $this->generarRecomendaciones($resultados)
        ]);
    }

    /**
     * Generar recomendaciones basadas en resultados de pruebas
     */
    private function generarRecomendaciones($resultados)
    {
        $recomendaciones = [];
        
        if ($resultados['periodos']['status'] === 'LENTA') {
            $recomendaciones[] = 'Considerar agregar índice en tabla metas_mensual(periodo)';
        }
        
        if ($resultados['metas_mensual']['status'] === 'LENTA') {
            $recomendaciones[] = 'Optimizar consulta de metas_mensual para período específico';
        }
        
        if ($resultados['metas_detalles']['status'] === 'LENTA') {
            $recomendaciones[] = 'Revisar índices en tabla metas para consultas por período';
        }
        
        if ($resultados['resumen']['status_general'] === 'NECESITA_OPTIMIZACION') {
            $recomendaciones[] = 'Considerar implementar caché frontend o paginación';
        }
        
        return $recomendaciones;
    }
}