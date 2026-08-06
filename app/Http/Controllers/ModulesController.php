<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModulesController extends Controller
{
    public function index()
    {
        $modules = Module::withCount('authorizableEmails')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.modules.index', compact('modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:modules,slug',
            'description' => 'nullable|string|max:500',
        ]);

        Module::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_active' => DB::raw('true'),
        ]);

        return response()->json([
            'message' => 'Módulo creado exitosamente.',
        ]);
    }

    public function update(Request $request, Module $module)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'required|boolean',
        ]);

        $module->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => DB::raw($request->is_active ? 'true' : 'false'),
        ]);

        return response()->json([
            'message' => 'Módulo actualizado exitosamente.',
        ]);
    }

    public function destroy(Module $module)
    {
        $module->delete();

        return response()->json([
            'message' => 'Módulo eliminado exitosamente.',
        ]);
    }
}
