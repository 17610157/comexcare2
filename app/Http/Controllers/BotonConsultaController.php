<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;

class BotonConsultaController extends Controller
{
    /**
     * Botón especializado para consultas de metas con visualización personalizada
     */
    
    /**
     * API para consultar metas con vista personalizada
     */
    public function consultarMetas(Request $request)
    {
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $periodo = $request->input('periodo', ''); // Formato: YYYY-MM
        $vista_tipo = $request->input('vista_tipo', 'metas_dias'); // 'metas_dias' o 'metas_mensual'
        
        try {
            $resultados = [];
            $mensaje_informativo = '';
            
            // Determinar vista según la fecha y tipo seleccionado
            $fecha_actual = date('Y-m-d');
            $anio_actual = date('Y');
            $mes_actual = date('m');
            
            // Validar formato del periodo
            if (!empty($periodo) && !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                return response()->json([
                    'error' => 'El periodo debe tener formato YYYY-MM (ej: 2024-01)',
                    'resultados' => []
                ], 400);
            }
            
            if ($vista_tipo === 'metas_dias') {
                // Consulta para tabla metas_dias
                $sql = "SELECT m.plaza, m.tienda, m.fecha, m.meta_dia, m.f.dias_mes 
                        FROM metas m 
                        LEFT JOIN metas_dias f ON (m.periodo = f.periodo) 
                        WHERE m.plaza = ? AND m.tienda = ? AND m.periodo = ?";
                
                $resultados = \Illuminate\Support\Facades\DB::select($sql, [$plaza, $tienda, $periodo]);
                
                if (empty($resultados)) {
                    $mensaje_informativo = "No se encontraron datos para el período {$periodo} con los filtros especificados";
                }
            } 
            } 
            elseif ($vista_tipo === 'metas_mensual') {
                // Consulta para tabla metas_mensual
                $sql = "SELECT m.plaza, m.tienda, f.periodo, f.descripcion, 
                           SUM(f.valor_dia) as total_valor_dia, 
                           SUM(f.meta_dia) as total_meta_dia,
                           f.fecha 
                        FROM metas_mensual f 
                        WHERE m.plaza = ? AND m.tienda = ? AND f.periodo = ?
                        GROUP BY m.plaza, m.tienda, f.periodo, f.descripcion, f.fecha
                        ORDER BY f.fecha";
                
                $resultados = \Illuminate\Support\Facades\DB::select($sql, [$plaza, $tienda, $periodo]);
                
                if (empty($resultados)) {
                    $mensaje_informativo = "No se encontraron datos para el período {$periodo} con los filtros especificados";
                }
            }
            }
            
            return response()->json([
                'vista_tipo' => $vista_tipo',
                'periodo' => $periodo,
                'resultados' => $resultados,
                'mensaje_informativo' => $mensaje_informativo
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error en consulta personalizada de metas: " . $e->getMessage());
            return response()->json([
                'error' => 'Error en la consulta: ' . $e->getMessage(),
                'resultados' => []
            ], 500);
        }
    }

    /**
     * Redirigir a la vista correspondiente según el tipo
     */
    private function redirigirAccionMetas($data, $vista_tipo)
    {
        $query_params = http_build_query($data);
        
        // Construir URL según el tipo de vista
        $base_url = url('/reportes/metas-ventas') . '?' . $query_params);
        
        // Redirigir con los parámetros
        return redirect()->to($base_url);
    }
}