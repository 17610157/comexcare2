<?php

namespace App\Http\Controllers;

use App\Models\AuthorizableEmail;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorizableEmailsController extends Controller
{
    public function index()
    {
        $emails = AuthorizableEmail::with(['user', 'module'])
            ->orderBy('id', 'desc')
            ->get();

        $modules = Module::whereRaw('is_active = true')->orderBy('name')->get();
        $users = User::where('activo', 1)->orderBy('name')->get();

        return view('admin.authorizable-emails.index', compact('emails', 'modules', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email|max:255',
            'module_id' => 'nullable|exists:modules,id',
        ]);

        $exists = AuthorizableEmail::where('email', $request->email)
            ->where('module_id', $request->module_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Este correo ya está registrado para este módulo.',
            ], 422);
        }

        AuthorizableEmail::create([
            'user_id' => $request->user_id,
            'email' => $request->email,
            'module_id' => $request->module_id,
            'is_active' => DB::raw('true'),
        ]);

        return response()->json([
            'message' => 'Correo autorizado registrado exitosamente.',
        ]);
    }

    public function update(Request $request, AuthorizableEmail $authorizableEmail)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email' => 'required|email|max:255',
            'module_id' => 'nullable|exists:modules,id',
            'is_active' => 'required|boolean',
        ]);

        $exists = AuthorizableEmail::where('email', $request->email)
            ->where('module_id', $request->module_id)
            ->where('id', '!=', $authorizableEmail->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Este correo ya está registrado para este módulo.',
            ], 422);
        }

        $authorizableEmail->update([
            'user_id' => $request->user_id,
            'email' => $request->email,
            'module_id' => $request->module_id,
            'is_active' => DB::raw($request->is_active ? 'true' : 'false'),
        ]);

        return response()->json([
            'message' => 'Correo autorizado actualizado exitosamente.',
        ]);
    }

    public function destroy(AuthorizableEmail $authorizableEmail)
    {
        $authorizableEmail->delete();

        return response()->json([
            'message' => 'Correo autorizado eliminado exitosamente.',
        ]);
    }
}
