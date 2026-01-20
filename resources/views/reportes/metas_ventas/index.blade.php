@extends('adminlte::page')

@section('title', 'Reporte de Metas de Ventas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line text-primary"></i> Reporte de Metas de Ventas</h1>
        <div>
            <form method="POST" action="{{ route('reportes.metas-ventas.export') }}" style="display: inline;" id="export-excel-form">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="zona" value="{{ $zona }}">
                <button type="button" class="btn btn-success" id="btn-export-excel">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </form>
            <form method="POST" action="{{ route('reportes.metas-ventas.export.pdf') }}" style="display: inline;" id="export-pdf-form">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="zona" value="{{ $zona }}">
                <button type="button" class="btn btn-danger ml-2" id="btn-export-pdf">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </form>
            <form method="POST" action="{{ route('reportes.metas-ventas.export.csv') }}" style="display: inline;" id="export-csv-form">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="zona" value="{{ $zona }}">
                <button type="button" class="btn btn-warning ml-2" id="btn-export-csv">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </form>
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
                            <label>Plaza</label>
                            <input type="text" name="plaza" class="form-control form-control-sm" 
                                   value="{{ $plaza }}" placeholder="Todas">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tienda</label>
                            <input type="text" name="tienda" class="form-control form-control-sm" 
                                   value="{{ $tienda }}" placeholder="Todas">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Zona</label>
                            <input type="text" name="zona" class="form-control form-control-sm" 
                                   value="{{ $zona }}" placeholder="Todas">
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
                        <p>Registros</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list-ol"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($estadisticas['total_meta_dia'], 2) }}</h3>
                        <p>Meta Día Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${{ number_format($estadisticas['total_venta_dia'], 2) }}</h3>
                        <p>Venta del Día</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($estadisticas['porcentaje_acumulado_global'], 2) }}%</h3>
                        <p>% Acumulado Total</p>
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
                                <th width="40">#</th>
                                <th width="80">Plaza</th>
                                <th width="80">Tienda</th>
                                <th width="120">Sucursal</th>
                                <th width="90">Fecha</th>
                                <th width="80">Zona</th>
                                <th width="90" class="text-right">Meta Total</th>
                                <th width="80" class="text-right">Días Total</th>
                                <th width="90" class="text-right">Valor Día</th>
                                <th width="90" class="text-right bg-info">Meta Día</th>
                                <th width="100" class="text-right bg-warning">Venta Día</th>
                                <th width="100" class="text-right bg-success">Venta Acum.</th>
                                <th width="100" class="text-right">% Cumplimiento</th>
                                <th width="100" class="text-right bg-secondary">% Acumulado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultados as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $item->id_plaza }}</td>
                                <td>{{ $item->clave_tienda }}</td>
                                <td>{{ $item->sucursal }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $item->zona }}</td>
                                <td class="text-right">${{ number_format($item->meta_total, 2) }}</td>
                                <td class="text-right">{{ $item->dias_total }}</td>
                                <td class="text-right">${{ number_format($item->valor_dia, 2) }}</td>
                                <td class="text-right font-weight-bold bg-info-light">${{ number_format($item->meta_dia, 2) }}</td>
                                <td class="text-right font-weight-bold bg-warning-light">${{ number_format($item->venta_del_dia, 2) }}</td>
                                <td class="text-right font-weight-bold bg-success-light">${{ number_format($item->venta_acumulada, 2) }}</td>
                                <td class="text-right font-weight-bold">
                                    @php
                                        $porcentaje = floatval($item->porcentaje);
                                        $color = $porcentaje >= 100 ? 'text-success' : ($porcentaje >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <span class="{{ $color }}">{{ number_format($porcentaje, 2) }}%</span>
                                </td>
                                <td class="text-right font-weight-bold bg-secondary-light">
                                    @php
                                        $porcentaje_acumulado = floatval($item->porcentaje_acumulado);
                                        $color_acum = $porcentaje_acumulado >= 100 ? 'text-success' : ($porcentaje_acumulado >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <span class="{{ $color_acum }}">{{ number_format($porcentaje_acumulado, 2) }}%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="9" class="text-right font-weight-bold">TOTALES:</td>
                                <td class="text-right font-weight-bold">${{ number_format($estadisticas['total_meta_dia'], 2) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($estadisticas['total_venta_dia'], 2) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($estadisticas['total_venta_acumulada'], 2) }}</td>
                                <td class="text-right font-weight-bold">
                                    @php
                                        $porcentaje_total = $estadisticas['porcentaje_promedio'];
                                        $color_total = $porcentaje_total >= 100 ? 'text-success' : ($porcentaje_total >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <span class="{{ $color_total }}">{{ number_format($porcentaje_total, 2) }}%</span>
                                </td>
                                <td class="text-right font-weight-bold">
                                    @php
                                        // ¡IMPORTANTE! Aquí calculamos el % acumulado TOTAL de la tabla
                                        // (Total Venta Acumulada / Total Meta Día) × 100
                                        $porcentaje_acumulado_total = $estadisticas['porcentaje_acumulado_global'];
                                        $color_acum_total = $porcentaje_acumulado_total >= 100 ? 'text-success' : ($porcentaje_acumulado_total >= 80 ? 'text-warning' : 'text-danger');
                                    @endphp
                                    <span class="{{ $color_acum_total }}">{{ number_format($porcentaje_acumulado_total, 2) }}%</span>
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
                Swal.fire({
                    title: 'Exportar a Excel',
                    text: '¿Desea descargar el archivo Excel?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, descargar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#export-excel-form').submit();
                    }
                });
            });

            // Botón exportar CSV
            $('#btn-export-csv').on('click', function() {
                Swal.fire({
                    title: 'Exportar a CSV',
                    text: '¿Desea descargar el archivo CSV?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, descargar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#export-csv-form').submit();
                    }
                });
            });

            // Botón exportar PDF
            $('#btn-export-pdf').on('click', function() {
                Swal.fire({
                    title: 'Exportar a PDF',
                    text: '¿Desea descargar el archivo PDF?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, descargar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#export-pdf-form').submit();
                    }
                });
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
        });
    </script>
@stop