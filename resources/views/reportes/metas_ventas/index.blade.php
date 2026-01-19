@extends('adminlte::page')

@section('title', 'Reporte de Metas vs Ventas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line text-primary"></i> Reporte de Metas vs Ventas</h1>
        <div>
            <form method="POST" action="{{ route('reportes.metas-ventas.export.excel') }}" style="display: inline;" id="export-excel-form">
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
                        <h3>${{ number_format($total_meta, 2) }}</h3>
                        <p>Meta Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($total_vendido, 2) }}</h3>
                        <p>Total Vendido</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ number_format($porcentaje_promedio, 2) }}%</h3>
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
                                <th>Plaza</th>
                                <th>Tienda</th>
                                <th>Zona</th>
                                <th>Sucursal</th>
                                <th>Fecha</th>
                                <th class="text-right">Meta Total</th>
                                <th class="text-right">Días Total</th>
                                <th class="text-right">Valor Día</th>
                                <th class="text-right">Meta Día</th>
                                <th class="text-right">Total Vendido</th>
                                <th class="text-right">% Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultados as $item)
                            <tr>
                                <td>{{ $item->plaza }}</td>
                                <td>{{ $item->tienda }}</td>
                                <td>{{ $item->zona }}</td>
                                <td>{{ $item->sucursal }}</td>
                                <td>{{ $item->fecha }}</td>
                                <td class="text-right">${{ number_format($item->meta_total ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($item->dias_total ?? 0, 0) }}</td>
                                <td class="text-right">${{ number_format($item->valor_dia ?? 0, 2) }}</td>
                                <td class="text-right">${{ number_format($item->meta_dia ?? 0, 2) }}</td>
                                <td class="text-right">${{ number_format($item->total_vendido ?? 0, 2) }}</td>
                                <td class="text-right {{ ($item->porcentaje_cumplimiento ?? 0) >= 100 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($item->porcentaje_cumplimiento ?? 0, 2) }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold">TOTALES:</td>
                                <td class="text-right font-weight-bold">${{ number_format($resultados->sum('meta_total') ?? 0, 2) }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($resultados->sum('dias_total') ?? 0, 0) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($resultados->avg('valor_dia') ?? 0, 2) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($total_meta, 2) }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($total_vendido, 2) }}</td>
                                <td class="text-right font-weight-bold {{ $porcentaje_promedio >= 100 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($porcentaje_promedio, 2) }}%
                                </td>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <style>
        .text-right { text-align: right !important; }
        .text-success { color: #28a745 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-warning { color: #ffc107 !important; }
        .small-box .icon { font-size: 70px; }
        .table-responsive { overflow-x: auto; }
    </style>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            @if(count($resultados) > 0)
                $('#tabla-reportes').DataTable({
                    "pageLength": 25,
                    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
                    "order": [[0, 'asc'], [1, 'asc'], [4, 'asc']],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                    }
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
        });
    </script>
@stop