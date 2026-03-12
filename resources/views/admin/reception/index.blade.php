@extends('adminlte::page')

@section('title', 'Recepción de Información')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-upload"></i> Recepción de Información</h1>
        <a href="{{ route('admin.reception.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Recepción
        </a>
    </div>
@stop

@section('content')
    <!-- Lista de Recepciones Creadas -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Recepciones Creadas</h3>
        </div>
        <div class="card-body">
            @if(isset($receptions) && $receptions->count() > 0)
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Objetivos</th>
                            <th>Programado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receptions as $reception)
                            <tr>
                                <td>{{ $reception->id }}</td>
                                <td>{{ $reception->name }}</td>
                                <td>
                                    @if($reception->type === 'immediate')
                                        <span class="badge badge-primary">Inmediato</span>
                                    @elseif($reception->type === 'scheduled')
                                        <span class="badge badge-warning">Programado</span>
                                    @else
                                        <span class="badge badge-info">Recurrente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($reception->status === 'completed')
                                        <span class="badge badge-success">Completado</span>
                                    @elseif($reception->status === 'in_progress')
                                        <span class="badge badge-primary">En Progreso</span>
                                    @elseif($reception->status === 'failed')
                                        <span class="badge badge-danger">Fallido</span>
                                    @else
                                        <span class="badge badge-secondary">Pendiente</span>
                                    @endif
                                </td>
                                <td>{{ $reception->targets->count() }}</td>
                                <td>
                                    @if($reception->scheduled_at)
                                        {{ $reception->scheduled_at->format('d/m/Y H:i') }}
                                    @elseif($reception->recurrence)
                                        {{ $reception->recurrence }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.reception.show', $reception) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form action="{{ route('admin.reception.destroy', $reception) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $receptions->links() }}
            @else
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> No hay recepciones creadas. Crea una nueva para solicitar archivos a los agentes.
                </div>
            @endif
        </div>
    </div>

    <!-- Agentes con rutas de recepción configuradas -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-desktop"></i> Agentes con rutas de recepción</h3>
        </div>
        <div class="card-body">
            @php
                $computers = \App\Models\Computer::with('group')->whereNotNull('receive_paths')->get();
            @endphp
            @if($computers->count() > 0)
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Computadora</th>
                            <th>Short Key</th>
                            <th>Grupo</th>
                            <th>Rutas de Recepción</th>
                            <th>Última Comunicación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($computers as $computer)
                            <tr>
                                <td>
                                    <i class="fas fa-desktop"></i> {{ $computer->computer_name }}
                                    <br><small class="text-muted">{{ $computer->mac_address }}</small>
                                </td>
                                <td>
                                    @if($computer->short_key)
                                        <span class="badge badge-info">{{ $computer->short_key }}</span>
                                    @else
                                        <span class="text-muted">Sin configurar</span>
                                    @endif
                                </td>
                                <td>{{ $computer->group->name ?? 'Sin grupo' }}</td>
                                <td>
                                    @php
                                        $receivePaths = $computer->receive_paths ?? [];
                                    @endphp
                                    @if(count($receivePaths) > 0)
                                        <ul class="list-unstyled mb-0">
                                            @foreach($receivePaths as $path)
                                                <li><small><code>{{ $path['local_path'] ?? 'N/A' }}</code></small></li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">Sin rutas</span>
                                    @endif
                                </td>
                                <td>{{ $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Nunca' }}</td>
                                <td>
                                    <a href="{{ route('admin.reception.computer', $computer) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Ver Archivos
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i> No hay agentes con rutas de recepción configuradas.
                </div>
            @endif
        </div>
    </div>
@stop
