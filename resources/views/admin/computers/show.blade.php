@extends('adminlte::page')

@section('title', 'Computer: ' . $computer->computer_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $computer->computer_name }}</h1>
        <div>
            <span class="badge badge-lg {{ $computer->status === 'online' ? 'badge-success' : 'badge-danger' }}">
                {{ $computer->status }}
            </span>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <!-- Computer Info -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-desktop"></i> Computer Info</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="40%">Name:</th><td>{{ $computer->computer_name }}</td></tr>
                        <tr><th>MAC:</th><td>{{ $computer->mac_address }}</td></tr>
                        <tr><th>IP:</th><td>{{ $computer->ip_address }}</td></tr>
                        <tr><th>Agent Version:</th><td>{{ $computer->agent_version ?? 'N/A' }}</td></tr>
                        <tr><th>Group:</th><td>{{ $computer->group->name ?? 'N/A' }}</td></tr>
                        <tr><th>Download Path:</th><td><small>{{ $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files' }}</small></td></tr>
                        <tr><th>Last Seen:</th><td>{{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Never' }}</td></tr>
                        <tr><th>Created:</th><td>{{ $computer->created_at->diffForHumans() }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Active Distributions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-download"></i> Active Distributions</h3>
                </div>
                <div class="card-body">
                    @php
                        $activeDistributions = \App\Models\DistributionTarget::with('distribution')
                            ->where('computer_id', $computer->id)
                            ->whereIn('status', ['pending', 'in_progress'])
                            ->get();
                    @endphp
                    @if($activeDistributions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Distribution</th>
                                        <th>Files</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Last Update</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeDistributions as $target)
                                        <tr>
                                            <td>{{ $target->distribution->name ?? 'N/A' }}</td>
                                            <td>{{ $target->distribution->files->count() ?? 0 }}</td>
                                            <td>
                                                @if($target->status === 'completed')
                                                    <span class="badge badge-success">Completed</span>
                                                @elseif($target->status === 'in_progress')
                                                    <span class="badge badge-primary">In Progress</span>
                                                @else
                                                    <span class="badge badge-warning">{{ $target->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 15px; width: 100px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $target->progress ?? 0 }}%;">
                                                        {{ $target->progress ?? 0 }}%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $target->updated_at ? $target->updated_at->diffForHumans() : 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No active distributions for this computer.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Commands -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-terminal"></i> Recent Commands</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="commandsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Sent At</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($computer->commands->take(20) as $command)
                        <tr>
                            <td>{{ $command->id }}</td>
                            <td>
                                @if($command->type === 'download')
                                    <span class="badge badge-primary"><i class="fas fa-download"></i> Download</span>
                                @elseif($command->type === 'update')
                                    <span class="badge badge-info"><i class="fas fa-sync"></i> Update</span>
                                @else
                                    <span class="badge badge-secondary">{{ $command->type }}</span>
                                @endif
                            </td>
                            <td><small>{{ is_array($command->data) ? json_encode($command->data) : $command->data }}</small></td>
                            <td>
                                @if($command->status === 'pending')
                                    <span class="badge badge-warning">Pending</span>
                                @elseif($command->status === 'sent')
                                    <span class="badge badge-primary">Sent</span>
                                @elseif($command->status === 'completed')
                                    <span class="badge badge-success">Completed</span>
                                @elseif($command->status === 'failed')
                                    <span class="badge badge-danger">Failed</span>
                                @else
                                    {{ $command->status }}
                                @endif
                            </td>
                            <td>{{ $command->sent_at ? $command->sent_at->diffForHumans() : 'Not sent' }}</td>
                            <td>{{ $command->completed_at ? $command->completed_at->diffForHumans() : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Real-time Logs -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Real-time Logs</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" onclick="clearLogs()">
                    <i class="fas fa-trash"></i> Clear
                </button>
                <button type="button" class="btn btn-tool" onclick="toggleAutoScroll()">
                    <i class="fas fa-arrow-down"></i> Auto-scroll
                </label>
                </button>
            </div>
        </div>
        <div class="card-body" style="height: 400px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 12px;" id="logContainer">
            <div id="logsContent">
                <div style="color: #6a9955;">// Waiting for logs...</div>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">Last update: <span id="lastUpdate">Never</span></small>
        </div>
    </div>
@stop

@section('css')
    <style>
        #logContainer pre {
            background: transparent;
            border: none;
            color: inherit;
            margin: 0;
            padding: 0;
        }
        .log-entry { margin: 2px 0; }
        .log-info { color: #4fc1ff; }
        .log-warn { color: #dcdcaa; }
        .log-error { color: #f48771; }
        .log-debug { color: #9cdcfe; }
    </style>
@stop

@section('js')
<script>
let autoScroll = true;
let lastLogId = {{ $computer->logs()->max('id') ?? 0 }};
let computerId = {{ $computer->id }};

function toggleAutoScroll() {
    autoScroll = !autoScroll;
}

function clearLogs() {
    document.getElementById('logsContent').innerHTML = '<div style="color: #6a9955;">// Logs cleared...</div>';
    lastLogId = 0;
}

function appendLog(type, message, timestamp) {
    const container = document.getElementById('logsContent');
    const div = document.createElement('div');
    div.className = 'log-entry log-' + type;
    
    let colorClass = type === 'error' ? 'log-error' : (type === 'warn' ? 'log-warn' : (type === 'debug' ? 'log-debug' : 'log-info'));
    
    div.innerHTML = `<span style="color: #858585;">[${timestamp}]</span> <span class="${colorClass}">${message}</span>`;
    container.appendChild(div);
    
    if (autoScroll) {
        document.getElementById('logContainer').scrollTop = container.scrollHeight;
    }
    
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
}

function fetchLogs() {
    fetch(`/admin/computers/${computerId}/logs?last_id=${lastLogId}`)
        .then(response => response.json())
        .then(data => {
            if (data.logs && data.logs.length > 0) {
                data.logs.forEach(log => {
                    appendLog(log.level, log.message, log.time);
                    lastLogId = log.id;
                });
            }
        })
        .catch(err => console.error('Error fetching logs:', err));
}

// Fetch logs every 1 second
setInterval(fetchLogs, 1000);

// Initial fetch
fetchLogs();

// Also poll for status updates every 1 second
setInterval(function() {
    fetch(`/admin/computers/${computerId}/status`)
        .then(r => r.json())
        .then(data => {
            // Update status badge if changed
            const badge = document.querySelector('.badge-lg');
            if (badge && data.status) {
                badge.textContent = data.status;
                badge.className = 'badge badge-lg ' + (data.status === 'online' ? 'badge-success' : 'badge-danger');
            }
        })
        .catch(() => {});
}, 1000);
</script>
@stop
