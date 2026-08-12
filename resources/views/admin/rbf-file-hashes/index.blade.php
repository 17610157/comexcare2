@extends('adminlte::page')

@section('title', 'RBF File Hashes')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>RBF File Hashes</h1>
@stop

@section('content')
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

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-upload"></i> Subir archivos</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.rbf-file-hashes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Archivos * <span class="text-muted small">(cualquier tipo, múltiples permitidos)</span></label>
                            <input type="file" name="archivos[]" class="form-control-file" multiple required>
                            <small class="text-muted">Solo se calcula el MD5; los archivos no se almacenan en disco. Servicio fijo: <strong>manual</strong>.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Plazas <span class="text-muted small">(puede seleccionar varias)</span></label>
                            <div class="border rounded p-2" style="max-height: 140px; overflow-y: auto;">
                                <div class="form-check">
                                    <input type="checkbox" id="select_all_plazas" class="form-check-input">
                                    <label for="select_all_plazas" class="form-check-label font-weight-bold">Todas</label>
                                </div>
                                @foreach ($plazas as $plaza)
                                    <div class="form-check">
                                        <input type="checkbox" name="plaza[]" value="{{ $plaza }}" id="plaza_{{ $plaza }}" class="form-check-input plaza-checkbox">
                                        <label for="plaza_{{ $plaza }}" class="form-check-label">{{ $plaza }}</label>
                                    </div>
                                @endforeach
                            </div>
                            <small class="text-muted">Se registra el archivo en cada plaza seleccionada. Si no selecciona ninguna, se registra sin plaza.</small>
                        </div>
                    </div>
                </div>
                @can('rbf-file-hashes.crear')
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-fingerprint"></i> Calcular MD5 y guardar
                    </button>
                @endcan
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Registros en rbf_file_hashes</h3>
            <div class="card-tools d-flex align-items-center">
                <select id="filter_plaza" class="form-control form-control-sm mr-2" style="width: 160px;">
                    <option value="">Todas las plazas</option>
                    @foreach ($plazas as $plaza)
                        <option value="{{ $plaza }}">{{ $plaza }}</option>
                    @endforeach
                </select>
                <button id="btn_refresh" class="btn btn-primary btn-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="rbfHashesTable" class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Plaza</th>
                            <th>Zona</th>
                            <th>Nombre</th>
                            <th>Hash</th>
                            <th>Ruta</th>
                            <th>Última sync</th>
                            <th>Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
        var table = $('#rbfHashesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.rbf-file-hashes.data') }}',
                data: function(d) {
                    d.plaza = $('#filter_plaza').val();
                }
            },
            order: [[0, 'desc']],
            pageLength: 25,
            columns: [
                { data: 'id' },
                { data: 'plaza' },
                { data: 'zona' },
                { data: 'name' },
                { data: 'hash', render: function(data) { return '<code>' + (data || '') + '</code>'; } },
                { data: 'path', render: function(data) { return '<code>' + (data || '') + '</code>'; } },
                { data: 'last_sync' },
                { data: 'created_at' },
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: function(data) {
                        return '<button type="button" class="btn btn-danger btn-sm btn-delete" data-id="' + data + '" title="Eliminar"><i class="fas fa-trash"></i></button>';
                    }
                }
            ],
            language: {
                decimal: '',
                emptyTable: 'No hay datos disponibles',
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

        $('#filter_plaza').on('change', function() {
            table.ajax.reload();
        });

        $('#select_all_plazas').on('change', function() {
            $('.plaza-checkbox').prop('checked', this.checked);
        });

        $('.plaza-checkbox').on('change', function() {
            var total = $('.plaza-checkbox').length;
            var checked = $('.plaza-checkbox:checked').length;
            $('#select_all_plazas').prop('checked', total > 0 && checked === total);
        });

        $('#btn_refresh').on('click', function() {
            table.ajax.reload();
        });

        $('#rbfHashesTable tbody').on('click', '.btn-delete', function() {
            var id = $(this).data('id');

            Swal.fire({
                title: '¿Eliminar?',
                text: '¿Eliminar el registro #' + id + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url('admin/rbf-file-hashes') }}/' + id,
                        type: 'POST',
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: { _method: 'DELETE' },
                        success: function(response) {
                            Swal.fire({ icon: 'success', title: 'Eliminado', text: response.message })
                                .then(() => table.ajax.reload());
                        },
                        error: function(xhr) {
                            let message = 'Error al eliminar el registro';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            } else if (xhr.status === 419) {
                                message = 'La sesión ha expirado. Recarga la página.';
                            } else if (xhr.status === 403) {
                                message = 'No tienes permiso para eliminar registros.';
                            }
                            Swal.fire({ icon: 'error', title: 'Error', text: message });
                        }
                    });
                }
            });
        });

        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
    </script>
@stop
