<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('usuarios.index');
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value', '');

        try {
            $query = User::select(['id', 'name', 'email', 'plaza', 'tienda', 'rol', 'activo', 'created_at']);

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', '%'.$search.'%')
                      ->orWhere('email', 'ILIKE', '%'.$search.'%')
                      ->orWhere('plaza', 'ILIKE', '%'.$search.'%')
                      ->orWhere('tienda', 'ILIKE', '%'.$search.'%')
                      ->orWhere('rol', 'ILIKE', '%'.$search.'%');
                });
            }

            if ($request->filled('rol') && $request->input('rol') !== '') {
                $query->where('rol', $request->input('rol'));
            }

            if ($request->filled('activo') && $request->input('activo') !== '') {
                $query->where('activo', $request->input('activo') === '1');
            }

            $total = $query->count();

            $data = $query->orderBy('name')
                ->offset($start)
                ->limit($length)
                ->get();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int)$total,
                'recordsFiltered' => (int)$total,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('User data error: ' . $e->getMessage());
            return response()->json(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'plaza' => 'nullable|string|max:10',
            'tienda' => 'nullable|string|max:10',
            'rol' => 'required|in:admin,vendedor,gerente,encargado',
            'activo' => 'boolean',
        ]);

        try {
            $validated['password'] = Hash::make($validated['password']);
            $validated['activo'] = $request->boolean('activo', true);

            User::create($validated);

            return response()->json(['success' => true, 'message' => 'Usuario creado correctamente']);
        } catch (\Exception $e) {
            Log::error('User store error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'plaza' => 'nullable|string|max:10',
            'tienda' => 'nullable|string|max:10',
            'rol' => 'required|in:admin,vendedor,gerente,encargado',
            'activo' => 'boolean',
        ]);

        try {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->plaza = $validated['plaza'];
            $user->tienda = $validated['tienda'];
            $user->rol = $validated['rol'];
            $user->activo = $request->boolean('activo', true);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } catch (\Exception $e) {
            Log::error('User update error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (\Exception $e) {
            Log::error('User destroy error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        return response()->json($user);
    }
}
