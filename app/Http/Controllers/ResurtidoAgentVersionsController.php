<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ResurtidoAgentVersion;
use App\Services\ResurtidoAgentUpdateService;
use Illuminate\Http\Request;

class ResurtidoAgentVersionsController extends Controller
{
    public function index()
    {
        $versions = ResurtidoAgentVersion::orderBy('created_at', 'desc')->paginate(20);

        // Get computers without resurtido version or with old version
        $latestVersion = ResurtidoAgentVersion::active()->orderBy('created_at', 'desc')->first();
        $computersWithoutUpdate = Computer::whereNull('resurtido_agent_version')
            ->orWhere('resurtido_agent_version', '')
            ->orWhere('resurtido_agent_version', '<>', $latestVersion?->version)
            ->count();

        // Get unique plazas for the deploy modal
        $plazas = Computer::whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->distinct()
            ->pluck('plaza')
            ->sort()
            ->values();

        return view('admin.resurtido-agent-versions.index', compact('versions', 'computersWithoutUpdate', 'plazas'));
    }

    public function create()
    {
        return view('admin.resurtido-agent-versions.create');
    }

    public function store(Request $request, ResurtidoAgentUpdateService $service)
    {
        $request->validate([
            'version' => 'required|string',
            'channel' => 'required|in:stable,beta,alpha',
            'file' => 'required|file|max:51200',
        ]);

        $service->createVersion($request->all());

        return redirect()->route('admin.resurtido-agent-versions.index');
    }

    public function destroy(ResurtidoAgentVersion $resurtidoAgentVersion, ResurtidoAgentUpdateService $service)
    {
        $service->deactivateVersion($resurtidoAgentVersion);

        return redirect()->route('admin.resurtido-agent-versions.index');
    }

    public function deploy(Request $request, ResurtidoAgentVersion $version, ResurtidoAgentUpdateService $service)
    {
        $request->validate([
            'computer_ids' => 'required|array',
            'computer_ids.*' => 'exists:computers,id',
        ]);

        $computers = Computer::whereIn('id', $request->computer_ids)->get();

        foreach ($computers as $computer) {
            $service->deployUpdate($computer, $version);
        }

        return redirect()->route('admin.resurtido-agent-versions.index')
            ->with('success', 'Update deployed to '.$computers->count().' computers');
    }
}
