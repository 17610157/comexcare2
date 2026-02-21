@extends('adminlte::page')

@section('title', 'Permisos')

@section('content_header')
    <h1>Gestión de Permisos</h1>
@stop

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="card">
        <div class="card-header">
            @can('admin.permissions.crear')
            <button class="btn btn-primary" data-toggle="modal" data-target="#permissionModal" onclick="resetPermissionForm()">
                <i class="fas fa-plus"></i> Nuevo Permiso
            </button>
            @endcan
            @can('admin.permissions.ver')
            <button class="btn btn-warning" onclick="syncPermissions()">
                <i class="fas fa-sync"></i> Sincronizar Permisos
            </button>
            @endcan
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="permissions-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Módulo</th>
                        <th>Guard</th>
                        <th>Roles Asignados</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para crear/editar permiso -->
    <div class="modal fade" id="permissionModal" tabindex="-1" role="dialog" aria-labelledby="permissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down" role="document">
            <div class="modal-content">
                <form id="permissionForm">
                    @csrf
                    <input type="hidden" name="id" id="permission_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Nuevo Permiso</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="permission_name">Nombre del Permiso</label>
                            <input type="text" name="name" id="permission_name" class="form-control" placeholder="modulo.accion" required>
                            <small class="text-muted">Ej: usuarios.ver, reportes.editar</small>
                        </div>
                        <div class="form-group">
                            <label for="permission_guard">Guard</label>
                            <select name="guard_name" id="permission_guard" class="form-control">
                                <option value="web">web</option>
                                <option value="api">api</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="deletePermissionModal" tabindex="-1" role="dialog" aria-labelledby="deletePermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePermissionModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar el permiso <strong id="delete_permission_name"></strong>?</p>
                    <p class="text-danger">Esta acción puede afectar a los roles que tengan este permiso asignado.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDeletePermission">Eliminar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    initDataTable();
    
    $('#permissionForm').on('submit', function(e) {
        e.preventDefault();
        savePermission();
    });

    $('#deletePermissionModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        $('#delete_permission_name').text(name);
        $('#confirmDeletePermission').data('id', id);
    });

    $('#confirmDeletePermission').on('click', function() {
        deletePermission($(this).data('id'));
    });
});

function initDataTable() {
    $('#permissions-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("permissions.data") }}',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { 
                data: 'name',
                render: function(data) {
                    let parts = data.split('.');
                    return parts[0] ? parts[0].charAt(0).toUpperCase() + parts[0].slice(1) : 'General';
                }
            },
            { data: 'guard_name', name: 'guard_name' },
            { 
                data: 'roles',
                render: function(data) {
                    return data ? data.length : 0;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return data ? new Date(data).toLocaleDateString() : '';
                }
            },
            {
                data: null,
                render: function(data) {
                    let buttons = '';
                    @can('admin.permissions.eliminar')
                    buttons += `<button class="btn btn-sm btn-danger" data-id="${data.id}" data-name="${data.name}" data-toggle="modal" data-target="#deletePermissionModal">
                        <i class="fas fa-trash"></i>
                    </button>`;
                    @endcan
                    return buttons;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });
}

function resetPermissionForm() {
    $('#permission_id').val('');
    $('#permission_name').val('');
    $('#permission_guard').val('web');
    $('#permissionModalLabel').text('Nuevo Permiso');
}

function savePermission() {
    let id = $('#permission_id').val();
    let url = id ? '/admin/permissions/' + id : '{{ route("permissions.store") }}';
    let method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: $('#permissionForm').serialize(),
        success: function(response) {
            $('#permissionModal').modal('hide');
            $('#permissions-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Permiso guardado correctamente',
                icon: 'success'
            });
        },
        error: function(xhr) {
            let error = xhr.responseJSON?.message || 'Error al guardar el permiso';
            Swal.fire({
                title: 'Error',
                text: error,
                type: 'error'
            });
        }
    });
}

function deletePermission(id) {
    $.ajax({
        url: '/admin/permissions/' + id,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#deletePermissionModal').modal('hide');
            $('#permissions-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Permiso eliminado correctamente',
                type: 'success'
            });
        },
        error: function(xhr) {
            let error = xhr.responseJSON?.message || 'Error al eliminar el permiso';
            Swal.fire({
                title: 'Error',
                text: error,
                type: 'error'
            });
        }
    });
}

function syncPermissions() {
    Swal.fire({
        title: 'Sincronizando permisos...',
        html: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    $.ajax({
        url: '{{ route("permissions.sync") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#permissions-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Permisos sincronizados correctamente',
                type: 'success'
            });
        },
        error: function(xhr) {
            let error = xhr.responseJSON?.message || 'Error al sincronizar permisos';
            Swal.fire({
                title: 'Error',
                text: error,
                type: 'error'
            });
        }
    });
}
</script>
@stop
