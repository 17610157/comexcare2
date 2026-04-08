<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupShortKey;
use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class GroupsController extends Controller
{
    public function index()
    {
        $groups = Group::withCount('computers')
            ->with('shortKeys')
            ->paginate(20);
        return view('admin.groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function show(Group $group)
    {
        $group->load('shortKeys');
        $group->loadCount('computers');
        return view('admin.groups.show', compact('group'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        $shortKeys = $this->parseShortKeys($request->input('short_keys', ''));
        $duplicates = $this->checkDuplicateShortKeys($shortKeys);
        if ($duplicates) {
            return redirect()->back()->with('error', $duplicates)->withInput();
        }

        DB::beginTransaction();
        try {
            $group = Group::create($request->only(['name', 'type', 'description']));

            foreach ($shortKeys as $shortKey) {
                if (!empty($shortKey)) {
                    GroupShortKey::create([
                        'group_id' => $group->id,
                        'short_key' => strtoupper(trim($shortKey)),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.groups.index')->with('success', 'Grupo creado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al crear: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Group $group)
    {
        return view('admin.groups.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        $shortKeys = $this->parseShortKeys($request->input('short_keys', ''));
        $duplicates = $this->checkDuplicateShortKeys($shortKeys, $group->id);
        if ($duplicates) {
            return redirect()->back()->with('error', $duplicates)->withInput();
        }

        DB::beginTransaction();
        try {
            $group->update($request->only(['name', 'type', 'description']));

            if ($request->has('short_keys')) {
                $group->shortKeys()->delete();
                
                foreach ($shortKeys as $shortKey) {
                    if (!empty($shortKey)) {
                        GroupShortKey::create([
                            'group_id' => $group->id,
                            'short_key' => strtoupper(trim($shortKey)),
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('admin.groups.index')->with('success', 'Grupo actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Group $group)
    {
        $group->shortKeys()->delete();
        $group->delete();
        return redirect()->route('admin.groups.index')->with('success', 'Grupo eliminado exitosamente');
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $file = $request->file('file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $imported = 0;
            $updated = 0;
            $errors = [];

            array_shift($rows);

            DB::beginTransaction();

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                $name = trim($row[0] ?? '');
                $shortKeysInput = trim($row[1] ?? '');
                $type = trim($row[2] ?? '');
                $description = trim($row[3] ?? '');
                $tiendaIds = trim($row[4] ?? '');

                if (empty($name)) {
                    $errors[] = "Fila {$rowNumber}: Nombre es requerido";
                    continue;
                }

                $group = Group::where('name', $name)->first();

                if ($group) {
                    $group->update([
                        'type' => $type ?: $group->type,
                        'description' => $description ?: $group->description,
                    ]);
                    $updated++;
                } else {
                    $group = Group::create([
                        'name' => $name,
                        'type' => $type ?: null,
                        'description' => $description ?: null,
                    ]);
                    $imported++;
                }

                if (!empty($shortKeysInput)) {
                    $group->shortKeys()->delete();
                    
                    $shortKeys = array_map('trim', explode(',', $shortKeysInput));
                    foreach ($shortKeys as $shortKey) {
                        if (!empty($shortKey)) {
                            GroupShortKey::create([
                                'group_id' => $group->id,
                                'short_key' => strtoupper($shortKey),
                            ]);
                        }
                    }
                }

                if (!empty($tiendaIds)) {
                    $ids = array_map('trim', explode(',', $tiendaIds));
                    foreach ($ids as $tiendaId) {
                        if (is_numeric($tiendaId)) {
                            Computer::where('id', $tiendaId)
                                ->update(['group_id' => $group->id]);
                        }
                    }
                }
            }

            DB::commit();

            $message = "Importación completada: {$imported} grupos creados, {$updated} actualizados";
            if (count($errors) > 0) {
                $message .= ". Errores: " . implode('; ', array_slice($errors, 0, 10));
                if (count($errors) > 10) {
                    $message .= " y " . (count($errors) - 10) . " más...";
                }
            }

            return redirect()->route('admin.groups.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing groups', ['error' => $e->getMessage()]);
            return redirect()->route('admin.groups.index')->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $groups = Group::with('shortKeys')->withCount('computers')->get();

        $csvData = [];
        $csvData[] = ['Nombre', 'Short Keys', 'Tipo', 'Descripción', 'Computadoras'];

        foreach ($groups as $group) {
            $shortKeysStr = implode(', ', $group->shortKeys->pluck('short_key')->toArray());
            $csvData[] = [
                $group->name,
                $shortKeysStr,
                $group->type ?? '',
                $group->description ?? '',
                $group->computers_count,
            ];
        }

        $filename = 'grupos_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $content = chr(239) . chr(187) . chr(191) . $content;

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function parseShortKeys(string $input): array
    {
        if (empty($input)) {
            return [];
        }
        return array_map('trim', explode(',', $input));
    }

    private function checkDuplicateShortKeys(array $shortKeys, ?int $excludeGroupId = null): ?string
    {
        foreach ($shortKeys as $shortKey) {
            if (empty($shortKey)) continue;
            
            $shortKey = strtoupper(trim($shortKey));
            $existing = GroupShortKey::where('short_key', $shortKey);
            
            if ($excludeGroupId) {
                $existing = $existing->where('group_id', '!=', $excludeGroupId);
            }
            
            $found = $existing->first();
            
            if ($found) {
                $group = Group::find($found->group_id);
                $groupName = $group ? $group->name : 'otro grupo';
                return "La short key '{$shortKey}' ya está asignada al grupo '{$groupName}'";
            }
        }
        
        return null;
    }
}
