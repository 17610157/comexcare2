<?php

namespace App\Http\Controllers;

use App\Models\AgentVersion;
use App\Services\AgentUpdateService;
use Illuminate\Http\Request;

class AgentVersionsController extends Controller
{
    public function index()
    {
        $versions = AgentVersion::orderBy('created_at', 'desc')->paginate(20);
        return view('admin.agent-versions.index', compact('versions'));
    }

    public function create()
    {
        return view('admin.agent-versions.create');
    }

    public function store(Request $request, AgentUpdateService $service)
    {
        $request->validate([
            'version' => 'required|string',
            'channel' => 'required|in:stable,beta,alpha',
            'file' => 'required|file|max:51200', // 50MB
            'changelog' => 'nullable|string',
        ]);

        $service->createVersion($request->all());

        return redirect()->route('admin.agent-versions.index');
    }

    public function destroy(AgentVersion $agentVersion, AgentUpdateService $service)
    {
        $service->deactivateVersion($agentVersion);
        return redirect()->route('admin.agent-versions.index');
    }
}