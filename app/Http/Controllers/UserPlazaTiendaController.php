<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPlazaTienda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPlazaTiendaController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('plazaTiendas')->get();
        
        $plazasData = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->select('id_plaza', DB::raw('COUNT(*) as tiendas_count'))
            ->groupBy('id_plaza')
            ->get();

        return view('admin.user-plaza-tienda.index', compact('users', 'plazasData'));
    }

    public function edit(User $user)
    {
        $user->load('plazaTiendas');
        
        $plazas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values();

        $tiendasPorPlaza = [];
        foreach ($plazas as $plaza) {
            $tiendas = DB::table('bi_sys_tiendas')
                ->where('id_plaza', $plaza)
                ->whereNotNull('clave_tienda')
                ->orderBy('clave_tienda')
                ->pluck('clave_tienda')
                ->filter()
                ->values();
            $tiendasPorPlaza[$plaza] = $tiendas;
        }

        $userPlazas = $user->plazaTiendas->pluck('plaza')->filter()->unique()->values()->toArray();
        $userTiendas = $user->plazaTiendas->pluck('tienda')->filter()->unique()->values()->toArray();

        return view('admin.user-plaza-tienda.edit', compact('user', 'plazas', 'tiendasPorPlaza', 'userPlazas', 'userTiendas'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'plazas' => 'required|array',
            'plazas.*' => 'string',
            'tiendas' => 'nullable|array',
            'tiendas.*' => 'string',
        ]);

        $plazas = $request->input('plazas', []);
        $tiendas = $request->input('tiendas', []);

        DB::transaction(function () use ($user, $plazas, $tiendas) {
            UserPlazaTienda::where('user_id', $user->id)->delete();
            
            foreach ($plazas as $plaza) {
                if (empty($tiendas)) {
                    UserPlazaTienda::create([
                        'user_id' => $user->id,
                        'plaza' => $plaza,
                        'tienda' => null,
                    ]);
                } else {
                    foreach ($tiendas as $tienda) {
                        UserPlazaTienda::create([
                            'user_id' => $user->id,
                            'plaza' => $plaza,
                            'tienda' => $tienda,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.user-plaza-tienda.index')->with('success', 'Asignaciones actualizadas correctamente para ' . $user->name);
    }

    public function getTiendas(Request $request)
    {
        $plaza = $request->input('plaza');
        
        $tiendas = DB::table('bi_sys_tiendas')
            ->where('id_plaza', $plaza)
            ->whereNotNull('clave_tienda')
            ->orderBy('clave_tienda')
            ->select('clave_tienda', 'nombre')
            ->get();

        return response()->json($tiendas);
    }
}
