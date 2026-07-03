@extends('adminlte::page')

@section('title', 'Archivos Predeterminados del Agente')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('home') }}">Admin</a></li>
            <li class="breadcrumb-item active">Archivos Predeterminados del Agente</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3"><i class="fas fa-cogs text-primary"></i> Archivos Predeterminados del Agente</h1>
        @can('agent-defaults.crear')
            <a href="{{ route('admin.agent-defaults.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Categoría
            </a>
        @endcan
    </div>
    <p class="text-muted">Cada ruta se asigna independientemente a grupos o computadoras.</p>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Categorías</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Rutas</th>
                        <th>Archivos</th>
                        <th>Estado</th>
                        <th style="width:140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->id }}</td>
                            <td>
                                <strong>{{ $category->name }}</strong>
                                @if($category->description)
                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($category->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                @php $routesCount = $category->routes_count ?? 0; @endphp
                                @if($routesCount > 0)
                                    <span class="badge bg-success"><i class="fas fa-check-circle"></i> {{ $routesCount }}</span>
                                @else
                                    <span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> Sin rutas</span>
                                @endif
                            </td>
                            <td>{{ $category->files_count ?? 0 }}</td>
                            <td>
                                @if($category->is_active)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                @can('agent-defaults.ver')
                                    <a href="{{ route('admin.agent-defaults.show', $category) }}"
                                       class="btn btn-sm btn-outline-primary" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @endcan
                                @can('agent-defaults.editar')
                                    <a href="{{ route('admin.agent-defaults.edit', $category) }}"
                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                                @can('agent-defaults.eliminar')
                                    <form action="{{ route('admin.agent-defaults.destroy', $category) }}"
                                          method="POST" style="display:inline"
                                          onsubmit="return confirm('¿Eliminar esta categoría?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">No hay categorías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop

@push('css')
<style>
    .table > :not(caption) > * > * {
        vertical-align: middle;
    }
</style>
@endpush
