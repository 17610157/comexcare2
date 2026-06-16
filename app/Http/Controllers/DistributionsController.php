<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Distribution;
use App\Models\DistributionTarget;
use App\Models\Group;
use App\Services\DistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributionsController extends Controller
{
    public function index()
    {
        $distributions = Distribution::with(['creator', 'files', 'targets.computer'])
            ->orderBy('id', 'desc')
            ->get();
        $groups = Group::all();
        $computers = Computer::select('id', 'computer_name', 'short_key')->orderBy('computer_name')->get();

        return view('admin.distributions.index', compact('distributions', 'groups', 'computers'));
    }

    public function create()
    {
        $groups = Group::all();
        $computers = Computer::select('id', 'computer_name', 'short_key')->orderBy('computer_name')->get();

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
        $distribution->load('files', 'targets.computer');

        return view('admin.distributions.show', compact('distribution'));
    }

    public function destroy(Distribution $distribution)
    {
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
        ]);

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
        try {
            DB::beginTransaction();

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

            $distribution->targets()->delete();

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

            foreach ($computerList as $computer) {
                DistributionTarget::create([
                    'distribution_id' => $distribution->id,
                    'computer_id' => $computer->id,
                ]);
            }

            $distribution->update([
                'status' => 'pending',
                'scheduled_at' => now(),
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
                'computer_name' => $target->computer->computer_name ?? 'Unknown',
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
