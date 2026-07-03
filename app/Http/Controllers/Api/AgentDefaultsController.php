<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentDefaultCategory;
use App\Models\AgentDefaultCategoryFile;
use App\Models\AgentDefaultDownload;
use App\Models\Computer;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentDefaultsController extends Controller
{
    public function config(int $computerId): JsonResponse
    {
        $computer = Computer::with('group')->findOrFail($computerId);

        $computerId = $computer->id;
        $groupIds = [];

        if ($computer->group) {
            $groupIds[] = $computer->group->id;
            $groupIds = array_merge(
                $groupIds,
                Group::where('id', $computer->group_id)->pluck('id')->toArray()
            );
        }

        $categories = AgentDefaultCategory::with([
            'routes' => function ($query) use ($computerId, $groupIds) {
                $query->whereHas('assignments', function ($q) use ($computerId, $groupIds) {
                    $q->where(function ($q) use ($computerId) {
                        $q->where('assignable_type', Computer::class)
                            ->where('assignable_id', $computerId);
                    })->orWhere(function ($q) use ($groupIds) {
                        $q->where('assignable_type', Group::class)
                            ->whereIn('assignable_id', $groupIds);
                    });
                })->orderBy('sort_order');
            },
            'routes.assignments',
            'routes.files',
        ])->where('is_active', true)->get();

        $result = $categories->map(function ($category) {
            $routes = $category->routes->map(function ($route) {
                $rutaServidor = $route->route_pattern;

                return [
                    'id' => $route->id,
                    'route_pattern' => $route->route_pattern,
                    'label' => $route->label,
                    'download_path_index' => $route->download_path_index ?? 0,
                    'sort_order' => $route->sort_order,
                    'ruta_servidor' => $rutaServidor,
                    'files' => $route->files->map(function ($file) use ($rutaServidor) {
                        return [
                            'id' => $file->id,
                            'file_name' => $file->file_name,
                            'checksum' => $file->checksum,
                            'file_size' => $file->file_size,
                            'ruta_servidor' => $rutaServidor.'\\'.$file->file_name,
                        ];
                    }),
                ];
            });

            return [
                'id' => $category->id,
                'name' => $category->name,
                'auto_sync' => (bool) $category->auto_sync,
                'auto_validation' => (bool) $category->auto_validation,
                'routes' => $routes,
            ];
        });

        return response()->json([
            'categories' => $result,
        ]);
    }

    public function syncStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'computer_id' => 'required|integer|exists:computers,id',
            'files' => 'required|array',
            'files.*.file_id' => 'required|integer|exists:agent_default_category_files,id',
            'files.*.sync_status' => 'required|string|in:synced,different,error,pending',
            'files.*.local_path' => 'nullable|string',
            'files.*.local_checksum' => 'nullable|string',
            'files.*.ruta_local' => 'nullable|string',
            'files.*.ruta_servidor' => 'nullable|string',
        ]);

        $computerId = $validated['computer_id'];
        $results = [];

        foreach ($validated['files'] as $fileStatus) {
            $download = AgentDefaultDownload::updateOrCreate(
                [
                    'computer_id' => $computerId,
                    'agent_default_category_file_id' => $fileStatus['file_id'],
                ],
                [
                    'local_path' => $fileStatus['local_path'] ?? null,
                    'local_checksum' => $fileStatus['local_checksum'] ?? null,
                    'ruta_local' => $fileStatus['ruta_local'] ?? null,
                    'ruta_servidor' => $fileStatus['ruta_servidor'] ?? null,
                    'sync_status' => $fileStatus['sync_status'],
                    'synced_at' => now(),
                    'downloaded_at' => $fileStatus['sync_status'] === 'synced' ? now() : null,
                ]
            );

            $results[] = [
                'file_id' => $fileStatus['file_id'],
                'status' => $download->sync_status,
            ];
        }

        return response()->json([
            'message' => 'Sync status updated',
            'files' => $results,
        ]);
    }

    public function download(Request $request, int $fileId)
    {
        $file = AgentDefaultCategoryFile::findOrFail($fileId);

        if (! Storage::exists($file->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::download($file->file_path, $file->file_name, [
            'X-Checksum' => $file->checksum,
            'X-FileSize' => $file->file_size,
        ]);
    }
}
