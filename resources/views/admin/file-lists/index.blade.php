@extends('adminlte::page')

@section('title', 'Listas de Archivos')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Listas de Archivos (Whitelist / Blacklist)</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createWhitelistModal">
                        <i class="fas fa-plus-circle"></i> Agregar a Whitelist
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#createBlacklistModal">
                        <i class="fas fa-ban"></i> Agregar a Blacklist
                    </button>
                </div>
                <div class="card-body">
                    <table id="fileListsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Nombre de Archivo</th>
                                <th>Descripción</th>
                                <th>Creado por</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fileLists as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>
                                        @if($item->type === 'whitelist')
                                            <span class="badge badge-success">Whitelist</span>
                                        @else
                                            <span class="badge badge-danger">Blacklist</span>
                                        @endif
                                    </td>
                                    <td><code>{{ $item->file_name }}</code></td>
                                    <td>{{ $item->description ?? '-' }}</td>
                                    <td>{{ $item->creator->name ?? 'N/A' }}</td>
                                    <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm" data-toggle="modal"
                                                data-target="#editFileListModal"
                                                data-id="{{ $item->id }}"
                                                data-type="{{ $item->type }}"
                                                data-file_name="{{ $item->file_name }}"
                                                data-description="{{ $item->description ?? '' }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm"
                                                onclick="deleteFileList({{ $item->id }}, '{{ $item->file_name }}')">
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

    <!-- Create Whitelist Modal -->
    <div class="modal fade" id="createWhitelistModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form class="fileListForm" data-type="whitelist">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title">Agregar a Whitelist</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="type" value="whitelist">
                        <div class="form-group">
                            <label>Nombre del Archivo *</label>
                            <input type="text" name="file_name" class="form-control" required placeholder="ej: reporte.xlsx">
                            <small class="text-muted">Nombre exacto (ej: virus.exe) o extensión (ej: .exe para todos los .exe)</small>
                        </div>
                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Motivo por el que está permitido..."></textarea>
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

    <!-- Create Blacklist Modal -->
    <div class="modal fade" id="createBlacklistModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form class="fileListForm" data-type="blacklist">
                    @csrf
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title">Agregar a Blacklist</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="type" value="blacklist">
                        <div class="form-group">
                            <label>Nombre del Archivo *</label>
                            <input type="text" name="file_name" class="form-control" required placeholder="ej: virus.exe">
                            <small class="text-muted">Nombre exacto (ej: virus.exe) o extensión (ej: .exe para todos los .exe)</small>
                        </div>
                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Motivo por el que está bloqueado..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban"></i> Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editFileListModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editFileListForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Editar Archivo en Lista</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="editId">
                        <div class="form-group">
                            <label>Tipo</label>
                            <select name="type" id="editType" class="form-control" required>
                                <option value="whitelist">Whitelist</option>
                                <option value="blacklist">Blacklist</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nombre del Archivo *</label>
                            <input type="text" name="file_name" id="editFileName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
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
    $('#fileListsTable').DataTable({
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
            },
            "aria": {
                "sortAscending": ": activar para ordenar la columna ascendente",
                "sortDescending": ": activar para ordenar la columna descendente"
            }
        }
    });

    $('.fileListForm').submit(function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);
        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ route("admin.file-lists.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
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
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg
                });
                submitBtn.prop('disabled', false).html(form.data('type') === 'whitelist' ? '<i class="fas fa-plus-circle"></i> Agregar' : '<i class="fas fa-ban"></i> Agregar');
            }
        });
    });

    $('#editFileListModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const id = button.data('id');
        const type = button.data('type');
        const fileName = button.data('file_name');
        const description = button.data('description');

        $('#editId').val(id);
        $('#editType').val(type);
        $('#editFileName').val(fileName);
        $('#editDescription').val(description);
    });

    $('#editFileListForm').submit(function(e) {
        e.preventDefault();
        const id = $('#editId').val();
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '{{ url("admin/file-lists") }}/' + id,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#editFileListModal').modal('hide');
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
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg
                });
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
            }
        });
    });
});

function deleteFileList(id, fileName) {
    Swal.fire({
        title: '¿Eliminar?',
        text: `¿Eliminar "${fileName}" de la lista?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/file-lists") }}/' + id,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    _method: 'DELETE'
                },
                success: function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: 'Archivo eliminado de la lista exitosamente'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar'
                    });
                }
            });
        }
    });
}
</script>
@stop
