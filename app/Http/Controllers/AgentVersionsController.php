<?php

namespace App\Http\Controllers;

use App\Models\AgentVersion;
use App\Models\Computer;
use App\Models\Group;
use App\Services\AgentUpdateService;
use Illuminate\Http\Request;

class AgentVersionsController extends Controller
{
    public function index()
    {
        $versions = AgentVersion::orderBy('created_at', 'desc')->paginate(20);

        $groups = Group::orderBy('name')->get();

        $plazas = Computer::whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->distinct()
            ->pluck('plaza')
            ->sort()
            ->values();

        $computersWithoutUpdate = Computer::whereNull('agent_version')
            ->orWhere('agent_version', '')
            ->count();

        $computers = Computer::orderBy('nombre_instalacion')
            ->select('id', 'nombre_instalacion', 'short_key', 'plaza', 'group_id')
            ->get();

        return view('admin.agent-versions.index', compact('versions', 'groups', 'plazas', 'computersWithoutUpdate', 'computers'));
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
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:51200',
            'changelog' => 'nullable|string',
        ]);

        $service->createVersion($request->all());

        return redirect()->route('admin.agent-versions.index');
    }

    public function activate(AgentVersion $agentVersion, AgentUpdateService $service)
    {
        $service->activateVersion($agentVersion);

        return redirect()->route('admin.agent-versions.index')
            ->with('success', 'Versión '.$agentVersion->version.' activada correctamente.');
    }

    public function destroy(AgentVersion $agentVersion, AgentUpdateService $service)
    {
        $service->deactivateVersion($agentVersion);

        return redirect()->route('admin.agent-versions.index');
    }

    public function forceDelete(AgentVersion $agentVersion, AgentUpdateService $service)
    {
        $service->forceDelete($agentVersion);

        return redirect()->route('admin.agent-versions.index')
            ->with('success', 'Versión '.$agentVersion->version.' eliminada permanentemente.');
    }

    public function deploy(Request $request, AgentVersion $agentVersion, AgentUpdateService $service)
    {
        $request->validate([
            'deploy_target' => 'required|in:group,store,computers,all',
            'group_id' => 'required_if:deploy_target,group|nullable|exists:groups,id',
            'plaza' => 'required_if:deploy_target,store|nullable|string',
            'computer_ids' => 'required_if:deploy_target,computers|nullable|array',
            'computer_ids.*' => 'exists:computers,id',
        ]);

        $query = Computer::query();

        switch ($request->deploy_target) {
            case 'group':
                $query->where('group_id', $request->group_id);
                break;
            case 'store':
                $query->where('plaza', $request->plaza);
                break;
            case 'computers':
                $query->whereIn('id', $request->computer_ids ?? []);
                break;
            case 'all':
                break;
        }

        $computers = $query->get();

        if ($computers->isEmpty()) {
            return redirect()->route('admin.agent-versions.index')
                ->with('error', 'No se encontraron computadoras para el destino seleccionado.');
        }

        foreach ($computers as $computer) {
            $service->deployUpdate($computer, $agentVersion);
        }

        return redirect()->route('admin.agent-versions.index')
            ->with('success', 'Actualización desplegada a '.$computers->count().' computadora(s).');
    }
}
