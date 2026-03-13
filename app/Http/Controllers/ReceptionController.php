<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
use App\Models\Reception;
use App\Models\ReceptionTarget;
use Illuminate\Http\Request;

class ReceptionController extends Controller
{
    public function index()
    {
        $receptions = Reception::with('group', 'targets')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('admin.reception.index', compact('receptions'));
    }

    public function create()
    {
        $groups = Group::all();
        $computers = Computer::where('status', 'online')->get();

        return view('admin.reception.create', compact('groups', 'computers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:immediate,scheduled,recurring',
            'scheduled_at' => 'nullable|required_if:type,scheduled',
            'scheduled_time' => 'nullable',
            'recurrence' => 'nullable|required_if:type,recurring',
            'target_type' => 'required|in:all,group,specific',
            'group_id' => 'nullable|required_if:target_type,group',
        ]);

        // Procesar días de la semana
        $weekDays = null;
        if ($request->has('week_days') && is_array($request->week_days)) {
            $weekDays = array_filter($request->week_days);
            $weekDays = array_values($weekDays);
        }

        // Procesar tipos de archivo
        $fileTypes = null;
        $allFiles = true;
        if ($request->has('file_types') && is_array($request->file_types) && count($request->file_types) > 0) {
            $fileTypes = array_filter($request->file_types);
            $fileTypes = array_values($fileTypes);
            $allFiles = false;
        }

        // Procesar archivos específicos
        $specificFiles = null;
        if ($request->has('specific_files') && $request->specific_files) {
            $specificFilesText = trim($request->specific_files);
            if (! empty($specificFilesText)) {
                $specificFiles = array_filter(array_map('trim', explode("\n", $specificFilesText)));
                $specificFiles = array_values($specificFiles);
                $allFiles = false;
            }
        }

        $reception = Reception::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'scheduled_at' => $request->scheduled_at,
            'scheduled_time' => $request->scheduled_time,
            'recurrence' => $request->recurrence,
            'frequency_type' => $request->frequency_type,
            'frequency_interval' => $request->frequency_interval,
            'week_days' => $weekDays,
            'file_types' => $fileTypes,
            'specific_files' => $specificFiles,
            'all_files' => $allFiles,
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
                        'file_types' => $reception->file_types,
                        'specific_files' => $reception->specific_files,
                        'all_files' => $reception->all_files,
                        'scheduled_time' => $reception->scheduled_time,
                        'frequency_type' => $reception->frequency_type,
                        'frequency_interval' => $reception->frequency_interval,
                        'type' => $reception->type,
                        'recurrence' => $reception->recurrence,
                        'week_days' => $reception->week_days,
                    ]),
                    'status' => 'pending',
                ]);
            }
        }

        // Si es inmediato, cambiar estado
        if ($request->type === 'immediate') {
            $reception->update(['status' => 'in_progress']);
        }

        return redirect()->route('admin.reception.index')->with('success', 'Recepción creada correctamente');
    }

    public function show(Reception $reception)
    {
        $reception->load('targets.computer', 'group');

        return view('admin.reception.show', compact('reception'));
    }

    public function showComputer(Computer $computer)
    {
        $receivePaths = $computer->receive_paths ?? [];

        $receptionData = [];
        foreach ($receivePaths as $path) {
            $serverPath = $this->getServerPath($computer, $path);
            $files = $this->getFilesInPath($serverPath);

            $receptionData[] = [
                'local_path' => $path['local_path'] ?? '',
                'folder_name' => $path['folder_name'] ?? '',
                'server_path' => $serverPath,
                'files' => $files,
            ];
        }

        return view('admin.reception.computer', compact('computer', 'receptionData'));
    }

    public function destroy(Reception $reception)
    {
        $reception->delete();

        return redirect()->route('admin.reception.index')->with('success', 'Recepción eliminada');
    }

    public function stop(Reception $reception)
    {
        $reception->update(['status' => 'stopped']);

        return redirect()->route('admin.reception.index')->with('success', 'Recepción detenida. Ya no se enviarán más comandos.');
    }

    private function getServerPath(Computer $computer, array $path): string
    {
        $shortKey = $computer->short_key ?? 'NO_KEY';
        $folderName = $path['folder_name'] ?? basename($path['local_path'] ?? '');

        return storage_path('app/distributions/'.$shortKey.'/'.$folderName);
    }

    private function getFilesInPath(string $path): array
    {
        $files = [];

        if (! is_dir($path)) {
            return $files;
        }

        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path.'/'.$item;
            $isDirectory = is_dir($fullPath);

            $files[] = [
                'name' => $item,
                'path' => $fullPath,
                'is_directory' => $isDirectory,
                'size' => $isDirectory ? 0 : filesize($fullPath),
                'modified' => filemtime($fullPath),
            ];
        }

        return $files;
    }
}
