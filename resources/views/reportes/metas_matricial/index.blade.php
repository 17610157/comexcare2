@extends('adminlte::page')

@section('title', 'Metas de Ventas - Reporte Matricial')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-chart-bar text-success"></i> Metas de Ventas - Matricial</h1>
    <div>
        @hasPermission('reportes.metas_matricial.exportar')
        <form id="export-excel-form" method="POST" action="{{ route('reportes.metas-matricial.export') }}" style="display: inline;">
            @csrf
            <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
            <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
            <input type="hidden" name="plaza" value="{{ $plaza }}">
            <input type="hidden" name="tienda" value="{{ $tienda }}">
            <input type="hidden" name="zona" value="{{ $zona }}">
            <button type="button" class="btn btn-success" id="btn-export-excel">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <form id="export-pdf-form" method="POST" action="{{ route('reportes.metas-matricial.export.pdf') }}" style="display: inline;">
                @csrf
                <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
                <input type="hidden" name="plaza" value="{{ $plaza }}">
                <input type="hidden" name="tienda" value="{{ $tienda }}">
                <input type="hidden" name="zona" value="{{ $zona }}">
                <button type="button" class="btn btn-danger" id="btn-export-pdf">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
            </form>
        </form>
        @endhasPermission
        @hasPermission('reportes.metas_matricial.ver')
        <a href="{{ url()->current() }}" class="btn btn-primary ml-2">
            <i class="fas fa-sync-alt"></i> Recargar
        </a>
        @endhasPermission
    </div>
</div>
@stop

@section('content')
<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filtros</h3>
    </div>
    <form method="GET" action="{{ route('reportes.metas-matricial.index') }}">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ $fecha_inicio }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="{{ $fecha_fin }}" class="form-control">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label>Zona</label>
                    <input type="text" name="zona" value="{{ $zona ?? '' }}" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
        </div>
    </form>
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

