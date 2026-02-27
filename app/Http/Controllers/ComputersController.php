<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ComputerLog;
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

    public function logs(Request $request, Computer $computer)
    {
        $lastId = $request->query('last_id', 0);

        $logs = ComputerLog::where('computer_id', $computer->id)
            ->where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'time' => $log->created_at->format('H:i:s'),
                ];
            });

        return response()->json(['logs' => $logs]);
    }

    public function status(Computer $computer)
    {
        return response()->json([
            'status' => $computer->status,
            'last_seen' => $computer->last_seen?->toIso8601String(),
        ]);
    }
}
