@extends('adminlte::page')

@section('title', 'Versiones de Agente')

@section('content_header')
    <h1>Versiones de Agente</h1>
@stop

@section('content')
    <div class="card mt-3">
        <div class="card-header">
            <a href="{{ route('admin.agent-versions.create') }}" class="btn btn-primary">Crear Versión</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
<th>Versión</th>
                         <th>Canal</th>
                         <th>Archivos</th>
                         <th>Activo</th>
                         <th>Creado</th>
                         <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($versions as $version)
                        <tr>
                            <td><strong>{{ $version->version }}</strong></td>
                            <td>
                                @if($version->channel === 'stable')
                                    <span class="badge badge-success">Estable</span>
                                @elseif($version->channel === 'beta')
                                    <span class="badge badge-warning">Beta</span>
                                @else
                                    <span class="badge badge-secondary">Alfa</span>
                                @endif
                            </td>
                            <td>
                                @php $files = $version->files; @endphp
                                @if(!empty($files))
                                    <small class="text-muted">{{ count($files) }} archivos:</small>
                                    <ul class="mb-0 pl-3">
                                        @foreach($files as $file)
                                            <li class="text-small">{{ $file['name'] ?? 'N/A' }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">Sin archivos</span>
                                @endif
                            </td>
                            <td>
                                @if($version->is_active)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <form action="{{ route('admin.agent-versions.destroy', $version) }}" method="POST" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desactivar esta versión?')">Desactivar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $versions->links() }}
        </div>
    </div>
@stop