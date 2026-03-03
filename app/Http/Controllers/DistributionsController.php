<?php

namespace App\Http\Controllers;

use App\Models\Distribution;
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

        return view('admin.distributions.create', compact('groups'));
    }

    public function store(Request $request, DistributionService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:immediate,scheduled,recurring',
            'files' => 'nullable|array',
            'files.*' => 'file|max:204800', // 200MB
            'target_type' => 'required|in:all,group,specific',
            'group_id' => 'nullable|exists:groups,id',
            'targets' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
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
}
