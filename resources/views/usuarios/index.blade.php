@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <h1>Gestión de Usuarios</h1>
@stop

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="container-fluid">
    <div class="card bg-light mb-3">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> Filtros
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label for="filter_rol" class="form-label small mb-1">Rol</label>
                        <select id="filter_rol" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="gerente_tienda">Gerente de Tienda</option>
                            <option value="gerente_plaza">Gerente de Plaza</option>
                            <option value="coordinador">Coordinador</option>
                            <option value="administrativo">Administrativo</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                </div>
                <div class="col-6 col-md-3">
                    <label for="filter_activo" class="form-label small mb-1">Estado</label>
                    <select id="filter_activo" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                    <div class="d-flex gap-2 flex-wrap">
                        <button id="btn_search" class="btn btn-success btn-sm">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button id="btn_refresh" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <button id="btn_reset_filters" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo"></i> Limpiar
                        </button>
                    </div>
                    @hasPermission('admin.usuarios.crear')
                    <button id="btn_create" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
                    @endhasPermission
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> Usuarios
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="users-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Plaza</th>
                            <th>Tienda</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="userModalLabel">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="userForm">
                <div class="modal-body">
                    <input type="hidden" id="user_id" name="user_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback" id="name_error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback" id="email_error"></div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Contraseña <span id="password_required">*</span></label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">Mínimo 8 caracteres</div>
                            <div class="invalid-feedback" id="password_error"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-2">
                        <div class="col-md-4">
                            <label for="plaza" class="form-label">Plaza</label>
                            <input type="text" class="form-control" id="plaza" name="plaza" placeholder="Ej: A001" maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label for="tienda" class="form-label">Tienda</label>
                            <input type="text" class="form-control" id="tienda" name="tienda" placeholder="Ej: T001" maxlength="10">
                        </div>
                        <div class="col-md-4">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="vendedor">Vendedor</option>
                                <option value="gerente_tienda">Gerente de Tienda</option>
                                <option value="gerente_plaza">Gerente de Plaza</option>
                                <option value="coordinador">Coordinador</option>
                                <option value="administrativo">Administrativo</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">
                                Usuario activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar Usuario -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Eliminar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de eliminar el usuario <strong id="delete_user_name"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">Eliminar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<style>
.table th, .table td { font-size: 0.85rem; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
</style>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

$(function() {
    const dataTable = $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 10,
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            },
            emptyTable: "No hay usuarios disponibles",
            zeroRecords: "No se encontraron resultados"
        },
        ajax: {
            url: "{{ url('/admin/usuarios/data') }}",
            data: function (d) {
                d.rol = $('#filter_rol').val();
                d.activo = $('#filter_activo').val();
            }
        },
        columns: [
            { data: 'id', className: 'text-center' },
            { data: 'name' },
            { data: 'email' },
            { data: 'plaza', className: 'text-center' },
            { data: 'tienda', className: 'text-center' },
            { data: 'rol', className: 'text-center', render: function(data) {
                const roles = {
                    'vendedor': '<span class="badge bg-primary">Vendedor</span>',
                    'gerente_tienda': '<span class="badge bg-info">Gerente Tienda</span>',
                    'gerente_plaza': '<span class="badge bg-success">Gerente Plaza</span>',
                    'coordinador': '<span class="badge bg-warning text-dark">Coordinador</span>',
                    'administrativo': '<span class="badge bg-secondary">Administrativo</span>',
                    'super_admin': '<span class="badge bg-danger">Super Admin</span>'
                };
                return roles[data] || data;
            }},
            { data: 'activo', className: 'text-center', render: function(data) {
                return data ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
            }},
            { data: 'created_at', className: 'text-center', render: function(data) {
                return data ? new Date(data).toLocaleDateString('es-MX') : '';
            }},
            { data: 'id', className: 'text-center', render: function(data, type, row) {
                return `
                    <button class="btn btn-sm btn-primary btn-edit" data-id="${data}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${data}" data-name="${row.name}" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
            }, orderable: false }
        ]
    });

    $('#btn_search').on('click', function() { dataTable.ajax.reload(); });
    $('#btn_refresh').on('click', function() { dataTable.ajax.reload(); });

    $('#btn_reset_filters').on('click', function() {
        $('#filter_rol').val('');
        $('#filter_activo').val('');
        dataTable.ajax.reload();
    });

    // Abrir modal para crear
    $('#btn_create').on('click', function() {
        $('#userModalLabel').text('Nuevo Usuario');
        $('#userForm')[0].reset();
        $('#user_id').val('');
        $('#password_required').show();
        $('#password').prop('required', true);
        $('#activo').prop('checked', true);
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    });

    // Editar usuario
    $('#users-table').on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        $.get("{{ url('/admin/usuarios') }}/" + id, function(response) {
            $('#userModalLabel').text('Editar Usuario');
            $('#user_id').val(response.id);
            $('#name').val(response.name);
            $('#email').val(response.email);
            $('#plaza').val(response.plaza || '');
            $('#tienda').val(response.tienda || '');
            $('#rol').val(response.rol);
            $('#activo').prop('checked', response.activo);
            $('#password_required').hide();
            $('#password').prop('required', false);
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').text('');
            $('#userModal').modal('show');
        });
    });

    // Eliminar usuario
    let deleteUserId = null;
    $('#users-table').on('click', '.btn-delete', function() {
        deleteUserId = $(this).data('id');
        $('#delete_user_name').text($(this).data('name'));
        $('#deleteModal').modal('show');
    });

    $('#btnConfirmDelete').on('click', function() {
        if (deleteUserId) {
            $.ajax({
                url: "{{ url('/admin/usuarios') }}/" + deleteUserId,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        $('#deleteModal').modal('hide');
                        dataTable.ajax.reload();
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                    toastr.error('Error al eliminar usuario');
                }
            });
        }
    });

    // Guardar usuario (crear o actualizar)
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        
        const userId = $('#user_id').val();
        const url = userId ? "{{ url('/admin/usuarios') }}/" + userId : "{{ url('/admin/usuarios') }}";
        
        $('.is-invalid').removeClass('is-invalid');
        
        let formData = $(this).serialize();
        if (userId) {
            formData += '&_method=PUT';
        }
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#userModal').modal('hide');
                    dataTable.ajax.reload();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                console.log(xhr.responseText);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        $('#' + field).addClass('is-invalid');
                        $('#' + field + '_error').text(errors[field][0]);
                    }
                } else {
                    toastr.error('Error al guardar usuario');
                }
            }
        });
    });

    // Filtros
    $('#filter_rol, #filter_activo').on('change', function() {
        dataTable.ajax.reload();
    });
});
</script>
@endsection
