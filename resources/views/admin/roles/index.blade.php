@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1>Gestión de Roles</h1>
@stop

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="card">
        <div class="card-header">
            @can('admin.roles.crear')
            <button class="btn btn-primary" data-toggle="modal" data-target="#roleModal" onclick="resetRoleForm()">
                <i class="fas fa-plus"></i> Nuevo Rol
            </button>
            @endcan
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="roles-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Permisos</th>
                        <th>Usuarios</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para crear/editar rol -->
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down" role="document">
            <div class="modal-content">
                <form id="roleForm">
                    @csrf
                    <input type="hidden" name="id" id="role_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="roleModalLabel">Nuevo Rol</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="role_name">Nombre del Rol</label>
                            <input type="text" name="name" id="role_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Permisos</label>
                            <div class="row" id="permissions-container">
                                <!-- Permisos cargados via AJAX -->
                                <div class="col-12 text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando permisos...
                                </div>
                            </div>
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
    <div class="modal fade" id="deleteRoleModal" tabindex="-1" role="dialog" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-md-down" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRoleModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar el rol <strong id="delete_role_name"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDeleteRole">Eliminar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
let permissionsData = {};

$(document).ready(function() {
    loadPermissions();
    initDataTable();
    
    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        saveRole();
    });

    $('#deleteRoleModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var name = button.data('name');
        $('#delete_role_name').text(name);
        $('#confirmDeleteRole').data('id', id);
    });

    $('#confirmDeleteRole').on('click', function() {
        deleteRole($(this).data('id'));
    });
});

function loadPermissions() {
    $.ajax({
        url: '{{ route("roles.permissions") }}',
        method: 'GET',
        success: function(response) {
            permissionsData = response;
            renderPermissionsCheckboxes();
        },
        error: function() {
            $('#permissions-container').html('<div class="col-12 text-danger">Error al cargar permisos</div>');
        }
    });
}

function renderPermissionsCheckboxes(selectedPermissions = []) {
    let html = '';
    let columns = 3;
    let permissionsPerColumn = Math.ceil(Object.keys(permissionsData).length / columns);
    let count = 0;
    let colIndex = 0;

    html += '<div class="col-12"><strong>Seleccionar Todos</strong> <input type="checkbox" id="selectAllPermissions" onchange="toggleAllPermissions(this)"></div>';
    
    for (let module in permissionsData) {
        if (colIndex === 0) {
            html += '<div class="col-md-4">';
        }
        
        html += '<div class="module-group">';
        html += '<strong>' + module + '</strong><br>';
        
        permissionsData[module].forEach(function(permission) {
            let isChecked = selectedPermissions.includes(permission.name) ? 'checked' : '';
            html += '<div class="form-check">';
            html += '<input type="checkbox" name="permissions[]" value="' + permission.name + '" class="form-check-input permission-checkbox" ' + isChecked + '>';
            html += '<label class="form-check-label">' + permission.name + '</label>';
            html += '</div>';
        });
        
        html += '</div>';
        
        count++;
        if (count >= permissionsPerColumn) {
            html += '</div>';
            count = 0;
            colIndex++;
        }
    }
    
    if (count > 0) {
        html += '</div>';
    }

    $('#permissions-container').html(html);
}

function toggleAllPermissions(source) {
    $('.permission-checkbox').prop('checked', $(source).prop('checked'));
}

function initDataTable() {
    $('#roles-table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '{{ route("roles.data") }}',
            dataSrc: 'data'
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'name', name: 'name' },
            { 
                data: 'permissions',
                render: function(data) {
                    return data ? data.length : 0;
                }
            },
            { 
                data: 'users_count',
                render: function(data) {
                    return data || 0;
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
                    @can('admin.roles.editar')
                    buttons += `<button class="btn btn-sm btn-warning" onclick="editRole(${data.id}, '${data.name}')">
                        <i class="fas fa-edit"></i>
                    </button> `;
                    @endcan
                    @can('admin.roles.eliminar')
                    buttons += `<button class="btn btn-sm btn-danger" data-id="${data.id}" data-name="${data.name}" data-toggle="modal" data-target="#deleteRoleModal">
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

function resetRoleForm() {
    $('#role_id').val('');
    $('#role_name').val('');
    $('#roleModalLabel').text('Nuevo Rol');
    renderPermissionsCheckboxes([]);
}

function editRole(id, name) {
    $('#role_id').val(id);
    $('#role_name').val(name);
    $('#roleModalLabel').text('Editar Rol');
    
    $.ajax({
        url: '/admin/roles/' + id,
        method: 'GET',
        success: function(response) {
            let permissions = response.permissions ? response.permissions.map(p => p.name) : [];
            renderPermissionsCheckboxes(permissions);
            $('#roleModal').modal('show');
        },
        error: function() {
            alert('Error al cargar el rol');
        }
    });
}

function saveRole() {
    let id = $('#role_id').val();
    let url = id ? '/admin/roles/' + id : '{{ route("roles.store") }}';
    let method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: $('#roleForm').serialize(),
        success: function(response) {
            $('#roleModal').modal('hide');
            $('#roles-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Rol guardado correctamente',
                type: 'success'
            });
        },
        error: function(xhr) {
            let error = xhr.responseJSON?.message || 'Error al guardar el rol';
            Swal.fire({
                title: 'Error',
                text: error,
                type: 'error'
            });
        }
    });
}

function deleteRole(id) {
    $.ajax({
        url: '/admin/roles/' + id,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#deleteRoleModal').modal('hide');
            $('#roles-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Rol eliminado correctamente',
                type: 'success'
            });
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Error al eliminar el rol',
                type: 'error'
            });
        }
    });
}
</script>
@stop
