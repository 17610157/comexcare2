<?php

namespace App\Http\Controllers;

use App\Models\AgentDefaultCategory;
use App\Models\AgentDefaultCategoryFile;
use App\Models\AgentDefaultCategoryRoute;
use App\Models\AgentDefaultDownload;
use App\Models\AgentDefaultRouteAssignment;
use App\Models\Computer;
use App\Models\FileList;
use App\Models\Group;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentDefaultsController extends Controller
{
    public function index()
    {
        $categories = AgentDefaultCategory::withCount('routes')->with('routes.files')->orderBy('id', 'desc')->get()->each(function ($category) {
            $category->files_count = $category->routes->sum(fn ($route) => $route->files->count());
        });

        return view('admin.agent-defaults.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.agent-defaults.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['auto_sync'] = true;
        $validated['auto_validation'] = true;

        $category = AgentDefaultCategory::create($validated);

        return redirect()->route('admin.agent-defaults.show', $category)
            ->with('success', 'Categoría creada exitosamente.');
    }

    public function show(AgentDefaultCategory $category)
    {
        $category->load(['routes.assignments', 'routes.files']);

        $groups = Group::all();
        $computers = Computer::select('id', 'nombre_instalacion', 'short_key', 'plaza')
            ->orderBy('nombre_instalacion')
            ->get();

        // Build sync table: all assigned files per computer, joined with download status
        $allFileIds = $category->routes->pluck('files')->flatten()->pluck('id');
        $downloads = AgentDefaultDownload::whereIn('agent_default_category_file_id', $allFileIds)
            ->get()->keyBy(fn ($d) => $d->computer_id.'-'.$d->agent_default_category_file_id);

        $syncRows = collect();
        foreach ($category->routes as $route) {
            foreach ($route->assignments as $assignment) {
                $targetComputers = collect();
                if ($assignment->assignable_type === 'App\Models\Computer') {
                    $targetComputers->push($assignment->assignable);
                } elseif ($assignment->assignable_type === 'App\Models\Group' && $assignment->assignable) {
                    $targetComputers = Computer::where('group_id', $assignment->assignable->id)
                        ->select('id', 'nombre_instalacion', 'plaza')->get();
                }

                foreach ($targetComputers as $computer) {
                    foreach ($route->files as $file) {
                        $download = $downloads->get($computer->id.'-'.$file->id);
                        $syncRows->push((object) [
                            'nombre_instalacion' => $computer->nombre_instalacion,
                            'plaza' => $computer->plaza ?? '',
                            'ruta_servidor' => $route->route_pattern.'\\'.$file->file_name,
                            'ruta_local' => $download->ruta_local ?? '',
                            'file_name' => $file->file_name,
                            'sync_status' => $download->sync_status ?? 'pending',
                            'server_checksum' => $file->checksum,
                            'local_checksum' => $download->local_checksum ?? '',
                            'synced_at' => $download?->synced_at,
                        ]);
                    }
                }
            }
        }

        return view('admin.agent-defaults.show', compact('category', 'groups', 'computers', 'syncRows'));
    }

    public function edit(AgentDefaultCategory $category)
    {
        return view('admin.agent-defaults.edit', compact('category'));
    }

    public function update(Request $request, AgentDefaultCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);

        return redirect()->route('admin.agent-defaults.show', $category)
            ->with('success', 'Categoría actualizada exitosamente.');
    }

    public function toggleAutoSync(Request $request, AgentDefaultCategory $category)
    {
        $category->update(['auto_sync' => $request->boolean('enabled')]);

        return response()->json([
            'message' => 'Sincronización automática '.($request->boolean('enabled') ? 'activada' : 'desactivada').'.',
            'auto_sync' => $category->fresh()->auto_sync,
        ]);
    }

    public function toggleAutoValidation(Request $request, AgentDefaultCategory $category)
    {
        $category->update(['auto_validation' => $request->boolean('enabled')]);

        return response()->json([
            'message' => 'Validación automática '.($request->boolean('enabled') ? 'activada' : 'desactivada').'.',
            'auto_validation' => $category->fresh()->auto_validation,
        ]);
    }

    public function destroy(AgentDefaultCategory $category)
    {
        $category->delete();

        return redirect()->route('admin.agent-defaults.index')
            ->with('success', 'Categoría eliminada exitosamente.');
    }

    public function storeRoute(Request $request, AgentDefaultCategory $category)
    {
        $request->validate([
            'route_pattern' => 'required|string|max:500',
            'label' => 'nullable|string|max:255',
            'download_path_index' => 'nullable|integer|min:0|max:10',
        ]);

        $maxSort = $category->routes()->max('sort_order') ?? 0;

        $route = $category->routes()->create([
            'route_pattern' => $request->route_pattern,
            'label' => $request->label,
            'download_path_index' => $request->download_path_index,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'message' => 'Ruta agregada exitosamente.',
            'route' => $route->load('assignments'),
        ]);
    }

    public function updateRoute(Request $request, AgentDefaultCategoryRoute $route)
    {
        $request->validate([
            'route_pattern' => 'required|string|max:500',
            'label' => 'nullable|string|max:255',
            'download_path_index' => 'nullable|integer|min:0|max:10',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $route->update($request->only('route_pattern', 'label', 'download_path_index', 'sort_order'));

        return response()->json([
            'message' => 'Ruta actualizada exitosamente.',
            'route' => $route->fresh('assignments'),
        ]);
    }

    public function destroyRoute(AgentDefaultCategoryRoute $route)
    {
        $route->delete();

        return response()->json([
            'message' => 'Ruta eliminada exitosamente.',
        ]);
    }

    public function listAssignments(AgentDefaultCategoryRoute $route)
    {
        $route->load('assignments.assignable');

        return response()->json(['assignments' => $route->assignments]);
    }

    public function storeAssignment(Request $request, AgentDefaultCategoryRoute $route)
    {
        $request->validate([
            'assignable_type' => 'required|in:computer,group',
            'assignable_id' => 'required|integer',
        ]);

        $exists = $route->assignments()
            ->where('assignable_type', $request->assignable_type === 'computer' ? Computer::class : Group::class)
            ->where('assignable_id', $request->assignable_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Esta asignación ya existe.',
            ], 422);
        }

        $assignment = $route->assignments()->create([
            'assignable_type' => $request->assignable_type === 'computer' ? Computer::class : Group::class,
            'assignable_id' => $request->assignable_id,
        ]);

        $assignment->load('assignable');

        return response()->json([
            'message' => 'Asignación agregada exitosamente.',
            'assignment' => $assignment,
        ]);
    }

    public function destroyAssignment(AgentDefaultRouteAssignment $assignment)
    {
        $assignment->delete();

        return response()->json([
            'message' => 'Asignación eliminada exitosamente.',
        ]);
    }

    public function storeFile(Request $request, AgentDefaultCategoryRoute $route)
    {
        $request->validate([
            'file' => 'required|file|max:204800',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        $blacklistRules = FileList::where('type', 'blacklist')->pluck('file_name')->toArray();
        $whitelistRules = FileList::where('type', 'whitelist')->pluck('file_name')->toArray();

        if ($this->matchesFileList($originalName, $blacklistRules)) {
            return response()->json([
                'message' => "El archivo '{$originalName}' está en la blacklist y no puede ser subido.",
            ], 422);
        }

        if (! $this->matchesFileList($originalName, $whitelistRules)) {
            return response()->json([
                'message' => "El archivo '{$originalName}' no está en la whitelist y no puede ser subido.",
            ], 422);
        }

        $categoryId = $route->agent_default_category_id;
        $path = $file->store('agent_defaults/'.$categoryId.'/routes/'.$route->id);
        $checksum = hash_file('sha256', $file->getRealPath());
        $fileSize = $file->getSize();

        $categoryFile = $route->files()->create([
            'file_name' => $originalName,
            'file_path' => $path,
            'checksum' => $checksum,
            'file_size' => $fileSize,
        ]);

        return response()->json([
            'message' => 'Archivo subido exitosamente.',
            'file' => $categoryFile,
        ]);
    }

    public function listFiles(AgentDefaultCategoryRoute $route)
    {
        $files = $route->files()->orderBy('file_name')->get();

        return response()->json(['files' => $files]);
    }

    public function destroyFile(AgentDefaultCategoryRoute $route, AgentDefaultCategoryFile $file)
    {
        Storage::delete($file->file_path);
        $file->delete();

        return response()->json([
            'message' => 'Archivo eliminado exitosamente.',
        ]);
    }

    public function downloadFile(AgentDefaultCategoryRoute $route, AgentDefaultCategoryFile $file)
    {
        if (! Storage::exists($file->file_path)) {
            return response()->json(['message' => 'Archivo no encontrado en el servidor.'], 404);
        }

        return Storage::download($file->file_path, $file->file_name);
    }

    public function syncFiles(Request $request, AgentDefaultCategoryRoute $route)
    {
        $routePattern = $route->route_pattern;

        if (empty($routePattern) || ! is_dir($routePattern)) {
            return response()->json([
                'message' => 'La ruta configurada no es válida o no existe en el servidor.',
            ], 422);
        }

        $files = scandir($routePattern);
        $synced = 0;
        $errors = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $routePattern.DIRECTORY_SEPARATOR.$file;

            if (! is_file($fullPath)) {
                continue;
            }

            $blacklistRules = FileList::where('type', 'blacklist')->pluck('file_name')->toArray();
            $whitelistRules = FileList::where('type', 'whitelist')->pluck('file_name')->toArray();

            if ($this->matchesFileList($file, $blacklistRules)) {
                continue;
            }

            if (! $this->matchesFileList($file, $whitelistRules)) {
                continue;
            }

            $checksum = hash_file('sha256', $fullPath);
            $fileSize = filesize($fullPath);

            $existingFile = $route->files()->where('file_name', $file)->first();

            if ($existingFile) {
                if ($existingFile->checksum !== $checksum) {
                    $existingFile->update([
                        'checksum' => $checksum,
                        'file_size' => $fileSize,
                    ]);
                    $synced++;
                }
            } else {
                $storedPath = Storage::putFileAs(
                    'agent_defaults/'.$route->agent_default_category_id.'/routes/'.$route->id,
                    new File($fullPath),
                    $file
                );

                $route->files()->create([
                    'file_name' => $file,
                    'file_path' => $storedPath,
                    'checksum' => $checksum,
                    'file_size' => $fileSize,
                ]);
                $synced++;
            }
        }

        return response()->json([
            'message' => "Sincronización completada. {$synced} archivo(s) procesado(s).",
            'synced' => $synced,
        ]);
    }

    private function matchesFileList(string $fileName, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, '.')) {
                if (str_ends_with($fileName, $rule)) {
                    return true;
                }
            } elseif ($fileName === $rule) {
                return true;
            }
        }

        return false;
    }
}
