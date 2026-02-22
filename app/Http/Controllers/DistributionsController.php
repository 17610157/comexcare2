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
        $distributions = Distribution::with('creator')->paginate(20);
        return view('admin.distributions.index', compact('distributions'));
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

        return redirect()->route('distributions.index')->with('success', 'Distribution created successfully');
    }

    public function show(Distribution $distribution)
    {
        $distribution->load('files', 'targets.computer');
        return view('admin.distributions.show', compact('distribution'));
    }

    public function destroy(Distribution $distribution)
    {
        $distribution->delete();
        return redirect()->route('distributions.index')->with('success', 'Distribution deleted');
    }
}