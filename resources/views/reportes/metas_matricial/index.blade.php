@extends('adminlte::page')

@section('title', 'Metas de Ventas - Reporte Matricial')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-chart-bar text-success"></i> Metas de Ventas - Matricial</h1>
    <div>
        <form method="POST" action="{{ route('reportes.metas-matricial.export') }}" style="display: inline;">
            @csrf
            <input type="hidden" name="fecha_inicio" value="{{ $fecha_inicio }}">
            <input type="hidden" name="fecha_fin" value="{{ $fecha_fin }}">
            <input type="hidden" name="plaza" value="{{ $plaza }}">
            <input type="hidden" name="tienda" value="{{ $tienda }}">
            <input type="hidden" name="zona" value="{{ $zona }}">
            <button type="button" class="btn btn-success" id="btn-export-excel">
                <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
        </form>
        <a href="{{ url()->current() }}" class="btn btn-primary ml-2">
            <i class="fas fa-sync-alt"></i> Recargar
        </a>
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
                    <label>Plaza</label>
                    <input type="text" name="plaza" value="{{ $plaza }}" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-md-2">
                    <label>Tienda</label>
                    <input type="text" name="tienda" value="{{ $tienda }}" class="form-control" placeholder="Opcional">
                </div>
                <div class="col-md-2">
                    <label>Zona</label>
                    <input type="text" name="zona" value="{{ $zona }}" class="form-control" placeholder="Opcional">
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
<script>
$(document).ready(function() {
    // Exportar Excel
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
});
</script>
@endsection