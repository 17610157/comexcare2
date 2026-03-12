@extends('adminlte::page')

@section('title', 'Recepción: ' . $reception->name)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ $reception->name }}</h1>
        <a href="{{ route('admin.file-receptions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detalles</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Nombre:</th><td>{{ $reception->name }}</td></tr>
                        <tr><th>Descripción:</th><td>{{ $reception->description ?? 'N/A' }}</td></tr>
                        <tr><th>Tipo:</th>
                            <td>
                                @if($reception->type === 'immediate')
                                    <span class="badge badge-primary">Inmediato</span>
                                @elseif($reception->type === 'scheduled')
                                    <span class="badge badge-warning">Programado</span>
                                @else
                                    <span class="badge badge-info">Recurrente</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>Estado:</th>
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
                        </tr>
                        <tr><th>Programado:</th>
                            <td>
                                @if($reception->scheduled_at)
                                    {{ $reception->scheduled_at->format('d/m/Y H:i') }}
                                @elseif($reception->recurrence)
                                    {{ $reception->recurrence }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr><th>Grupo:</th><td>{{ $reception->group->name ?? 'Todos' }}</td></tr>
                        <tr><th>Creado:</th><td>{{ $reception->created_at->format('d/m/Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Progreso</h3>
                </div>
                <div class="card-body">
                    @php
                        $completed = $reception->targets->where('status', 'completed')->count();
                        $total = $reception->targets->count();
                        $percent = $total > 0 ? round(($completed / $total) * 100) : 0;
                    @endphp
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar" role="progressbar" style="width: {{ $percent }}%;">
                            {{ $percent }}%
                        </div>
                    </div>
                    <p class="mt-2">{{ $completed }} / {{ $total }} completado(s)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">Objetivos</h3>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Computadora</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                        <th>Última Actualización</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reception->targets as $target)
                        <tr>
                            <td>{{ $target->computer->computer_name ?? 'N/A' }}</td>
                            <td>
                                @if($target->status === 'completed')
                                    <span class="badge badge-success">Completado</span>
                                @elseif($target->status === 'in_progress')
                                    <span class="badge badge-primary">En Progreso</span>
                                @elseif($target->status === 'failed')
                                    <span class="badge badge-danger">Fallido</span>
                                @else
                                    <span class="badge badge-warning">Pendiente</span>
                                @endif
                            </td>
                            <td>
                                <div class="progress" style="height: 15px; width: 100px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $target->progress }}%;">
                                    </div>
                                </div>
                                {{ $target->progress }}%
                            </td>
                            <td>{{ $target->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop
