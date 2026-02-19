<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all()->groupBy('module');
        return view('admin.permissions.index', compact('permissions'));
    }

    public function data(Request $request)
    {
        $permissions = Permission::all();
        return response()->json(['data' => $permissions]);
    }
}
