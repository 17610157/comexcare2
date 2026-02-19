<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    public function data(Request $request)
    {
        $roles = Role::with('permissions')->get();
        return response()->json(['data' => $roles]);
    }

    public function store(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        if ($request->permissions) {
            $role->givePermissionTo($request->permissions);
        }
        return response()->json(['success' => true, 'role' => $role]);
    }

    public function show(Role $role)
    {
        $role->load('permissions');
        return response()->json($role);
    }

    public function update(Request $request, Role $role)
    {
        $role->update(['name' => $request->name]);
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }
        return response()->json(['success' => true, 'role' => $role]);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(['success' => true]);
    }

    public function allPermissions()
    {
        $permissions = Permission::all()->groupBy('module');
        return response()->json($permissions);
    }
}
