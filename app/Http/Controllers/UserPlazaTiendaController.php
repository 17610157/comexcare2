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
        $users = User::where('activo', true)->get();
        return view('admin.user-plaza-tienda.index', compact('users'));
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

        $tiendas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('clave_tienda')
            ->orderBy('clave_tienda')
            ->pluck('clave_tienda')
            ->filter()
            ->values();

        return view('admin.user-plaza-tienda.edit', compact('user', 'plazas', 'tiendas'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'plazas' => 'required|array',
            'tiendas' => 'required|array',
        ]);

        DB::transaction(function () use ($user, $request) {
            UserPlazaTienda::where('user_id', $user->id)->delete();
            
            foreach ($request->plazas as $plaza) {
                foreach ($request->tiendas as $tienda) {
                    UserPlazaTienda::create([
                        'user_id' => $user->id,
                        'plaza' => $plaza,
                        'tienda' => $tienda,
                    ]);
                }
            }
        });

        return redirect()->route('user-plaza-tienda.index')->with('success', 'Asignaciones actualizadas correctamente');
    }
}
