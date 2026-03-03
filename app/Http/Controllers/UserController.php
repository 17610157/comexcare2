<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return view('usuarios.index');
    }

    public function data(Request $request)
    {
        $users = User::with('roles')
            ->when($request->rol, function ($query) use ($request) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->rol);
                });
            })
            ->when($request->activo !== '' && $request->activo !== null, function ($query) use ($request) {
                $query->where('activo', $request->activo);
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'plaza' => $user->plaza,
                    'tienda' => $user->tienda,
                    'rol' => $user->getRoleNames()->first(),
                    'activo' => $user->activo,
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        Log::info('User store request', $request->all());

        try {
            $roles = Role::pluck('name')->toArray();
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'plaza' => 'nullable|string|max:10',
                'tienda' => 'nullable|string|max:10',
                'rol' => 'required|in:'.implode(',', $roles),
            ], [
                'password.required' => 'La contraseña es requerida.',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            $validated['activo'] = $request->has('activo');
            $rolName = $validated['rol'];
            unset($validated['rol']);

            $user = User::create($validated);
            $user->assignRole($rolName);

            return response()->json(['success' => true, 'message' => 'Usuario creado correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('User store validation error: '.json_encode($e->errors()));

            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('User store error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, User $user)
    {
        Log::info('User update request', $request->all());

        try {
            $roles = Role::pluck('name')->toArray();
            $rules = [
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'plaza' => 'nullable|string|max:10',
                'tienda' => 'nullable|string|max:10',
                'rol' => 'required|in:'.implode(',', $roles),
            ];

            if ($request->filled('password')) {
                $rules['password'] = 'string|min:8';
            }

            $validated = $request->validate($rules);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->plaza = $validated['plaza'] ?? null;
            $user->tienda = $validated['tienda'] ?? null;
            $user->activo = $request->has('activo');

            if ($request->filled('password')) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            $user->syncRoles([$validated['rol']]);

            return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('User update validation error: '.json_encode($e->errors()));

            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('User update error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(User $user)
    {
        try {
            $user->delete();

            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } catch (\Exception $e) {
            Log::error('User destroy error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'plaza' => $user->plaza,
            'tienda' => $user->tienda,
            'rol' => $user->getRoleNames()->first(),
            'activo' => $user->activo,
            'created_at' => $user->created_at,
        ]);
    }
}
