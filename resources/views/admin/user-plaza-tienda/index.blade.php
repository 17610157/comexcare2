@extends('adminlte::page')

@section('title', 'Asignación de Usuarios a Plaza/Tienda')

@section('content_header')
    <h1>Asignación de Usuarios a Plaza/Tienda</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Seleccione un usuario para asignarle plazas y tiendas</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
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
                            @foreach($user->plazaTiendas->pluck('plaza')->unique()->filter() as $plaza)
                                <span class="badge badge-info">{{ $plaza }}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach($user->plazaTiendas->pluck('tienda')->unique()->filter() as $tienda)
                                <span class="badge badge-success">{{ $tienda }}</span>
                            @endforeach
                        </td>
                        <td>
                            <a href="{{ route('user-plaza-tienda.edit', $user->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#users-table').DataTable();
});
</script>
@stop
