@extends('adminlte::page')

@section('title', 'Módulos de Autorización')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Módulos de Autorización</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createModuleModal">
                        <i class="fas fa-plus-circle"></i> Agregar Módulo
                    </button>
                </div>
                <div class="card-body">
                    <table id="modulesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Descripción</th>
                                <th>Correos Asignados</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $module)
                                <tr>
                                    <td>{{ $module->id }}</td>
                                    <td>{{ $module->name }}</td>
                                    <td><code>{{ $module->slug }}</code></td>
                                    <td>{{ $module->description ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $module->authorizable_emails_count }}</span>
                                    </td>
                                    <td>
                                        @if($module->is_active)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-secondary">Inactivo</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal"
                                                data-target="#editModuleModal"
                                                data-id="{{ $module->id }}"
                                                data-name="{{ $module->name }}"
                                                data-description="{{ $module->description ?? '' }}"
                                                data-is_active="{{ $module->is_active ? 1 : 0 }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteModule({{ $module->id }}, '{{ $module->name }}')">
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

    <!-- Create Module Modal -->
    <div class="modal fade" id="createModuleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="createModuleForm">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title">Agregar Módulo</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="name" class="form-control" required placeholder="ej: Listas de Archivos">
                        </div>
                        <div class="form-group">
                            <label>Slug *</label>
                            <input type="text" name="slug" class="form-control" required placeholder="ej: file-lists" pattern="[a-z0-9\-]+">
                            <small class="text-muted">Identificador único (minúsculas, números, guiones)</small>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Descripción del módulo..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Crear
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Module Modal -->
    <div class="modal fade" id="editModuleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editModuleForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Editar Módulo</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editModuleId">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="name" id="editModuleName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" id="editModuleDescription" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select name="is_active" id="editModuleIsActive" class="form-control">
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
    $('#modulesTable').DataTable({
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

    $('#createModuleForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route("admin.modules.store") }}',
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
                submitBtn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Crear');
            }
        });
    });

    $('#editModuleModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        $('#editModuleId').val(button.data('id'));
        $('#editModuleName').val(button.data('name'));
        $('#editModuleDescription').val(button.data('description'));
        $('#editModuleIsActive').val(button.data('is_active'));
    });

    $('#editModuleForm').submit(function(e) {
        e.preventDefault();
        const id = $('#editModuleId').val();
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ url("admin/modules") }}/' + id,
            type: 'POST',
            data: $(this).serialize() + '&_method=PUT',
            success: function(response) {
                $('#editModuleModal').modal('hide');
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

function deleteModule(id, name) {
    Swal.fire({
        title: '¿Eliminar?',
        text: `¿Eliminar el módulo "${name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/modules") }}/' + id,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { _method: 'DELETE' },
                success: function() {
                    Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Módulo eliminado exitosamente' })
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
