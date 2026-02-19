@extends('adminlte::page')

@section('title', 'Permisos')

@section('content_header')
    <h1>Gestión de Permisos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="permissions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Módulo</th>
                        <th>Guard</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\Spatie\Permission\Models\Permission::all() as $permission)
                    <tr>
                        <td>{{ $permission->id }}</td>
                        <td>{{ $permission->name }}</td>
                        <td>{{ $permission->module ?? 'General' }}</td>
                        <td>{{ $permission->guard_name }}</td>
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
    $('#permissions-table').DataTable();
});
</script>
@stop
