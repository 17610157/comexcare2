@extends('adminlte::page')

@section('title', 'Reporte de Vendedores')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-users text-primary"></i> Reporte de Vendedores</h1>
        <div>
            @hasPermission('reportes.vendedores.exportar')
            <form method="POST" action="{{ route('reportes.vendedores.export') }}" style="display: inline;" id="export-excel-form">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="vendedor" value="{{ $vendedor }}">
                <button type="button" class="btn btn-success" id="btn-export-excel">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
            </form>
            <form method="POST" action="{{ route('reportes.vendedores.export.pdf') }}" style="display: inline;" id="export-pdf-form">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="vendedor" value="{{ $vendedor }}">
                <button type="button" class="btn btn-danger ml-2" id="btn-export-pdf">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
            </form>
            <form method="POST" action="{{ route('reportes.vendedores.export.csv') }}" style="display: inline;" id="export-csv-form">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="vendedor" value="{{ $vendedor }}">
                <button type="button" class="btn btn-warning ml-2" id="btn-export-csv">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
            </form>
            @endhasPermission
            @hasPermission('reportes.vendedores.ver')
            <a href="{{ url()->current() }}" class="btn btn-primary ml-2">
                <i class="fas fa-sync-alt"></i> Recargar
            </a>
            @endhasPermission
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
            <form method="GET" action="{{ route('reportes.vendedores') }}" id="filtros-form">
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
                                    <input type="checkbox" id="select_all_plazas" class="form-check-input">
                                    <label for="select_all_plazas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
                                </div>
                                @foreach($plazas as $p)
                                <div class="form-check">
                                    <input type="checkbox" name="plaza[]" value="{{ $p }}" id="plaza_{{ $p }}" class="form-check-input plaza-checkbox"
                                           {{ is_array($plaza) && in_array($p, $plaza) ? 'checked' : '' }}>
                                    <label for="plaza_{{ $p }}" class="form-check-label">{{ $p }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tiendas</label>
                            <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
                                <div class="form-check">
                                    <input type="checkbox" id="select_all_tiendas" class="form-check-input">
                                    <label for="select_all_tiendas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
                                </div>
                                @foreach($tiendas as $t)
                                <div class="form-check">
                                    <input type="checkbox" name="tienda[]" value="{{ $t }}" id="tienda_{{ $t }}" class="form-check-input tienda-checkbox"
                                           {{ is_array($tienda) && in_array($t, $tienda) ? 'checked' : '' }}>
                                    <label for="tienda_{{ $t }}" class="form-check-label">{{ $t }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Vendedor</label>
                            <input type="text" name="vendedor" class="form-control form-control-sm" 
                                   value="{{ $vendedor }}" placeholder="Todos">
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

    <script>
    $(function() {
        $('#select_all_plazas').on('change', function() {
            $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        $('#select_all_tiendas').on('change', function() {
            $('.tienda-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // Verificar si todas están seleccionadas
        const todasPlazas = $('.plaza-checkbox').length > 0 && $('.plaza-checkbox:checked').length === $('.plaza-checkbox').length;
        const todasTiendas = $('.tienda-checkbox').length > 0 && $('.tienda-checkbox:checked').length === $('.tienda-checkbox').length;
        $('#select_all_plazas').prop('checked', todasPlazas);
        $('#select_all_tiendas').prop('checked', todasTiendas);
    });
    </script>

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
                        <h3>{{ number_format(count($resultados)) }}</h3>
                        <p>Registros</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-list-ol"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${{ number_format($total_ventas, 2) }}</h3>
                        <p>Ventas Totales</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>${{ number_format($total_devoluciones, 2) }}</h3>
                        <p>Devoluciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-undo"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>${{ number_format($total_neto, 2) }}</h3>
                        <p>Venta Neta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
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
                                <th width="120">Tienda-Vendedor</th>
                                <th width="120">Vendedor-Día</th>
                                <th width="80">Plaza Ajustada</th>
                                <th width="70">Tienda</th>
                                <th width="80">Vendedor</th>
                                <th width="90">Fecha</th>
                                <th width="100" class="text-right bg-success">Venta Total</th>
                                <th width="100" class="text-right bg-danger">Devolución</th>
                                <th width="100" class="text-right bg-primary">Venta Neta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultados as $item)
                            <tr>
                                <td class="text-center">{{ $item['no'] }}</td>
                                <td>{{ $item['tienda_vendedor'] }}</td>
                                <td>{{ $item['vendedor_dia'] }}</td>
                                <td>{{ $item['plaza_ajustada'] }}</td>
                                <td>{{ $item['ctienda'] }}</td>
                                <td>{{ $item['vend_clave'] }}</td>
                                <td>{{ $item['fecha'] }}</td>
                                <td class="text-right text-success font-weight-bold">${{ number_format($item['venta_total'], 2) }}</td>
                                <td class="text-right text-danger font-weight-bold">${{ number_format($item['devolucion'], 2) }}</td>
                                <td class="text-right text-primary font-weight-bold">${{ number_format($item['venta_neta'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="7" class="text-right font-weight-bold">TOTALES:</td>
                                <td class="text-right font-weight-bold text-success">${{ number_format($total_ventas, 2) }}</td>
                                <td class="text-right font-weight-bold text-danger">${{ number_format($total_devoluciones, 2) }}</td>
                                <td class="text-right font-weight-bold text-primary">${{ number_format($total_neto, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    Mostrando {{ count($resultados) }} registros |
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
        .bg-success { background-color: #d4edda !important; }
        .bg-danger { background-color: #f8d7da !important; }
        .bg-primary { background-color: #cce5ff !important; }
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
        .btn-warning {
            color: #212529;
            background-color: #ffc107;
            border-color: #ffc107;
        }
        .btn-warning:hover {
            color: #212529;
            background-color: #e0a800;
            border-color: #d39e00;
        }
        #tabla-reportes {
            min-width: 1200px;
            table-layout: auto;
        }
        #tabla-reportes th,
        #tabla-reportes td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        #tabla-reportes th:hover,
        #tabla-reportes td:hover {
            overflow: visible;
            white-space: normal;
            z-index: 1;
            position: relative;
            background-color: inherit;
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
                        { "targets": [7, 8, 9], "className": "text-right" }
                    ]
                });
            @endif

            // Botón exportar Excel - SOLUCIÓN SIMPLE
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
                        // Enviar formulario inmediatamente - NO mostrar loading
                        $('#export-excel-form').submit();
                    }
                });
            });

            // Botón exportar CSV - SOLUCIÓN SIMPLE
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
                        // Enviar formulario inmediatamente - NO mostrar loading
                        $('#export-csv-form').submit();
                    }
                });
            });

            // Botón exportar PDF - SOLUCIÓN SIMPLE
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
                        // Enviar formulario inmediatamente - NO mostrar loading
                        $('#export-pdf-form').submit();
                    }
                });
            });

            // Validar fechas con SweetAlert
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