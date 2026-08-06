@extends('adminlte::page')

@section('title', 'Correos Autorizados')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Correos Autorizados</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createEmailModal">
                        <i class="fas fa-plus-circle"></i> Agregar Correo
                    </button>
                </div>
                <div class="card-body">
                    <table id="emailsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Correo</th>
                                <th>Módulo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emails as $email)
                                <tr>
                                    <td>{{ $email->id }}</td>
                                    <td>{{ $email->user->name ?? 'N/A' }}</td>
                                    <td>{{ $email->email }}</td>
                                    <td>{{ $email->module->name ?? 'Global (todos los módulos)' }}</td>
                                    <td>
                                        @if($email->is_active)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal"
                                                data-target="#editEmailModal"
                                                data-id="{{ $email->id }}"
                                                data-user_id="{{ $email->user_id }}"
                                                data-email="{{ $email->email }}"
                                                data-module_id="{{ $email->module_id ?? '' }}"
                                                data-is_active="{{ $email->is_active ? 1 : 0 }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteEmail({{ $email->id }}, '{{ $email->email }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Email Modal -->
    <div class="modal fade" id="createEmailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="createEmailForm">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title">Agregar Correo Autorizado</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Usuario (plataforma) *</label>
                            <select name="user_id" id="createEmailUserId" class="form-control" required>
                                <option value="">Seleccionar usuario...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" data-email="{{ $user->email }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Correo Autorizado *</label>
                            <input type="email" name="email" id="createEmailEmail" class="form-control" required placeholder="correo@ejemplo.com">
                            <small class="text-muted">Se autocompleta al seleccionar el usuario</small>
                        </div>
                        <div class="form-group">
                            <label>Módulo</label>
                            <select name="module_id" class="form-control">
                                <option value="">Global (todos los módulos)</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}">{{ $module->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Si no selecciona módulo, el correo recibirá notificaciones de todos</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Email Modal -->
    <div class="modal fade" id="editEmailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editEmailForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Editar Correo Autorizado</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editEmailId">
                        <div class="form-group">
                            <label>Usuario (plataforma) *</label>
                            <select name="user_id" id="editEmailUserId" class="form-control" required>
                                <option value="">Seleccionar usuario...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Correo Autorizado *</label>
                            <input type="email" name="email" id="editEmailEmail" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Módulo</label>
                            <select name="module_id" id="editEmailModuleId" class="form-control">
                                <option value="">Global (todos los módulos)</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}">{{ $module->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="is_active" id="editEmailIsActive" class="form-control">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#emailsTable').DataTable({
        "order": [[0, "desc"]],
        "language": {
            "decimal": "",
            "emptyTable": "No hay datos disponibles",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
            "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
            "infoFiltered": "(filtrado de _MAX_ entradas totales)",
            "lengthMenu": "Mostrar _MENU_ entradas",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "No se encontraron registros coincidentes",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });

    $('#createEmailUserId').on('change', function() {
        const email = $(this).find(':selected').data('email');
        if (email) {
            $('#createEmailEmail').val(email);
        }
    });

    $('#createEmailForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route("admin.authorizable-emails.store") }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                form.closest('.modal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                let msg = 'Error al guardar';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
                submitBtn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Agregar');
            }
        });
    });

    $('#editEmailModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        $('#editEmailId').val(button.data('id'));
        $('#editEmailUserId').val(button.data('user_id'));
        $('#editEmailEmail').val(button.data('email'));
        $('#editEmailModuleId').val(button.data('module_id'));
        $('#editEmailIsActive').val(button.data('is_active'));
    });

    $('#editEmailForm').submit(function(e) {
        e.preventDefault();
        const id = $('#editEmailId').val();
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ url("admin/authorizable-emails") }}/' + id,
            type: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            success: function(response) {
                $('#editEmailModal').modal('hide');
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.message
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                let msg = 'Error al actualizar';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
            }
        });
    });
});

function deleteEmail(id, email) {
    Swal.fire({
        title: '¿Eliminar?',
        text: `¿Eliminar el correo "${email}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/authorizable-emails") }}/' + id,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { _method: 'DELETE' },
                success: function() {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Correo eliminado exitosamente' })
                    .then(() => location.reload());
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al eliminar' });
                }
            });
        }
    });
}
</script>
@stop
