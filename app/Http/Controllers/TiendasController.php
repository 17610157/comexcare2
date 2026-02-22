<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TiendasController extends Controller
{
    public function index()
    {
        return view('admin.tiendas.index');
    }

    public function data(Request $request)
    {
        $search = $request->input('search.value', '');
        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
        $draw = $request->input('draw', 1);
        
        $query = DB::table('bi_sys_tiendas')
            ->select('id', 'clave_tienda', 'nombre', 'id_plaza', 'zona', 'clave_alterna')
            ->when($search, function($q) use ($search) {
                return $q->where(function($sub) use ($search) {
                    $sub->where('clave_tienda', 'ILIKE', '%'.$search.'%')
                        ->orWhere('nombre', 'ILIKE', '%'.$search.'%')
                        ->orWhere('id_plaza', 'ILIKE', '%'.$search.'%')
                        ->orWhere('zona', 'ILIKE', '%'.$search.'%')
                        ->orWhere('clave_alterna', 'ILIKE', '%'.$search.'%');
                });
            });

        $total = $query->count();
        
        $data = $query->orderBy('id_plaza')
            ->orderBy('clave_tienda')
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data
        ]);
    }

    public function show($tienda)
    {
        $tiendaData = DB::table('bi_sys_tiendas')
            ->where('clave_tienda', $tienda)
            ->orWhere('id', $tienda)
            ->first();

        return response()->json($tiendaData);
    }

    public function store(Request $request)
    {
        try {
            $id = DB::table('bi_sys_tiendas')->insertGetId([
                'clave_tienda' => $request->clave_tienda,
                'nombre' => $request->nombre ?? '',
                'id_plaza' => $request->id_plaza ?? '',
                'zona' => $request->zona ?? '',
                'clave_alterna' => $request->clave_alterna ?? '',
                'estado' => 'A',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Tienda creada correctamente',
                'id' => $id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error al crear tienda: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $tienda)
    {
        try {
            DB::table('bi_sys_tiendas')
                ->where('id', $tienda)
                ->update([
                    'clave_tienda' => $request->clave_tienda,
                    'nombre' => $request->nombre ?? '',
                    'id_plaza' => $request->id_plaza ?? '',
                    'zona' => $request->zona ?? '',
                    'clave_alterna' => $request->clave_alterna ?? '',
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true, 
                'message' => 'Tienda actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Error al actualizar tienda: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($tienda)
    {
        // Las tiendas son del sistema, no permitir eliminación
        return response()->json([
            'success' => false, 
            'message' => 'No se pueden eliminar tiendas del sistema. Estas tiendas provienen de la base de datos del sistema.'
        ], 403);
    }
}
