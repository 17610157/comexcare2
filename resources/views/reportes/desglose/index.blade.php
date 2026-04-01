@extends('adminlte::page')

@section('title', 'Desglose de Ventas')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-list-alt text-info"></i> Desglose de Ventas por Día</h1>
    <a href="{{ url()->current() }}" class="btn btn-primary">
        <i class="fas fa-sync-alt"></i> Recargar
    </a>
</div>
@stop

@section('content')
<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filtros</h3>
    </div>
    <form method="GET" action="{{ route('reportes.desglose.index') }}">
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
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label>Zona</label>
                    <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
                        <div class="form-check">
                            <input type="checkbox" id="select_all_zonas" class="form-check-input"
                                   {{ empty($zona) ? 'checked' : '' }}>
                            <label for="select_all_zonas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
                        </div>
                        @php
                            $zonas = range(1, 10);
                            $zonaArray = $zona ? explode(',', $zona) : [];
                        @endphp
                        @foreach($zonas as $z)
                        <div class="form-check">
                            <input type="checkbox" name="zona[]" value="{{ $z }}" id="zona_{{ $z }}" class="form-check-input zona-checkbox"
                                   {{ in_array((string)$z, $zonaArray) || empty($zonaArray) ? 'checked' : '' }}>
                            <label for="zona_{{ $z }}" class="form-check-label">{{ $z }}</label>
                        </div>
                        @endforeach
                    </div>
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

@if(isset($agrupado) && !empty($agrupado))
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="desglose-table">
                <thead class="thead-dark">
                    <tr>
                        <th class="text-center" style="min-width: 80px;">CLAVE</th>
                        <th class="text-center" style="min-width: 80px;">TIPO</th>
                        @foreach($fechas as $fecha)
                            <th class="text-center" style="min-width: 90px;">{{ \Carbon\Carbon::parse($fecha)->format('d/m') }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($agrupado as $key => $data)
                    <!-- Fila Contado -->
                    <tr>
                        <td rowspan="2" class="font-weight-bold text-center align-middle">{{ $data['tienda'] }}</td>
                        <td class="font-weight-bold bg-info">Contado</td>
                        @foreach($fechas as $fecha)
                            <td class="text-right">
                                @php $valor = $data['contado'][$fecha] ?? 0; @endphp
                                @if($valor > 0)
                                    ${{ number_format($valor, 2) }}
                                @else
                                    <span class="text-muted">$0.00</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <!-- Fila Crédito -->
                    <tr>
                        <td class="font-weight-bold bg-warning">Crédito</td>
                        @foreach($fechas as $fecha)
                            <td class="text-right">
                                @php $valor = $data['credito'][$fecha] ?? 0; @endphp
                                @if($valor > 0)
                                    ${{ number_format($valor, 2) }}
                                @else
                                    <span class="text-muted">$0.00</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <!-- Fila Totales por tienda -->
                    <tr class="table-secondary">
                        <td colspan="2" class="font-weight-bold text-right">{{ $data['tienda'] }} - Total:</td>
                        @foreach($fechas as $fecha)
                            @php 
                                $contado = $data['contado'][$fecha] ?? 0;
                                $credito = $data['credito'][$fecha] ?? 0;
                                $total = $contado + $credito;
                            @endphp
                            <td class="text-right font-weight-bold">
                                @if($total > 0)
                                    ${{ number_format($total, 2) }}
                                @else
                                    <span class="text-muted">$0.00</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    <tr><td colspan="{{ count($fechas) + 2 }}" style="height: 10px;"></td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <small class="text-muted">
            {{ count($agrupado) }} tiendas | {{ count($fechas) }} días
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
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
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
</script>
@stop
