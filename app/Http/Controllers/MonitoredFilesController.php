<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
use App\Models\MonitoredFile;
use Database\Seeders\DefaultMonitoredFilesSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoredFilesController extends Controller
{
    public function index()
    {
        $records = MonitoredFile::with(['computer', 'group'])
            ->orderByDesc('general')
            ->orderByDesc('group_id')
            ->orderBy('computer_id')
            ->orderBy('sort_order')
            ->paginate(25);

        $groups = Group::orderBy('name')->get(['id', 'name']);
        $computers = Computer::orderBy('computer_name')->get(['id', 'computer_name', 'short_key']);

        return view('admin.monitored-files.index', compact('records', 'groups', 'computers'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRecord($request);

        MonitoredFile::create($this->recordPayload($data, $request));

        return redirect()->route('admin.monitored-files.index')
            ->with('success', 'Registro de archivo monitoreado creado exitosamente.');
    }

    public function update(Request $request, MonitoredFile $monitoredFile)
    {
        $data = $this->validateRecord($request, $monitoredFile);

        $monitoredFile->update($this->recordPayload($data, $request));

        return redirect()->route('admin.monitored-files.index')
            ->with('success', 'Registro de archivo monitoreado actualizado exitosamente.');
    }

    public function destroy(MonitoredFile $monitoredFile)
    {
        $monitoredFile->delete();

        return redirect()->route('admin.monitored-files.index')
            ->with('success', 'Registro de archivo monitoreado eliminado exitosamente.');
    }

    public function seedDefaults(Request $request)
    {
        $validated = $request->validate([
            'group_id' => 'required|integer|exists:groups,id',
        ]);

        $group = Group::findOrFail($validated['group_id']);

        DB::transaction(function () use ($group) {
            $group->monitoredFiles()->delete();

            foreach (DefaultMonitoredFilesSeeder::defaultEntries() as $i => $entry) {
                $group->monitoredFiles()->create($entry + ['sort_order' => $i + 1]);
            }
        });

        return redirect()->route('admin.monitored-files.index')
            ->with('success', "Lista predeterminada asignada al grupo '{$group->name}'.");
    }

    private function validateRecord(Request $request, ?MonitoredFile $except = null): array
    {
        return $request->validate([
            'scope' => 'required|in:group,computer,general',
            'group_id' => 'required_if:scope,group|nullable|integer|exists:groups,id',
            'computer_id' => 'required_if:scope,computer|nullable|integer|exists:computers,id',
            'path' => 'required|string|max:500',
            'file_names' => 'nullable|array',
            'file_names.*' => 'nullable|string|max:255',
            'recursive' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }

    private function recordPayload(array $data, Request $request): array
    {
        $fileNames = collect($data['file_names'] ?? [])
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->values()
            ->all();

        return [
            'general' => $data['scope'] === 'general',
            'group_id' => $data['scope'] === 'group' ? $data['group_id'] : null,
            'computer_id' => $data['scope'] === 'computer' ? $data['computer_id'] : null,
            'path' => trim($data['path']),
            'file_names' => $fileNames,
            'recursive' => $request->boolean('recursive'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
