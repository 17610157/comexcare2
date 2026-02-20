@extends('adminlte::page')

@section('title', 'Asignación de Usuarios a Plaza/Tienda')

@section('content_header')
    <h1>Asignación de Usuarios a Plaza/Tienda</h1>
    <p class="text-muted">Asigne plazas y tiendas a los usuarios para controlar su acceso a los reportes.</p>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Usuarios del Sistema</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Plazas Asignadas</th>
                        <th>Tiendas Asignadas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @forelse($user->getRoleNames() as $role)
                                <span class="badge badge-primary">{{ $role }}</span>
                            @empty
                                <span class="badge badge-secondary">Sin rol</span>
                            @endforelse
                        </td>
                        <td>
                            @forelse($user->plazaTiendas->pluck('plaza')->unique()->filter()->values() as $plaza)
                                <span class="badge badge-info">{{ $plaza }}</span>
                            @empty
                                <span class="text-muted">Sin asignar</span>
                            @endforelse
                        </td>
                        <td>
                            @forelse($user->plazaTiendas->pluck('tienda')->unique()->filter()->values()->take(5) as $tienda)
                                <span class="badge badge-success">{{ $tienda }}</span>
                            @empty
                                <span class="text-muted">Todas las de plaza</span>
                            @endforelse
                            @if($user->plazaTiendas->pluck('tienda')->unique()->filter()->count() > 5)
                                <span class="badge badge-warning">+{{ $user->plazaTiendas->pluck('tienda')->unique()->filter()->count() - 5 }} más</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.user-plaza-tienda.edit', $user->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">Información de Plazas Disponibles</h3>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($plazasData as $plaza)
                <div class="col-md-4">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-building"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ $plaza->id_plaza }}</span>
                            <span class="info-box-number">
                                {{ $plaza->tiendas_count }} tiendas
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#users-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "order": [[0, "desc"]]
    });
});
</script>
@stop
