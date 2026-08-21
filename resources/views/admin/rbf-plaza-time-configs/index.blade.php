@extends('adminlte::page')

@section('title', 'Horarios por Plaza RBF')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Horarios por Plaza RBF</h1>
@stop

@section('content')
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        El ajuste se aplica al campo <code>last_modified</code> de <code>rbf_file_hashes</code> en cada sincronización:
        <strong>last_modified − meridiano + zona_horaria horas</strong>.
        Solo las plazas configuradas aquí reciben el ajuste. Ejemplo: meridiano = 6, zona horaria = -1 → resta 7 horas.
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clock"></i> Configuración de ajuste de horas por plaza</h3>
            <div class="card-tools d-flex align-items-center">
                @can('rbf-plaza-time.crear')
                    <button type="button" class="btn btn-primary btn-sm mr-2" id="btn_nuevo">
                        <i class="fas fa-plus"></i> Nueva configuración
                    </button>
                @endcan
                @can('rbf-plaza-time.sincronizar')
                    <button type="button" class="btn btn-success btn-sm mr-2" id="btn_sync">
                        <i class="fas fa-bolt"></i> Sincronizar ahora
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
                            <th>Meridiano</th>
                            <th>Zona horaria</th>
                            <th>Total aplicado</th>
                            <th>Creado</th>
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="plaza">Plaza *</label>
                                    <input type="text" id="plaza" name="plaza" class="form-control" list="plazas_list" maxlength="50" required>
                                    <datalist id="plazas_list">
                                        @foreach ($plazas as $plaza)
                                            <option value="{{ $plaza }}"></option>
                                        @endforeach
                                    </datalist>
                                    <small class="text-muted">Se normaliza a minúsculas.</small>
                                    <div class="text-danger small error-plaza d-none"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="meridiano">Meridiano *</label>
                                    <input type="number" id="meridiano" name="meridiano" class="form-control" min="0" max="23" step="1" required>
                                    <small class="text-muted">Horas base a restar (ej. 6 o 7).</small>
                                    <div class="text-danger small error-meridiano d-none"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="zona_horaria">Zona horaria *</label>
                                    <input type="number" id="zona_horaria" name="zona_horaria" class="form-control" min="-12" max="14" step="1" value="0" required>
                                    <small class="text-muted">Desplazamiento adicional (ej. -1 o +1).</small>
                                    <div class="text-danger small error-zona_horaria d-none"></div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning mb-0">
                            Vista previa: <span id="preview_total" class="font-weight-bold"></span>
                        </div>
                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="forzar_sync" name="forzar_sync" value="1" checked @cannot('rbf-plaza-time.sincronizar') disabled @endcannot>
                            <label class="form-check-label" for="forzar_sync">
                                Forzar actualización de hashes al guardar
                                <small class="text-muted d-block">Ejecuta la sincronización inmediatamente en lugar de esperar el ciclo automático (30 min).</small>
                            </label>
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

    function updatePreview() {
        var m = parseInt($('#meridiano').val() || 0, 10);
        var z = parseInt($('#zona_horaria').val() || 0, 10);
        var net = z - m;
        var sign = net < 0 ? '-' : '+';
        $('#preview_total').text('last_modified ' + sign + ' ' + Math.abs(net) + ' h');
    }

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
            ajax: '{{ route('admin.rbf-plaza-time-configs.data') }}',
            order: [[1, 'asc']],
            pageLength: 25,
            columns: [
                { data: 'id' },
                { data: 'plaza', render: function(data) { return '<strong>' + data + '</strong>'; } },
                { data: 'meridiano' },
                { data: 'zona_horaria' },
                { data: 'total_horas', render: function(data) { return '<span class="badge badge-warning">' + data + '</span>'; } },
                { data: 'created_at' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        var html = '';
                        @can('rbf-plaza-time.editar')
                            html += '<button type="button" class="btn btn-info btn-sm btn-edit mr-1" data-id="' + data.id + '" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
                        @endcan
                        @can('rbf-plaza-time.eliminar')
                            html += '<button type="button" class="btn btn-danger btn-sm btn-delete" data-id="' + data.id + '" title="Eliminar"><i class="fas fa-trash"></i></button>';
                        @endcan
                        return html;
                    }
                }
            ],
            language: {
                decimal: '',
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

        $('#btn_sync').on('click', function() {
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: '{{ route('admin.rbf-plaza-time-configs.sincronizar') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sincronización iniciada',
                        text: response.message,
                        timer: 4000,
                        showConfirmButton: false
                    });
                    setTimeout(function() { table.ajax.reload(null, false); }, 60000);
                },
                error: function(xhr) {
                    var message = 'Error al encolar la sincronización';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 419) {
                        message = 'La sesión ha expirado. Recarga la página.';
                    } else if (xhr.status === 403) {
                        message = 'No tienes permiso para sincronizar.';
                    }
                    Swal.fire({ icon: 'error', title: 'Error', text: message });
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        $('#meridiano, #zona_horaria').on('input', updatePreview);

        $('#btn_nuevo').on('click', function() {
            $('#form_config')[0].reset();
            $('#config_id').val('');
            $('#modal_title').text('Nueva configuración');
            clearErrors();
            updatePreview();
            $('#modal_config').modal('show');
        });

        $('#configsTable tbody').on('click', '.btn-edit', function() {
            var rowData = table.row($(this).closest('tr')).data();
            if (!rowData) return;

            $('#config_id').val(rowData.id);
            $('#plaza').val(rowData.plaza);
            $('#meridiano').val(parseInt(rowData.meridiano, 10));
            $('#zona_horaria').val(parseInt(rowData.zona_horaria.replace('+', ''), 10));
            $('#modal_title').text('Editar configuración: ' + rowData.plaza);
            clearErrors();
            updatePreview();
            $('#modal_config').modal('show');
        });

        $('#form_config').on('submit', function(e) {
            e.preventDefault();

            var configId = $('#config_id').val();
            var isEdit = configId !== '';
            var url = isEdit
                ? '{{ url('admin/rbf-plaza-time-configs') }}/' + configId
                : '{{ url('admin/rbf-plaza-time-configs') }}';

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                data: {
                    _method: isEdit ? 'PUT' : 'POST',
                    plaza: $('#plaza').val(),
                    meridiano: $('#meridiano').val(),
                    zona_horaria: $('#zona_horaria').val(),
                    forzar_sync: $('#forzar_sync').is(':checked') ? 1 : 0
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

        $('#configsTable tbody').on('click', '.btn-delete', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: '¿Eliminar?',
                text: 'Se eliminará la configuración y la plaza dejará de recibir el ajuste en el próximo sync.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url('admin/rbf-plaza-time-configs') }}/' + id,
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
                            } else if (xhr.status === 419) {
                                message = 'La sesión ha expirado. Recarga la página.';
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
