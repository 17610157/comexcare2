<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DemoRequest;
use Illuminate\Http\Request;

class DemoRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'param1' => 'required|string|max:255',
            'param2' => 'required|string|max:255',
        ]);

        $demo = DemoRequest::create([
            'param1' => $validated['param1'],
            'param2' => $validated['param2'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Datos guardados correctamente',
            'data' => $demo,
        ], 201);
    }
}
