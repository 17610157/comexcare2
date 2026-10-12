@extends('adminlte::page')

@section('title', 'Notificaciones de Cortes')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Notificaciones de Cortes</h1>
@stop

@section('content')
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Destinatarios fijos en todos los envíos:
        <strong>{{ implode(', ', config('cortes.destinatarios_fijos', [])) }}</strong>.
        Los correos capturados por plaza se agregan a los fijos.
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-envelope"></i> Destinatarios y horario por plaza</h3>
            <div class="card-tools d-flex align-items-center">
                @can('cortes-notificaciones.crear')
                    <button type="button" class="btn btn-primary btn-sm mr-2" id="btn_nuevo">
                        <i class="fas fa-plus"></i> Nueva configuración
                    </button>
                @endcan
                @can('cortes.enviar')
                    <button type="button" class="btn btn-success btn-sm mr-2" id="btn_enviar_todas">
                        <i class="fas fa-paper-plane"></i> Enviar todas
                    </button>
                @endcan
                <button id="btn_refresh" class="btn btn-default btn-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="configsTable" class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plaza</th>
                            <th>Correos</th>
                            <th>Envío automático</th>
                            <th>Hora</th>
                            <th>Último envío</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_config" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_title">Nueva configuración</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="form_config" method="POST">
                    @csrf
                    <input type="hidden" id="config_id" name="config_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="plaza">Plaza *</label>
                                    <input type="text" id="plaza" name="plaza" class="form-control" list="plazas_list" maxlength="50" required>
                                    <datalist id="plazas_list">
                                        @foreach ($plazas as $plaza)
                                            <option value="{{ $plaza }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="text-danger small error-plaza d-none"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora_envio">Hora de envío *</label>
                                    <input type="number" id="hora_envio" name="hora_envio" class="form-control" min="0" max="23" step="1" value="7" required>
                                    <small class="text-muted">0 a 23 h.</small>
                                    <div class="text-danger small error-hora_envio d-none"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="d-block">Envío automático</label>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="envio_activo" name="envio_activo" value="1">
                                        <label class="custom-control-label" for="envio_activo">Activo (una vez al día)</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="emails">Correos adicionales de la plaza</label>
                            <textarea id="emails" name="emails" class="form-control" rows="3" placeholder="correo1@dominio.com, correo2@dominio.com"></textarea>
                            <small class="text-muted">Separa varios correos por coma, punto y coma o espacio.</small>
                            <div class="text-danger small error-emails d-none"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn_guardar"><i class="fas fa-save"></i> Guardar</button>
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
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var hoy = '{{ now()->subDay()->toDateString() }}';

    function clearErrors() {
        $('.text-danger.small').addClass('d-none').text('');
    }

    function showErrors(errors) {
        clearErrors();
        $.each(errors || {}, function(field, messages) {
            var $el = $('.error-' + field);
            if ($el.length) {
                $el.removeClass('d-none').text(messages[0]);
            }
        });
    }

    $(document).ready(function() {
        var table = $('#configsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.cortes-notificaciones.data') }}',
            order: [[1, 'asc']],
            pageLength: 25,
            columns: [
                { data: 'id' },
                { data: 'plaza', render: function(data) { return '<strong>' + data + '</strong>'; } },
                { data: 'emails', render: function(data) { return data ? data : '<span class="text-muted">Solo destinatarios fijos</span>'; } },
                {
                    data: 'envio_activo',
                    render: function(data) {
                        return data
                            ? '<span class="badge badge-success">Activo</span>'
                            : '<span class="badge badge-secondary">Inactivo</span>';
                    }
                },
                { data: 'hora_envio' },
                { data: 'ultimo_envio_at', render: function(data) { return data ? data : '<span class="text-muted">-</span>'; } },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        var html = '';
                        @can('cortes.enviar')
                            html += '<button type="button" class="btn btn-success btn-sm btn-send mr-1" title="Enviar ahora"><i class="fas fa-paper-plane"></i></button>';
                        @endcan
                        @can('cortes-notificaciones.editar')
                            html += '<button type="button" class="btn btn-info btn-sm btn-edit mr-1" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
                        @endcan
                        @can('cortes-notificaciones.eliminar')
                            html += '<button type="button" class="btn btn-danger btn-sm btn-delete" title="Eliminar"><i class="fas fa-trash"></i></button>';
                        @endcan
                        return html;
                    }
                }
            ],
            language: {
                emptyTable: 'No hay configuraciones registradas',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ entradas',
                infoEmpty: 'Mostrando 0 a 0 de 0 entradas',
                infoFiltered: '(filtrado de _MAX_ entradas totales)',
                lengthMenu: 'Mostrar _MENU_ entradas',
                loadingRecords: 'Cargando...',
                processing: 'Procesando...',
                search: 'Buscar:',
                zeroRecords: 'No se encontraron registros coincidentes',
                paginate: {
                    first: 'Primero',
                    last: 'Último',
                    next: 'Siguiente',
                    previous: 'Anterior'
                }
            }
        });

        $('#btn_refresh').on('click', function() {
            table.ajax.reload();
        });

        $('#btn_nuevo').on('click', function() {
            $('#form_config')[0].reset();
            $('#config_id').val('');
            $('#hora_envio').val(7);
            $('#modal_title').text('Nueva configuración');
            clearErrors();
            $('#modal_config').modal('show');
        });

        $('#configsTable tbody').on('click', '.btn-edit', function() {
            var rowData = table.row($(this).closest('tr')).data();
            if (!rowData) return;

            $('#config_id').val(rowData.id);
            $('#plaza').val(rowData.plaza);
            $('#emails').val(rowData.emails);
            $('#hora_envio').val(rowData.hora_envio_valor);
            $('#envio_activo').prop('checked', !!rowData.envio_activo);
            $('#modal_title').text('Editar configuración: ' + rowData.plaza);
            clearErrors();
            $('#modal_config').modal('show');
        });

        $('#form_config').on('submit', function(e) {
            e.preventDefault();

            var configId = $('#config_id').val();
            var isEdit = configId !== '';
            var url = isEdit
                ? '{{ url('admin/cortes-notificaciones') }}/' + configId
                : '{{ url('admin/cortes-notificaciones') }}';

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: {
                    _method: isEdit ? 'PUT' : 'POST',
                    plaza: $('#plaza').val(),
                    emails: $('#emails').val(),
                    hora_envio: $('#hora_envio').val(),
                    envio_activo: $('#envio_activo').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    $('#modal_config').modal('hide');
                    Swal.fire({ icon: 'success', title: 'Guardado', text: response.message })
                        .then(() => table.ajax.reload(null, false));
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        var message = 'Error al guardar la configuración';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.status === 419) {
                            message = 'La sesión ha expirado. Recarga la página.';
                        } else if (xhr.status === 403) {
                            message = 'No tienes permiso para esta acción.';
                        }
                        Swal.fire({ icon: 'error', title: 'Error', text: message });
                    }
                }
            });
        });

        $('#configsTable tbody').on('click', '.btn-send', function() {
            var rowData = table.row($(this).closest('tr')).data();
            if (!rowData) return;

            Swal.fire({
                title: '¿Enviar correo?',
                text: 'Se enviará el reporte de tiendas sin corte de la plaza ' + rowData.plaza + ' (fecha ' + hoy + ').',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route('reportes.cortes.notificaciones.enviar') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: { plaza: rowData.plaza, fecha: hoy },
                    success: function(response) {
                        Swal.fire({
                            icon: response.success ? 'success' : 'info',
                            title: response.success ? 'Enviado' : 'Sin envío',
                            text: response.message
                        }).then(() => table.ajax.reload(null, false));
                    },
                    error: function(xhr) {
                        var message = 'Error al enviar el correo';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.status === 403) {
                            message = 'No tienes permiso para enviar correos.';
                        }
                        Swal.fire({ icon: 'error', title: 'Error', text: message });
                    }
                });
            });
        });

        $('#btn_enviar_todas').on('click', function() {
            Swal.fire({
                title: '¿Enviar a todas las plazas?',
                text: 'Se enviará el reporte de tiendas sin corte de todas las plazas con faltantes (fecha ' + hoy + ').',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, enviar todas',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (!result.isConfirmed) return;

                var $btn = $('#btn_enviar_todas').prop('disabled', true);
                $.ajax({
                    url: '{{ route('reportes.cortes.notificaciones.enviar-todas') }}',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: { fecha: hoy },
                    success: function(response) {
                        Swal.fire({ icon: 'success', title: 'Completado', text: response.message })
                            .then(() => table.ajax.reload(null, false));
                    },
                    error: function(xhr) {
                        var message = 'Error al enviar los correos';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.status === 403) {
                            message = 'No tienes permiso para enviar correos.';
                        }
                        Swal.fire({ icon: 'error', title: 'Error', text: message });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                    }
                });
            });
        });

        $('#configsTable tbody').on('click', '.btn-delete', function() {
            var id = $(this).data('id') || table.row($(this).closest('tr')).data().id;

            Swal.fire({
                title: '¿Eliminar?',
                text: 'Se eliminará la configuración de la plaza.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url('admin/cortes-notificaciones') }}/' + id,
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        data: { _method: 'DELETE' },
                        success: function(response) {
                            Swal.fire({ icon: 'success', title: 'Eliminado', text: response.message })
                                .then(() => table.ajax.reload(null, false));
                        },
                        error: function(xhr) {
                            var message = 'Error al eliminar la configuración';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.status === 403) {
                                message = 'No tienes permiso para eliminar configuraciones.';
                            }
                            Swal.fire({ icon: 'error', title: 'Error', text: message });
                        }
                    });
                }
            });
        });

        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 5000);
    });
    </script>
@stop
