@extends('adminlte::page')

@section('title', 'Reporte de Metas de Ventas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line text-primary"></i> Reporte de Metas</h1>
        <div>
            <button type="button" class="btn btn-success" onclick="exportExcel()">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button type="button" class="btn btn-danger ml-2" onclick="exportPdf()">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
            <button type="button" class="btn btn-warning ml-2" onclick="exportCsv()">
                <i class="fas fa-file-csv"></i> CSV
            </button>
            <a href="{{ url()->current() }}" class="btn btn-primary ml-2">
                <i class="fas fa-sync-alt"></i> Recargar
            </a>
        </div>
    </div>
@stop

@section('content')
    <!-- Panel de Filtros -->
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter"></i> Filtros Rápidos</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('reportes.metas-ventas') }}" id="filtros-form">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Fecha Inicio</label>
                            <input type="date" name="fecha_inicio" class="form-control form-control-sm" 
                                   value="{{ $fecha_inicio }}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Fecha Fin</label>
                            <input type="date" name="fecha_fin" class="form-control form-control-sm" 
                                   value="{{ $fecha_fin }}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Plazas</label>
                            <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
                                <div class="form-check">
                                    <input type="checkbox" id="select_all_plazas" class="form-check-input"
                                           {{ isset($plazas) && is_array($plazas) && isset($plazaArray) && is_array($plazaArray) && count($plazas) === count($plazaArray) ? 'checked' : '' }}>
                                    <label for="select_all_plazas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
                                </div>
                                @php
                                    $plazaArray = is_array($plaza) ? $plaza : ($plaza ? explode(',', $plaza) : []);
                                @endphp
                                @if(isset($plazas) && is_array($plazas))
                                @foreach($plazas as $p)
                                <div class="form-check">
                                    <input type="checkbox" name="plaza[]" value="{{ $p }}" id="plaza_{{ $p }}" class="form-check-input plaza-checkbox"
                                           {{ in_array($p, $plazaArray) ? 'checked' : '' }}>
                                    <label for="plaza_{{ $p }}" class="form-check-label">{{ $p }}</label>
                                </div>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tiendas</label>
                            <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
                                <div class="form-check">
                                    <input type="checkbox" id="select_all_tiendas" class="form-check-input"
                                           {{ isset($tiendas) && is_array($tiendas) && isset($tiendaArray) && is_array($tiendaArray) && count($tiendas) === count($tiendaArray) ? 'checked' : '' }}>
                                    <label for="select_all_tiendas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
                                </div>
                                @php
                                    $tiendaArray = is_array($tienda) ? $tienda : ($tienda ? explode(',', $tienda) : []);
                                @endphp
                                @if(isset($tiendas) && is_array($tiendas))
                                @foreach($tiendas as $t)
                                <div class="form-check">
                                    <input type="checkbox" name="tienda[]" value="{{ $t }}" id="tienda_{{ $t }}" class="form-check-input tienda-checkbox"
                                           {{ in_array($t, $tiendaArray) ? 'checked' : '' }}>
                                    <label for="tienda_{{ $t }}" class="form-check-label">{{ $t }}</label>
                                </div>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Zonas</label>
                            <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
                                <div class="form-check">
                                    <input type="checkbox" id="select_all_zonas" class="form-check-input">
                                    <label for="select_all_zonas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
                                </div>
                                @php
                                    $zonaArray = is_array($zona) ? $zona : ($zona ? explode(',', $zona) : []);
                                @endphp
                                @if(isset($zonas) && is_array($zonas))
                                @foreach($zonas as $z)
                                <div class="form-check">
                                    <input type="checkbox" name="zona[]" value="{{ $z }}" id="zona_{{ $z }}" class="form-check-input zona-checkbox"
                                           {{ in_array($z, $zonaArray) ? 'checked' : '' }}>
                                    <label for="zona_{{ $z }}" class="form-check-label">{{ $z }}</label>
                                </div>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="submit" class="btn btn-primary btn-block" id="btn-buscar">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($error_msg))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            {{ $error_msg }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            {{ session('error') }}
        </div>
    @endif

    @if(count($resultados) > 0)
        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($estadisticas['total_registros']) }}</h3>
                        <p>Tiendas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($estadisticas['total_meta_total'], 2) }}</h3>
                        <p>Meta Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${{ number_format($estadisticas['total_venta_real'], 2) }}</h3>
                        <p>Venta Real</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($estadisticas['porcentaje_promedio'], 2) }}%</h3>
                        <p>% Cumplimiento</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> Resultados
                    @if($tiempo_carga > 0)
                        <span class="badge badge-secondary ml-2">{{ $tiempo_carga }}ms</span>
                    @endif
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-striped" id="tabla-reportes">
                        <thead class="thead-dark">
                            <tr>
                                <th width="80">CLAVE</th>
                                <th width="150">NOMBRE</th>
                                <th width="100" class="text-right">META</th>
                                <th width="90" class="text-center">DÍAS MES</th>
                                <th width="100" class="text-center">DÍAS AGOTADOS</th>
                                <th width="110" class="text-right">META PARCIAL</th>
                                <th width="110" class="text-right">VENTA REAL</th>
                                <th width="100" class="text-right">PORCENTAJE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultados as $item)
                            <tr>
                                <td>{{ $item->clave_tienda }}</td>
                                <td>{{ $item->sucursal }}</td>
                                <td class="text-right">${{ number_format($item->meta_total, 2) }}</td>
                                <td class="text-center">{{ number_format($item->dias_mes, 1) }}</td>
                                <td class="text-center">{{ number_format($item->dias_agotados, 2) }}</td>
                                <td class="text-right">${{ number_format($item->meta_parcial, 2) }}</td>
                                <td class="text-right">${{ number_format($item->venta_real, 2) }}</td>
                                <td class="text-right font-weight-bold">
                                    @php
                                        $porcentaje = floatval($item->porcentaje);
                                        $color = $porcentaje >= 100 ? 'text-success' : ($porcentaje >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <span class="{{ $color }}">{{ number_format($porcentaje, 2) }}%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="2" class="text-right font-weight-bold">TOTALES:</td>
                                <td class="text-right font-weight-bold">${{ number_format($estadisticas['total_meta_total'], 2) }}</td>
                                <td class="text-center">-</td>
                                <td class="text-center">-</td>
                                <td class="text-right font-weight-bold">${{ number_format($estadisticas['total_meta_parcial'], 2) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($estadisticas['total_venta_real'], 2) }}</td>
                                <td class="text-right font-weight-bold">
                                    @php
                                        $porcentaje_total = $estadisticas['porcentaje_promedio'];
                                        $color_total = $porcentaje_total >= 100 ? 'text-success' : ($porcentaje_total >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <span class="{{ $color_total }}">{{ number_format($porcentaje_total, 2) }}%</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    Mostrando {{ $estadisticas['total_registros'] }} registros | 
                    Exportado el {{ date('d/m/Y H:i:s') }}
                </small>
            </div>
        </div>
    @elseif(request()->has('fecha_inicio'))
        <div class="alert alert-warning text-center">
            <i class="icon fas fa-exclamation-triangle"></i>
            No se encontraron resultados para los filtros seleccionados
        </div>
    @endif
@stop

@section('css')
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <style>
        .text-right { text-align: right !important; }
        .bg-info-light { background-color: #d1ecf1 !important; }
        .bg-warning-light { background-color: #fff3cd !important; }
        .bg-success-light { background-color: #d4edda !important; }
        .bg-secondary-light { background-color: #e2e3e5 !important; }
        .text-success { color: #28a745 !important; }
        .text-warning { color: #ffc107 !important; }
        .text-danger { color: #dc3545 !important; }
        .monetary { 
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .small-box .icon {
            font-size: 70px;
        }
        .dataTables_wrapper {
            padding: 10px;
        }
        #tabla-reportes_wrapper {
            margin-top: 10px;
        }
        thead.bg-info th {
            background-color: #17a2b8 !important;
            color: white !important;
        }
    </style>
@stop

@section('js')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>jQuery.noConflict();</script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable si hay resultados
            @if(count($resultados) > 0)
                var table = $('#tabla-reportes').DataTable({
                    "pageLength": 25,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                    "order": [[0, 'asc']],
                    "language": {
                        "processing": "Procesando...",
                        "lengthMenu": "Mostrar _MENU_ registros",
                        "zeroRecords": "No se encontraron resultados",
                        "emptyTable": "Ningún dato disponible en esta tabla",
                        "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                        "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                        "infoFiltered": "(filtrado de un total de _MAX_ registros)",
                        "search": "Buscar:",
                        "paginate": {
                            "first": "Primero",
                            "last": "Último",
                            "next": "Siguiente",
                            "previous": "Anterior"
                        },
                        "aria": {
                            "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                            "sortDescending": ": Activar para ordenar la columna de manera descendente"
                        }
                    },
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                           '<"row"<"col-sm-12"tr>>' +
                           '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "columnDefs": [
                        { "targets": [6, 7, 8, 9, 10, 11, 12, 13], "className": "text-right" }
                    ]
                });
            @endif

            // Botón exportar Excel
            $('#btn-export-excel').on('click', function() {
                var form = $('<form>', {
                    method: 'POST',
                    action: '{{ route("reportes.metas-ventas.export") }}'
                });
                form.append('@csrf');
                form.append($('<input>', { type: 'hidden', name: 'fecha_inicio', value: $('input[name="fecha_inicio"]').val() }));
                form.append($('<input>', { type: 'hidden', name: 'fecha_fin', value: $('input[name="fecha_fin"]').val() }));
                
                $('.plaza-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'plaza[]', value: $(this).val() }));
                });
                $('.tienda-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'tienda[]', value: $(this).val() }));
                });
                $('.zona-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'zona[]', value: $(this).val() }));
                });
                
                form.appendTo('body').submit();
            });

            // Botón exportar CSV
            $('#btn-export-csv').on('click', function() {
                var form = $('<form>', {
                    method: 'POST',
                    action: '{{ route("reportes.metas-ventas.export.csv") }}'
                });
                form.append('@csrf');
                form.append($('<input>', { type: 'hidden', name: 'fecha_inicio', value: $('input[name="fecha_inicio"]').val() }));
                form.append($('<input>', { type: 'hidden', name: 'fecha_fin', value: $('input[name="fecha_fin"]').val() }));
                
                $('.plaza-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'plaza[]', value: $(this).val() }));
                });
                $('.tienda-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'tienda[]', value: $(this).val() }));
                });
                $('.zona-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'zona[]', value: $(this).val() }));
                });
                
                form.appendTo('body').submit();
            });

            // Botón exportar PDF
            $('#btn-export-pdf').on('click', function() {
                var form = $('<form>', {
                    method: 'POST',
                    action: '{{ route("reportes.metas-ventas.export.pdf") }}'
                });
                form.append('@csrf');
                form.append($('<input>', { type: 'hidden', name: 'fecha_inicio', value: $('input[name="fecha_inicio"]').val() }));
                form.append($('<input>', { type: 'hidden', name: 'fecha_fin', value: $('input[name="fecha_fin"]').val() }));
                
                $('.plaza-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'plaza[]', value: $(this).val() }));
                });
                $('.tienda-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'tienda[]', value: $(this).val() }));
                });
                $('.zona-checkbox:checked').each(function() {
                    form.append($('<input>', { type: 'hidden', name: 'zona[]', value: $(this).val() }));
                });
                
                form.appendTo('body').submit();
            });

            // Validar fechas
            $('#filtros-form').submit(function(e) {
                var fechaInicio = $('input[name="fecha_inicio"]').val();
                var fechaFin = $('input[name="fecha_fin"]').val();
                
                if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'La fecha de inicio no puede ser mayor a la fecha fin'
                    });
                    return false;
                }
                
                return true;
            });

            // SweetAlert para recargar página
            $('a.btn-primary[href*="current"]').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Recargar página',
                    text: '¿Desea recargar la página?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#007bff',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, recargar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        location.reload();
                    }
                });
            });

            // Checkboxes de filtros
            $('#select_all_plazas').on('change', function() {
                $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            $('#select_all_tiendas').on('change', function() {
                $('.tienda-checkbox').prop('checked', $(this).prop('checked'));
            });

            $('#select_all_zonas').on('change', function() {
                $('.zona-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            function updateSelectAll() {
                const todasPlazas = $('.plaza-checkbox').length > 0 && $('.plaza-checkbox:checked').length === $('.plaza-checkbox').length;
                $('#select_all_plazas').prop('checked', todasPlazas);

                const todasTiendas = $('.tienda-checkbox').length > 0 && $('.tienda-checkbox:checked').length === $('.tienda-checkbox').length;
                $('#select_all_tiendas').prop('checked', todasTiendas);

                const todasZonas = $('.zona-checkbox').length > 0 && $('.zona-checkbox:checked').length === $('.zona-checkbox').length;
                $('#select_all_zonas').prop('checked', todasZonas);
            }
            
            $('.plaza-checkbox, .tienda-checkbox, .zona-checkbox').on('change', updateSelectAll);
            updateSelectAll();
        });

        window.exportExcel = function() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("reportes.metas-ventas.export") }}';
            form.style.display = 'none';
            
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            
            var fechaInicio = document.createElement('input');
            fechaInicio.type = 'hidden';
            fechaInicio.name = 'fecha_inicio';
            fechaInicio.value = document.querySelector('input[name="fecha_inicio"]').value;
            form.appendChild(fechaInicio);
            
            var fechaFin = document.createElement('input');
            fechaFin.type = 'hidden';
            fechaFin.name = 'fecha_fin';
            fechaFin.value = document.querySelector('input[name="fecha_fin"]').value;
            form.appendChild(fechaFin);
            
            document.querySelectorAll('.plaza-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'plaza[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.querySelectorAll('.tienda-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tienda[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.querySelectorAll('.zona-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'zona[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }

        window.exportCsv = function() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("reportes.metas-ventas.export.csv") }}';
            form.style.display = 'none';
            
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            
            var fechaInicio = document.createElement('input');
            fechaInicio.type = 'hidden';
            fechaInicio.name = 'fecha_inicio';
            fechaInicio.value = document.querySelector('input[name="fecha_inicio"]').value;
            form.appendChild(fechaInicio);
            
            var fechaFin = document.createElement('input');
            fechaFin.type = 'hidden';
            fechaFin.name = 'fecha_fin';
            fechaFin.value = document.querySelector('input[name="fecha_fin"]').value;
            form.appendChild(fechaFin);
            
            document.querySelectorAll('.plaza-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'plaza[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.querySelectorAll('.tienda-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tienda[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.querySelectorAll('.zona-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'zona[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }

        window.exportPdf = function() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("reportes.metas-ventas.export.pdf") }}';
            form.style.display = 'none';
            
            var csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            
            var fechaInicio = document.createElement('input');
            fechaInicio.type = 'hidden';
            fechaInicio.name = 'fecha_inicio';
            fechaInicio.value = document.querySelector('input[name="fecha_inicio"]').value;
            form.appendChild(fechaInicio);
            
            var fechaFin = document.createElement('input');
            fechaFin.type = 'hidden';
            fechaFin.name = 'fecha_fin';
            fechaFin.value = document.querySelector('input[name="fecha_fin"]').value;
            form.appendChild(fechaFin);
            
            document.querySelectorAll('.plaza-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'plaza[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.querySelectorAll('.tienda-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'tienda[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.querySelectorAll('.zona-checkbox:checked').forEach(function(cb) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'zona[]';
                input.value = cb.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
@stop