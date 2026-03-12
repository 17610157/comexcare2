@extends('adminlte::page')

@section('title', 'Recepción de Archivos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-upload"></i> Recepción de Archivos</h1>
        <a href="{{ route('admin.file-receptions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Recepción
        </a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
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
                                <a href="{{ route('admin.file-receptions.show', $reception) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form action="{{ route('admin.file-receptions.destroy', $reception) }}" method="POST" style="display:inline;">
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
        </div>
    </div>
@stop
