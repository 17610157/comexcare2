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
    <!-- Computer Info -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-desktop"></i> Información de la Computadora</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="40%">Nombre:</th><td>{{ $computer->computer_name }}</td></tr>
                        @if($computer->short_key)
                        <tr><th>Short Key:</th><td><span class="badge badge-info">{{ $computer->short_key }}</span></td></tr>
                        @endif
                        <tr><th>MAC:</th><td>{{ $computer->mac_address }}</td></tr>
                        <tr><th>IP:</th><td>{{ $computer->ip_address }}</td></tr>
                        <tr><th>Versión Agente:</th><td>{{ $computer->agent_version ?? 'N/A' }}</td></tr>
                        <tr><th>Grupo:</th><td>{{ $computer->group->name ?? 'N/A' }}</td></tr>
                        <tr><th>Ruta de Descarga:</th><td><small>{{ $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files' }}</small></td></tr>
                        @php $additionalPaths = array_slice($computer->getAllDownloadPaths(), 1); @endphp
                        @if(count($additionalPaths) > 0)
                        <tr>
                            <th>Rutas Adicionales:</th>
                            <td>
                                @foreach($additionalPaths as $idx => $path)
                                    <div><small>{{ $idx + 2 }}. {{ $path }}</small></div>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        <tr><th>Última Comunicación:</th><td>{{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Nunca' }}</td></tr>
                        <tr><th>Creado:</th><td>{{ $computer->created_at->diffForHumans() }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Archivos DBF/CDX/FPT - Rutas de Recepción -->
    @php
        $receivePaths = $computer->receive_paths ?? [];
        $receptionFiles = [];
        
        foreach ($receivePaths as $path) {
            $shortKey = $computer->short_key ?? 'NO_KEY';
            $folderName = $path['folder_name'] ?? basename($path['local_path'] ?? '');
            $serverPath = storage_path('app/distributions/' . $shortKey . '/' . $folderName);
            
            if (is_dir($serverPath)) {
                $items = scandir($serverPath);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    
                    $fullPath = $serverPath . '/' . $item;
                    $isDir = is_dir($fullPath);
                    
                    $receptionFiles[] = [
                        'name' => $item,
                        'path' => $fullPath,
                        'local_path' => $path['local_path'] ?? '',
                        'folder_name' => $folderName,
                        'size' => $isDir ? 0 : filesize($fullPath),
                        'modified' => filemtime($fullPath),
                        'is_directory' => $isDir,
                    ];
                }
            }
        }
    @endphp
    @if(count($receptionFiles) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-upload"></i> Archivos DBF/CDX/FPT (Rutas de Recepción)</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Carpeta Servidor</th>
                                <th>Ruta Local Agente</th>
                                <th>Peso</th>
                                <th>Fecha Modificación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receptionFiles as $file)
                            <tr>
                                <td>
                                    @if($file['is_directory'])
                                        <i class="fas fa-folder text-warning"></i>
                                    @else
                                        <i class="fas fa-file text-info"></i>
                                    @endif
                                    <strong>{{ $file['name'] }}</strong>
                                </td>
                                <td><small>{{ $computer->short_key }}/{{ $file['folder_name'] }}</small></td>
                                <td><small>{{ $file['local_path'] }}</small></td>
                                <td>{{ $file['is_directory'] ? '-' : number_format($file['size'] / 1024, 2) . ' KB' }}</td>
                                <td>{{ date('d/m/Y H:i', $file['modified']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @else
    @if(count($receivePaths) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-upload"></i> Archivos DBF/CDX/FPT (Rutas de Recepción)</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle"></i> 
                        No hay archivos en las rutas de recepción del servidor.<br>
                        <small>Rutas configuradas:</small>
                        @foreach($receivePaths as $path)
                            <br><code>{{ $path['local_path'] ?? '' }}</code> → <code>{{ $computer->short_key }}/{{ $path['folder_name'] ?? '' }}</code>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- Real-time Logs -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Logs en Tiempo Real</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" onclick="clearLogs()">
                    <i class="fas fa-trash"></i> Limpiar
                </button>
                <button type="button" class="btn btn-tool" onclick="toggleAutoScroll()">
                    <i class="fas fa-arrow-down"></i> Auto-scroll
                </button>
            </div>
        </div>
        <div class="card-body" style="height: 500px; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; font-family: 'Courier New', monospace; font-size: 12px;" id="logContainer">
            <div id="logsContent">
                <div style="color: #6a9955;">// Esperando logs...</div>
            </div>
        </div>
        <div class="card-footer">
            <small class="text-muted">Última actualización: <span id="lastUpdate">Nunca</span></small>
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
let lastLogId = {{ $lastLogId ?? 0 }};
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
