@extends('adminlte::page')

@section('title', 'Reporte de Vendedores - Vista Matricial')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-users text-primary"></i> Reporte de Vendedores - Vista Matricial</h1>
        <div>
            @hasPermission('reportes.vendedores_matricial.exportar')
            <form method="POST" action="{{ route('reportes.vendedores.matricial.export.excel') }}" style="display: inline;" id="export-excel-form">
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
            <form method="POST" action="{{ route('reportes.vendedores.matricial.export.pdf') }}" style="display: inline;" id="export-pdf-form">
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
            @hasPermission('reportes.vendedores_matricial.ver')
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
            <form method="GET" action="{{ route('reportes.vendedores.matricial') }}" id="filtros-form">
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

    <!-- Estadísticas rápidas -->
    @if(count($vendedores_data) > 0)
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ number_format(count($vendedores_data)) }}</h3>
                    <p>Vendedores</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format(count($dias)) }}</h3>
                    <p>Días Reportados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>${{ number_format($total_general, 2) }}</h3>
                    <p>Total General</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $tiempo_carga }}ms</h3>
                    <p>Tiempo Carga</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Resultados -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-table"></i> Resultados - Vista Matricial
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
            @if(count($vendedores_data) == 0 && request()->has('fecha_inicio'))
                <div class="text-center py-4">
                    <i class="fas fa-search text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2">No se encontraron resultados para los filtros seleccionados</p>
                </div>
            @elseif(count($vendedores_data) > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="min-width: 1000px;">
                        <thead class="thead-dark" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="fixed-column" width="120">Descripción</th>
                                @foreach ($vendedores_data as $vendedor_id => $data)
                                    <th class="vendedor-header" width="100">{{ $vendedor_id }}</th>
                                @endforeach
                                <th class="vendedor-header bg-primary text-white" width="100">TOTAL DÍA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Fila 1: Nombres -->
                            <tr class="nombre-row">
                                <td class="fixed-column fw-bold">NOMBRE</td>
                                @foreach ($vendedores_data as $data)
                                    <td class="text-center small">{{ $data['nombre'] }}</td>
                                @endforeach
                                <td class="text-center fw-bold">-</td>
                            </tr>
                            
                            <!-- Fila 2: Tipo -->
                            <tr class="tipo-row">
                                <td class="fixed-column fw-bold">TIPO</td>
                                @foreach ($vendedores_data as $data)
                                    <td class="text-center small">{{ $data['tipo'] }}</td>
                                @endforeach
                                <td class="text-center fw-bold">-</td>
                            </tr>
                            
                            <!-- Fila 3: Tiendas -->
                            <tr class="tienda-row">
                                <td class="fixed-column fw-bold">TIENDAS</td>
                                @foreach ($vendedores_data as $data)
                                    <td class="text-center small">{{ implode(', ', $data['tiendas']) }}</td>
                                @endforeach
                                <td class="text-center fw-bold">-</td>
                            </tr>
                            
                            <!-- Fila 4: Plazas -->
                            <tr class="plaza-row">
                                <td class="fixed-column fw-bold">PLAZA</td>
                                @foreach ($vendedores_data as $data)
                                    <td class="text-center">{{ implode(', ', $data['plazas']) }}</td>
                                @endforeach
                                <td class="text-center fw-bold">-</td>
                            </tr>
                            
                            <!-- Filas de días -->
                            @foreach ($dias as $dia_key => $dia_formatted)
                            <tr>
                                <td class="fixed-column fw-bold">{{ $dia_formatted }}</td>
                                @php
                                    $total_dia = 0;
                                @endphp
                                @foreach ($vendedores_data as $vendedor_id => $data)
                                    @php
                                        $venta = $data['ventas'][$dia_key] ?? 0;
                                        $total_dia += $venta;
                                    @endphp
                                    <td class="monetary {{ $venta > 0 ? 'bg-success-light' : '' }}">
                                        @if ($venta > 0)
                                            ${{ number_format($venta, 2) }}
                                        @else
                                            $-
                                        @endif
                                    </td>
                                @endforeach
                                <td class="monetary total-row">
                                    ${{ number_format($total_dia, 2) }}
                                </td>
                            </tr>
                            @endforeach
                            
                            <!-- Fila de totales por vendedor -->
                            <tr class="total-row">
                                <td class="fixed-column fw-bold">TOTAL VENDEDOR</td>
                                @php
                                    $total_general_row = 0;
                                @endphp
                                @foreach ($vendedores_data as $vendedor_id => $data)
                                    @php
                                        $total_vendedor = $total_por_vendedor[$vendedor_id] ?? 0;
                                        $total_general_row += $total_vendedor;
                                    @endphp
                                    <td class="monetary">
                                        ${{ number_format($total_vendedor, 2) }}
                                    </td>
                                @endforeach
                                <td class="monetary">
                                    ${{ number_format($total_general_row, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if(count($vendedores_data) > 0)
        <div class="card-footer">
            <small class="text-muted">
                Mostrando {{ count($vendedores_data) }} vendedores y {{ count($dias) }} días | 
                Exportado el {{ date('d/m/Y H:i:s') }}
            </small>
        </div>
        @endif
    </div>
@stop

@section('css')
    <style>
        body {
            font-size: 14px;
        }
        .text-right { 
            text-align: right !important; 
        }
        .bg-success-light { 
            background-color: #d1e7dd !important; 
        }
        .bg-danger-light { 
            background-color: #f8d7da !important; 
        }
        .bg-primary-light { 
            background-color: #cfe2ff !important; 
        }
        .table-responsive { 
            overflow-x: auto; 
            max-height: none;
        }
        .table-responsive table {
            min-width: 1500px;
        }
        .monetary { 
            text-align: right !important;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            white-space: nowrap;
            padding: 4px 6px !important;
        }
        .fixed-column {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 20;
            border-right: 2px solid #dee2e6;
            font-weight: bold;
        }
        .vendedor-header {
            background-color: #e9ecef !important;
            font-weight: bold;
            text-align: center;
            vertical-align: middle !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        thead th {
            position: sticky;
            top: 0;
            z-index: 15;
        }
        .nombre-row {
            background-color: #f8f9fa !important;
            font-size: 11px;
            font-style: italic;
        }
        .tipo-row {
            background-color: #e3f2fd !important;
            font-size: 11px;
            font-weight: 500;
        }
        .tienda-row {
            background-color: #f8f9fa !important;
            font-size: 11px;
        }
        .plaza-row {
            background-color: #e9ecef !important;
            font-size: 11px;
            font-weight: bold;
        }
        .total-row {
            background-color: #cfe2ff !important;
            font-weight: bold;
        }
        .valor-numerico {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        table th, table td {
            border: 1px solid #dee2e6 !important;
        }
        .small-box .icon {
            font-size: 70px;
        }
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
    </style>
@stop

@section('js')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Botón exportar Excel - SIN LOADING
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

            // Botón exportar PDF - SIN LOADING
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

            // Botón exportar CSV - SIN LOADING
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
                
                // Mostrar loading en botón de búsqueda
                $('#btn-buscar').html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
                return true;
            });
        });
    </script>
@stop