@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1>Gestión de Roles</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <button class="btn btn-primary" data-toggle="modal" data-target="#roleModal">
                <i class="fas fa-plus"></i> Nuevo Rol
            </button>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="roles-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Permisos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>{{ $role->name }}</td>
                        <td>{{ $role->permissions->count() }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editRole({{ $role->id }}, '{{ $role->name }}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteRole({{ $role->id }})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="roleForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Rol</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre del Rol</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Permisos</label>
                            <select name="permissions[]" class="form-control" multiple>
                                @foreach(\Spatie\Permission\Models\Permission::all() as $permission)
                                    <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#roles-table').DataTable();
    
    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("roles.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                location.reload();
            }
        });
    });
});

function editRole(id, name) {
    alert('Editar rol: ' + name);
}

function deleteRole(id) {
    if (confirm('¿Está seguro de eliminar este rol?')) {
        $.ajax({
            url: '/admin/roles/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function() {
                location.reload();
            }
        });
    }
}
</script>
@stop
