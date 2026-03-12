@extends('adminlte::page')

@section('title', 'Recepción: ' . $computer->computer_name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-upload"></i> Recepción: {{ $computer->computer_name }}</h1>
        <a href="{{ route('admin.reception.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <!-- Computer Info -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-desktop"></i> Información del Agente</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th width="40%">Nombre:</th><td>{{ $computer->computer_name }}</td></tr>
                        <tr><th>Short Key:</th>
                            <td>
                                @if($computer->short_key)
                                    <span class="badge badge-info">{{ $computer->short_key }}</span>
                                @else
                                    <span class="text-muted">Sin configurar</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>MAC:</th><td>{{ $computer->mac_address }}</td></tr>
                        <tr><th>IP:</th><td>{{ $computer->ip_address }}</td></tr>
                        <tr><th>Última Comunicación:</th><td>{{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Nunca' }}</td></tr>
                        <tr><th>Estado:</th>
                            <td>
                                <span class="badge badge-{{ $computer->status === 'online' ? 'success' : 'danger' }}">
                                    {{ $computer->status }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Reception Paths Summary -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-folder"></i> Rutas de Recepción</h3>
                </div>
                <div class="card-body">
                    @if(count($receptionData) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ruta Local (Agente)</th>
                                        <th>Carpeta</th>
                                        <th>Ruta Servidor</th>
                                        <th>Archivos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($receptionData as $index => $data)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $data['local_path'] }}</code></td>
                                            <td><strong>{{ $data['folder_name'] }}</strong></td>
                                            <td><small>{{ $data['server_path'] }}</small></td>
                                            <td>
                                                <span class="badge badge-primary">{{ count($data['files']) }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No hay rutas de recepción configuradas.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Files in each reception path -->
    @foreach($receptionData as $index => $data)
        <div class="card mt-3">
            <div class="card-header bg-info">
                <h3 class="card-title">
                    <i class="fas fa-folder-open"></i> 
                    {{ $data['folder_name'] }} - Archivos en Servidor
                </h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Ruta Local (Agente):</strong> <code>{{ $data['local_path'] }}</code>
                    </div>
                    <div class="col-md-6">
                        <strong>Ruta Servidor:</strong> <code>{{ $data['server_path'] }}</code>
                    </div>
                </div>
                
                @if(count($data['files']) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Tamaño</th>
                                    <th>Fecha Modificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['files'] as $file)
                                    <tr>
                                        <td>
                                            @if($file['is_directory'])
                                                <i class="fas fa-folder text-warning"></i> 
                                            @else
                                                <i class="fas fa-file text-info"></i> 
                                            @endif
                                            {{ $file['name'] }}
                                        </td>
                                        <td>{{ $file['is_directory'] ? 'Carpeta' : 'Archivo' }}</td>
                                        <td>
                                            @if(!$file['is_directory'])
                                                {{ number_format($file['size'] / 1024, 2) }} KB
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ date('d/m/Y H:i:s', $file['modified']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> No hay archivos en esta ruta del servidor.
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@stop