@if(isset($datos) && !empty($datos['tiendas']))
<!-- Tabla Matricial Jerárquica -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-table"></i> Vista Matricial Jerárquica
            @if(isset($tiempo_carga))
                <span class="badge badge-secondary ml-2">{{ $tiempo_carga }}ms</span>
            @endif
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="matriz-metas">
                <thead class="thead-dark">
                     <tr>
                         <th class="text-center" style="min-width: 150px;">Categoría / Fecha</th>
                         @foreach($datos['tiendas'] as $tienda)
                             <th class="text-center" style="min-width: 120px;">
                                 {{ $tienda }}
                                 <br><small class="text-muted">{{ $datos['matriz']['info'][$tienda]['zona'] }}</small>
                             </th>
                         @endforeach
                         <th class="text-center" style="min-width: 120px;">Total</th>
                     </tr>
                </thead>
                <tbody>
                     <!-- Fila 1: Plazas -->
                     <tr class="table-primary">
                         <td class="font-weight-bold">🏢 Plaza</td>
                         @foreach($datos['tiendas'] as $tienda)
                             <td class="text-center font-weight-bold">
                                 {{ $datos['matriz']['info'][$tienda]['plaza'] }}
                             </td>
                         @endforeach
                         <td class="text-center font-weight-bold">-</td>
                     </tr>

                     <!-- Fila 2: Zonas -->
                     <tr class="table-info">
                         <td class="font-weight-bold">📍 Zona</td>
                         @foreach($datos['tiendas'] as $tienda)
                             <td class="text-center font-weight-bold">
                                 {{ $datos['matriz']['info'][$tienda]['zona'] }}
                             </td>
                         @endforeach
                         <td class="text-center font-weight-bold">-</td>
                     </tr>

                     <!-- Filas de Totales por Día -->
                     @foreach($datos['fechas'] as $fecha)
                         @php
                             $suma_fecha = 0;
                             foreach($datos['tiendas'] as $tienda) {
                                 $suma_fecha += $datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0;
                             }
                         @endphp
                         <tr>
                             <td class="font-weight-bold text-right">
                                 💰 Total {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}
                             </td>
                             @foreach($datos['tiendas'] as $tienda)
                                 <td class="text-right">
                                     ${{ number_format($datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0, 2) }}
                                 </td>
                             @endforeach
                             <td class="text-right font-weight-bold">
                                 ${{ number_format($suma_fecha, 2) }}
                             </td>
                         </tr>
                     @endforeach

                    <!-- Fila de Suma de los Días Consultados -->
                    @php
                        $suma_totales = 0;
                        foreach($datos['tiendas'] as $tienda) {
                            $suma_totales += $datos['matriz']['totales'][$tienda]['total'] ?? 0;
                        }
                    @endphp
                    <tr class="table-warning">
                        <td class="font-weight-bold">📊 Suma de los Días Consultados</td>
                        @foreach($datos['tiendas'] as $tienda)
                            <td class="text-right font-weight-bold">
                                ${{ number_format($datos['matriz']['totales'][$tienda]['total'] ?? 0, 2) }}
                            </td>
                        @endforeach
                        <td class="text-right font-weight-bold">
                            ${{ number_format($suma_totales, 2) }}
                        </td>
                    </tr>

                    <!-- Fila Objetivo -->
                    @php
                        $suma_objetivo = 0;
                        foreach($datos['tiendas'] as $tienda) {
                            if (($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0) {
                                $suma_objetivo += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
                            }
                        }
                    @endphp
                    <tr class="table-light">
                        <td class="font-weight-bold">🎯 Objetivo</td>
                        @foreach($datos['tiendas'] as $tienda)
                            <td class="text-right font-weight-bold">
                                @if(($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0)
                                    ${{ number_format($datos['matriz']['totales'][$tienda]['objetivo'] ?? 0, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td class="text-right font-weight-bold">
                            ${{ number_format($suma_objetivo, 2) }}
                        </td>
                    </tr>

                    <!-- Fila Suma Valor Día -->
                    @php
                        $suma_valor_dia_total = 0;
                        foreach($datos['tiendas'] as $tienda) {
                            $suma_valor_dia_total += $datos['matriz']['info'][$tienda]['suma_valor_dia'] ?? 0;
                        }
                    @endphp
                    <tr class="table-secondary">
                        <td class="font-weight-bold">💵 Suma Valor Día</td>
                        @foreach($datos['tiendas'] as $tienda)
                            <td class="text-right font-weight-bold">
                                ${{ number_format($datos['matriz']['info'][$tienda]['suma_valor_dia'] ?? 0, 2) }}
                            </td>
                        @endforeach
                        <td class="text-right font-weight-bold">
                            ${{ number_format($suma_valor_dia_total, 2) }}
                        </td>
                    </tr>

                    <!-- Fila Días Totales -->
                    <tr class="table-light">
                        <td class="font-weight-bold">📅 Días Totales</td>
                        @foreach($datos['tiendas'] as $tienda)
                            <td class="text-center font-weight-bold">
                                {{ $datos['matriz']['info'][$tienda]['dias_totales'] ?? $datos['dias_totales'] }}
                            </td>
                        @endforeach
                        <td class="text-center font-weight-bold">-</td>
                    </tr>

                    <!-- Fila Porcentaje Total -->
                    @php
                        $suma_totales_global = 0;
                        $suma_objetivos_global = 0;
                        foreach($datos['tiendas'] as $tienda) {
                            $suma_totales_global += $datos['matriz']['totales'][$tienda]['total'] ?? 0;
                            if (($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0) {
                                $suma_objetivos_global += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
                            }
                        }
                        $porcentaje_global = $suma_objetivos_global > 0 ? ($suma_totales_global / $suma_objetivos_global) * 100 : 0;
                        $clase_global = $porcentaje_global >= 100 ? 'text-success font-weight-bold' :
                                       ($porcentaje_global >= 80 ? 'text-warning' : 'text-danger');
                    @endphp
                    <tr class="table-info">
                        <td class="font-weight-bold">📈 Porcentaje Total</td>
                        @foreach($datos['tiendas'] as $tienda)
                            @php
                                $porcentaje_total = $datos['matriz']['totales'][$tienda]['porcentaje_total'] ?? 0;
                                $clase = $porcentaje_total >= 100 ? 'text-success font-weight-bold' :
                                        ($porcentaje_total >= 80 ? 'text-warning' : 'text-danger');
                            @endphp
                            <td class="text-right {{ $clase }}">
                                @if(($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0)
                                    {{ number_format($porcentaje_total, 1) }}%
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td class="text-right {{ $clase_global }}">
                            {{ number_format($porcentaje_global, 1) }}%
                        </td>
                    </tr>

                    <!-- Última Fila: Meta Total -->
                    @php
                        $suma_meta_total = 0;
                        foreach($datos['tiendas'] as $tienda) {
                            $suma_meta_total += $datos['matriz']['totales'][$tienda]['meta_total'] ?? 0;
                        }
                    @endphp
                    <tr class="table-success">
                        <td class="font-weight-bold">🏆 Meta Total</td>
                        @foreach($datos['tiendas'] as $tienda)
                            <td class="text-right font-weight-bold">
                                ${{ number_format($datos['matriz']['totales'][$tienda]['meta_total'] ?? 0, 2) }}
                            </td>
                        @endforeach
                        <td class="text-right font-weight-bold">
                            ${{ number_format($suma_meta_total, 2) }}
                        </td>
                    </tr>


                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted">
            Matriz jerárquica: Plaza → Zona → Totales Diarios → Suma de los Días Consultados → Objetivo → Suma Valor Día → Días Totales → Porcentaje Total → Meta Total
            | {{ count($datos['tiendas']) }} tiendas | {{ count($datos['fechas']) }} días
        </small>
    </div>
</div>
@elseif(request()->has('fecha_inicio'))
<div class="alert alert-warning">
    <i class="icon fas fa-exclamation-triangle"></i>
    No se encontraron datos para los filtros seleccionados.
</div>
@endif
@stop

@section('js')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Exportar Excel
    $('#btn-export-excel').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Exportar a Excel',
            text: '¿Desea descargar el archivo Excel?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Si, descargar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                console.log('Submitting Excel form');
                $('#export-excel-form').submit();
            } else {
                console.log('Excel cancelled');
            }
        });
    });

    // Exportar PDF
    $('#btn-export-pdf').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Exportar a PDF',
            text: '¿Desea descargar el archivo PDF?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Si, descargar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                console.log('Submitting PDF form');
                // $('#export-pdf-form').submit();
                alert('PDF confirmado, submitiendo...');
                $('#export-pdf-form')[0].submit();
            } else {
                console.log('PDF cancelled');
            }
        });
    });
});
</script>
@endsection