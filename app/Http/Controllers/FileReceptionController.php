<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
use App\Models\Reception as ModelsReception;
use App\Models\ReceptionTarget;
use Illuminate\Http\Request;

class FileReceptionController extends Controller
{
    public function index()
    {
        $receptions = ModelsReception::with('group', 'targets')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.file-receptions.index', compact('receptions'));
    }

    public function create()
    {
        $groups = Group::all();
        $computers = Computer::where('status', 'online')->get();

        return view('admin.file-receptions.create', compact('groups', 'computers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:immediate,scheduled,recurring',
            'scheduled_at' => 'nullable|required_if:type,scheduled',
            'recurrence' => 'nullable|required_if:type,recurring',
            'target_type' => 'required|in:all,group,specific',
            'group_id' => 'nullable|required_if:target_type,group',
        ]);

        $reception = ModelsReception::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'scheduled_at' => $request->scheduled_at,
            'recurrence' => $request->recurrence,
            'status' => 'pending',
            'group_id' => $request->group_id,
        ]);

        // Determinar qué computadoras recibirán el comando
        $computerIds = [];

        if ($request->target_type === 'all') {
            $computerIds = Computer::pluck('id')->toArray();
        } elseif ($request->target_type === 'group' && $request->group_id) {
            $computerIds = Computer::where('group_id', $request->group_id)->pluck('id')->toArray();
        } elseif ($request->target_type === 'specific' && $request->computer_ids) {
            $computerIds = $request->computer_ids;
        }

        // Crear objetivos de recepción y comandos
        foreach ($computerIds as $computerId) {
            $receptionTarget = ReceptionTarget::create([
                'reception_id' => $reception->id,
                'computer_id' => $computerId,
                'status' => 'pending',
                'progress' => 0,
            ]);

            // Crear comando para el agente
            $computer = Computer::find($computerId);
            if ($computer) {
                \App\Models\Command::create([
                    'computer_id' => $computerId,
                    'type' => 'receive',
                    'data' => json_encode([
                        'reception_id' => $reception->id,
                        'reception_target_id' => $receptionTarget->id,
                        'name' => $reception->name,
                        'receive_paths' => $computer->receive_paths ?? [],
                    ]),
                    'status' => 'pending',
                ]);
            }
        }

        // Si es inmediato, cambiar estado
        if ($request->type === 'immediate') {
            $reception->update(['status' => 'in_progress']);
        }

        return redirect()->route('admin.file-receptions.index')->with('success', 'Recepción creada correctamente');
    }

    public function show(ModelsReception $reception)
    {
        $reception->load('targets.computer', 'group');

        return view('admin.file-receptions.show', compact('reception'));
    }

    public function destroy(ModelsReception $reception)
    {
        $reception->delete();

        return redirect()->route('admin.file-receptions.index')->with('success', 'Recepción eliminada');
    }
}
