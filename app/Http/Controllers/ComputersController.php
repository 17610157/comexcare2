<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
use Illuminate\Http\Request;

class ComputersController extends Controller
{
    public function index()
    {
        $computers = Computer::with('group')->paginate(50);

        return view('admin.computers.index', compact('computers'));
    }

    public function show(Computer $computer)
    {
        $computer->load('commands', 'distributionTargets.distribution');

        return view('admin.computers.show', compact('computer'));
    }

    public function edit(Computer $computer)
    {
        $groups = Group::all();

        return view('admin.computers.edit', compact('computer', 'groups'));
    }

    public function update(Request $request, Computer $computer)
    {
        $request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'agent_config' => 'nullable|array',
            'download_path' => 'nullable|string',
        ]);

        $computer->update($request->only(['group_id', 'agent_config', 'download_path']));

        return redirect()->route('admin.computers.index')->with('success', 'Computer updated');
    }

    public function destroy(Computer $computer)
    {
        $computer->delete();

        return redirect()->route('admin.computers.index')->with('success', 'Agente eliminado correctamente');
    }
}
