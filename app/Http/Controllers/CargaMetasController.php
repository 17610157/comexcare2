<?php

namespace App\Http\Controllers;

use App\Models\MetaMensual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\MetaMensualImport;
use Illuminate\Support\Facades\DB;

class CargaMetasController extends Controller
{
    public function index()
    {
        return view('admin.metas.index');
    }

    public function upload(Request $request)
    {
        try {
            $request->validate([
                'archivo_excel' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB max
            ], [
                'archivo_excel.required' => 'Debe seleccionar un archivo Excel.',
                'archivo_excel.mimes' => 'El archivo debe ser de tipo Excel (.xlsx, .xls, .csv).',
                'archivo_excel.max' => 'El archivo no puede ser mayor a 10MB.',
            ]);

            $file = $request->file('archivo_excel');
            $filename = time() . '_' . $file->getClientOriginalName();
            
            // Guardar archivo temporal
            $file->storeAs('temp', $filename, 'local');

            // Importar datos
            $import = new MetaMensualImport();
            Excel::import($import, storage_path("app/temp/{$filename}"));

            // Eliminar archivo temporal
            unlink(storage_path("app/temp/{$filename}"));

            return response()->json([
                'success' => true,
                'message' => "Se importaron {$import->getRowCount()} registros correctamente.",
                'data' => [
                    'total_importados' => $import->getRowCount(),
                    'errores' => $import->getErrors()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar metas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function consultar(Request $request)
    {
        try {
            $periodo = $request->input('periodo');
            $plaza = $request->input('plaza');
            $tienda = $request->input('tienda');

            $query = MetaMensual::query();

            if ($periodo) {
                $query->byPeriodo($periodo);
            }
            if ($plaza) {
                $query->byPlaza($plaza);
            }
            if ($tienda) {
                $query->byTienda($tienda);
            }

            $metas = $query->orderBy('plaza')->orderBy('tienda')->orderBy('periodo')->get();

            return response()->json([
                'success' => true,
                'data' => $metas,
                'total' => count($metas)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al consultar metas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar metas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function eliminar(Request $request)
    {
        try {
            $periodo = $request->input('periodo');
            $plaza = $request->input('plaza');
            $tienda = $request->input('tienda');

            if (!$periodo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar un periodo para eliminar.'
                ], 400);
            }

            $query = MetaMensual::byPeriodo($periodo);

            if ($plaza) {
                $query->byPlaza($plaza);
            }
            if ($tienda) {
                $query->byTienda($tienda);
            }

            $count = $query->count();
            $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$count} registros correctamente."
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar metas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar metas: ' . $e->getMessage()
            ], 500);
        }
    }
}