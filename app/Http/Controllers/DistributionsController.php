<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Distribution;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use App\Models\FileList;
use App\Models\Group;
use App\Services\DistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DistributionsController extends Controller
{
    public function index()
    {
        $query = Distribution::with(['creator', 'files', 'targets.computer'])
            ->orderBy('id', 'desc');

        if (! Auth::user()->hasPermissionTo('distribution.ver_todas')) {
            $query->where('created_by', Auth::id());
        }

        $distributions = $query->get();
        $groups = Group::all();
        $computers = Computer::select('id', 'nombre_instalacion', 'short_key')->orderBy('nombre_instalacion')->get();

        return view('admin.distributions.index', compact('distributions', 'groups', 'computers'));
    }

    public function create()
    {
        $groups = Group::all();
        $computers = Computer::select('id', 'nombre_instalacion', 'short_key')->orderBy('nombre_instalacion')->get();

        return view('admin.distributions.create', compact('groups', 'computers'));
    }

    public function store(Request $request, DistributionService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:immediate,scheduled,recurring',
            'distribution_type' => 'nullable|in:file,update,command',
            'subfolder' => 'nullable|string|max:255',
            'command' => 'nullable|string|max:500',
            'command_args' => 'nullable|string|max:500',
            'files' => 'nullable|array',
            'files.*' => 'file|max:204800', // 200MB
            'target_type' => 'required|in:all,group,specific',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
            'computer_ids' => 'nullable|array',
            'computer_ids.*' => 'exists:computers,id',
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => 'nullable',
            'recurrence' => 'nullable',
            'frequency_interval' => 'nullable|integer',
            'week_days' => 'nullable|array',
        ]);

        if ($request->hasFile('files')) {
            $fileNames = [];
            foreach ($request->file('files') as $file) {
                $fileNames[] = $file->getClientOriginalName();
            }

            $blacklistRules = FileList::where('type', 'blacklist')->pluck('file_name')->toArray();
            $whitelistRules = FileList::where('type', 'whitelist')->pluck('file_name')->toArray();

            $blocked = [];
            foreach ($fileNames as $fileName) {
                if ($this->matchesFileList($fileName, $blacklistRules)) {
                    $blocked[] = $fileName;
                }
            }

            if (! empty($blocked)) {
                return response()->json([
                    'message' => 'Los siguientes archivos están en la blacklist y no pueden ser enviados: '.implode(', ', $blocked),
                ], 422);
            }

            $notAllowed = [];
            foreach ($fileNames as $fileName) {
                if (! $this->matchesFileList($fileName, $whitelistRules)) {
                    $notAllowed[] = $fileName;
                }
            }

            if (! empty($notAllowed)) {
                return response()->json([
                    'message' => 'Los siguientes archivos no están en la whitelist y no pueden ser enviados: '.implode(', ', $notAllowed),
                ], 422);
            }
        }

        $distribution = $service->createDistribution($request->all(), Auth::id());

        if ($request->type === 'immediate') {
            $service->startDistribution($distribution);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Distribution created successfully',
                'distribution' => $distribution->id,
            ]);
        }

        return redirect()->route('admin.distributions.index')->with('success', 'Distribution created successfully');
    }

    public function show(Distribution $distribution)
    {
        $this->authorizeDistributionAccess($distribution);

        $distribution->load('files', 'targets.computer');

        return view('admin.distributions.show', compact('distribution'));
    }

    public function destroy(Distribution $distribution)
    {
        $this->authorizeDistributionAccess($distribution);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'endpoint' => request()->fullUrl(),
            'method' => 'DELETE',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_data' => [
                'distribution_id' => $distribution->id,
                'distribution_name' => $distribution->name,
                'distribution_type' => $distribution->type,
            ],
            'response_code' => 200,
            'duration_ms' => 0,
            'created_at' => now(),
        ]);

        $distribution->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Distribution deleted successfully',
            ]);
        }

        return redirect()->route('admin.distributions.index')->with('success', 'Distribution deleted');
    }

    public function stop(Distribution $distribution)
    {
        $this->authorizeDistributionAccess($distribution);

        $distribution->update(['status' => 'stopped']);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Distribution stopped successfully',
            ]);
        }

        return redirect()->route('admin.distributions.index')->with('success', 'Distribution stopped. Ya no se enviarán más comandos.');
    }

    public function start(Distribution $distribution, DistributionService $service)
    {
        $this->authorizeDistributionAccess($distribution);

        $distribution->update(['status' => 'pending']);

        $service->startDistribution($distribution);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Distribution started successfully',
            ]);
        }

        return redirect()->route('admin.distributions.index')->with('success', 'Distribution iniciada correctamente.');
    }

    public function retryTarget(DistributionTarget $target)
    {
        $this->authorizeDistributionAccess($target->distribution);

        $target->update([
            'status' => 'pending',
            'error_message' => null,
            'attempts' => 0,
            'next_retry_at' => null,
        ]);

        $service = new DistributionService;
        $service->sendDownloadCommand($target);

        return redirect()->back()->with('success', 'Comando reenviado correctamente.');
    }

    public function update(Request $request, Distribution $distribution)
    {
        $this->authorizeDistributionAccess($distribution);

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:immediate,scheduled,recurring',
            'distribution_type' => 'nullable|in:file,update,command',
            'subfolder' => 'nullable|string|max:255',
            'command' => 'nullable|string|max:500',
            'command_args' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'scheduled_time' => 'nullable',
            'recurrence' => 'nullable',
            'frequency_interval' => 'nullable|integer',
            'week_days' => 'nullable|array',
            'files' => 'nullable|array',
            'files.*' => 'file|max:204800',
        ]);

        try {
            $this->handleFileUploads($request, $distribution);
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        $distribution->update([
            'name' => $request->name,
            'type' => $request->type,
            'distribution_type' => $request->distribution_type,
            'subfolder' => $request->subfolder,
            'command' => $request->command,
            'command_args' => $request->command_args,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
            'scheduled_time' => $request->scheduled_time,
            'recurrence' => $request->recurrence,
            'frequency_interval' => $request->frequency_interval,
            'week_days' => $request->week_days,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Distribution updated successfully',
                'distribution' => $distribution->id,
            ]);
        }

        return redirect()->route('admin.distributions.index')->with('success', 'Distribution updated successfully');
    }

    public function restart(Distribution $distribution, Request $request, DistributionService $service)
    {
        $this->authorizeDistributionAccess($distribution);

        try {
            DB::beginTransaction();

            $this->handleFileUploads($request, $distribution);

            $distribution->update([
                'name' => $request->name,
                'type' => $request->type,
                'distribution_type' => $request->distribution_type,
                'subfolder' => $request->subfolder,
                'command' => $request->command,
                'command_args' => $request->command_args,
                'description' => $request->description,
                'scheduled_at' => $request->scheduled_at ?? $distribution->scheduled_at,
                'scheduled_time' => $request->scheduled_time,
                'recurrence' => $request->recurrence,
                'frequency_interval' => $request->frequency_interval,
                'week_days' => $request->week_days,
            ]);

            $targetType = $request->input('target_type', 'all');
            $groupIds = $request->input('group_ids', []);
            $computerIds = $request->input('computer_ids', []);

            $computers = Computer::query();

            if ($targetType === 'group' && ! empty($groupIds)) {
                $computers->whereIn('group_id', $groupIds);
            } elseif ($targetType === 'specific' && ! empty($computerIds)) {
                $computers->whereIn('id', $computerIds);
            } elseif ($targetType === 'all') {
                // no filter -- all computers
            } else {
                $computers->whereRaw('1 = 0'); // empty set
            }

            $computerList = $computers->get();
            $computerIds = $computerList->pluck('id')->toArray();

            // Cancelar comandos previos pendientes/envidos para estas computadoras
            Command::whereIn('computer_id', $computerIds)
                ->where('status', 'pending')
                ->orWhere(function ($query) use ($computerIds) {
                    $query->whereIn('computer_id', $computerIds)
                        ->where('status', 'sent');
                })
                ->update(['status' => 'cancelled', 'response' => 'Cancelled by distribution restart']);

            // Eliminar targets anteriores y recrearlos
            $distribution->targets()->delete();

            foreach ($computerList as $computer) {
                DistributionTarget::create([
                    'distribution_id' => $distribution->id,
                    'computer_id' => $computer->id,
                ]);
            }

            $distribution->update([
                'status' => 'pending',
            ]);

            if ($distribution->type === 'immediate') {
                $service->startDistribution($distribution);
            }

            DB::commit();

            return response()->json([
                'message' => 'Distribución reiniciada correctamente',
                'distribution' => $distribution->id,
                'targets_count' => $computerList->count(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al reiniciar distribución: '.$e->getMessage(), [
                'distribution_id' => $distribution->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error al reiniciar distribución: '.$e->getMessage(),
            ], 500);
        }
    }

    private function authorizeDistributionAccess(Distribution $distribution): void
    {
        if (! Auth::user()->hasPermissionTo('distribution.ver_todas') && $distribution->created_by !== Auth::id()) {
            abort(403, 'No tiene permiso para acceder a esta distribución.');
        }
    }

    private function handleFileUploads(Request $request, Distribution $distribution): void
    {
        if (! $request->hasFile('files')) {
            return;
        }

        // Validar archivos contra file lists
        $fileNames = [];
        foreach ($request->file('files') as $file) {
            $fileNames[] = $file->getClientOriginalName();
        }

        $blacklistRules = FileList::where('type', 'blacklist')->pluck('file_name')->toArray();
        $whitelistRules = FileList::where('type', 'whitelist')->pluck('file_name')->toArray();

        $blocked = [];
        foreach ($fileNames as $fileName) {
            if ($this->matchesFileList($fileName, $blacklistRules)) {
                $blocked[] = $fileName;
            }
        }

        if (! empty($blocked)) {
            throw new \RuntimeException('Los siguientes archivos están en la blacklist: '.implode(', ', $blocked));
        }

        $notAllowed = [];
        foreach ($fileNames as $fileName) {
            if (! $this->matchesFileList($fileName, $whitelistRules)) {
                $notAllowed[] = $fileName;
            }
        }

        if (! empty($notAllowed)) {
            throw new \RuntimeException('Los siguientes archivos no están en la whitelist: '.implode(', ', $notAllowed));
        }

        // Eliminar archivos viejos de la distribucion
        foreach ($distribution->files as $oldFile) {
            if (Storage::disk('public')->exists($oldFile->file_path)) {
                Storage::disk('public')->delete($oldFile->file_path);
            }
            $oldFile->delete();
        }

        // Guardar archivos nuevos
        foreach ($request->file('files') as $file) {
            $path = $file->store('distributions', 'public');
            DistributionFile::create([
                'distribution_id' => $distribution->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'checksum' => hash_file('sha256', Storage::disk('public')->path($path)),
                'file_size' => $file->getSize(),
            ]);
        }
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

    public function progress($id)
    {
        $distribution = Distribution::with(['targets.computer', 'files'])->findOrFail($id);

        $targets = $distribution->targets;
        $completed = $targets->where('status', 'completed')->count();
        $failed = $targets->where('status', 'failed')->count();
        $inProgress = $targets->where('status', 'in_progress')->count();
        $pending = $targets->where('status', 'pending')->count();
        $total = $targets->count();

        $targetsData = $targets->map(function ($target) {
            return [
                'id' => $target->id,
                'computer_name' => $target->computer->nombre_instalacion ?? 'Unknown',
                'status' => $target->status,
                'progress' => $target->progress ?? 0,
                'error_message' => $target->error_message,
                'updated_at' => $target->updated_at ? $target->updated_at->toISOString() : null,
            ];
        });

        return response()->json([
            'id' => $distribution->id,
            'status' => $distribution->status,
            'completed' => $completed,
            'failed' => $failed,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'total' => $total,
            'percent' => $total > 0 ? round(($completed / $total) * 100) : 0,
            'targets' => $targetsData,
        ]);
    }
}
