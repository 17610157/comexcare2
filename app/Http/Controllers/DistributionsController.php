<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
use App\Models\DistributionTarget;
use App\Models\Group;
use App\Services\DistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DistributionsController extends Controller
{
    public function index()
    {
        $distributions = Distribution::with(['creator', 'files', 'targets.computer'])
            ->orderBy('id', 'desc')
            ->get();
        $groups = Group::all();
        $computers = \App\Models\Computer::select('id', 'computer_name')->orderBy('computer_name')->get();

        return view('admin.distributions.index', compact('distributions', 'groups', 'computers'));
    }

    public function create()
    {
        $groups = Group::all();
        $computers = \App\Models\Computer::select('id', 'computer_name')->orderBy('computer_name')->get();

        return view('admin.distributions.create', compact('groups', 'computers'));
    }

    public function store(Request $request, DistributionService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:immediate,scheduled,recurring',
            'distribution_type' => 'nullable|in:file,update',
            'subfolder' => 'nullable|string|max:255',
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
            'description' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $distribution->update([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'scheduled_at' => $request->scheduled_at,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Distribution updated successfully',
                'distribution' => $distribution->id,
            ]);
        }

        return redirect()->route('admin.distributions.index')->with('success', 'Distribution updated successfully');
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
