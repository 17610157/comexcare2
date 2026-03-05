@extends('adminlte::page')

@section('title', 'Dashboard ComexCare')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-chart-line text-primary"></i> Dashboard ComexCare</h1>
        <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2">
            <label class="mb-0"><strong>Del:</strong></label>
            <input type="date" name="fecha_inicio" value="{{ $fecha_inicio ?? date('Y-m-01') }}" class="form-control form-control-sm" style="width: 140px;">
            <label class="mb-0"><strong>Al:</strong></label>
            <input type="date" name="fecha_fin" value="{{ $fecha_fin ?? date('Y-m-d') }}" class="form-control form-control-sm" style="width: 140px;">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-calculator"></i> Calcular
            </button>
        </form>
    </div>
@stop

@section('content')
    @if(isset($error))
        <div class="alert alert-danger">
            <h5><i class="icon fas fa-ban"></i> Error</h5>
            {{ $error }}
        </div>
    @else
        <!-- Cards de Métricas Principales -->
        <div class="row">
            <!-- Ventas -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-info">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($ventas ?? 0, 0) }}</h3>
                        <p class="mb-0">Ventas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <!-- Devoluciones -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-danger">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($devoluciones ?? 0, 0) }}</h3>
                        <p class="mb-0">Devoluciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Venta Neta -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-success">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format(($ventas ?? 0) - ($devoluciones ?? 0), 0) }}</h3>
                        <p class="mb-0">Venta Neta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <!-- Meta -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-warning">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($meta ?? 0, 0) }}</h3>
                        <p class="mb-0">Meta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                </div>
            </div>

            <!-- Objetivo -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-primary">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($objetivo ?? 0, 0) }}</h3>
                        <p class="mb-0">Objetivo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-crosshairs"></i>
                    </div>
                </div>
            </div>

            <!-- Alcance -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-secondary">
                    <div class="inner p-2">
                        <h3 class="h5">{{ number_format($alcance ?? 0, 1) }}%</h3>
                        <p class="mb-0">Alcance</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>

            <!-- Tickets -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-dark">
                    <div class="inner p-2">
                        <h3 class="h5">{{ number_format($tickets ?? 0) }}</h3>
                        <p class="mb-0">Tickets</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>

            <!-- Ticket Promedio -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-indigo">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($ticket_promedio ?? 0, 0) }}</h3>
                        <p class="mb-0">Ticket Prom.</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
            </div>

            <!-- % Devoluciones -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-pink">
                    <div class="inner p-2">
                        <h3 class="h5">{{ number_format($porc_devoluciones ?? 0, 1) }}%</h3>
                        <p class="mb-0">% Dev.</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
            </div>

            <!-- Venta Contado -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-teal">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($venta_contado ?? 0, 0) }}</h3>
                        <p class="mb-0">Contado</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>

            <!-- Venta Crédito -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-olive">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($venta_credito ?? 0, 0) }}</h3>
                        <p class="mb-0">Crédito</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
            </div>

            <!-- Total Cartera -->
            <div class="col-lg-2 col-md-3 col-sm-6 col-12 mb-2 mb-lg-0">
                <div class="small-box bg-warning">
                    <div class="inner p-2">
                        <h3 class="h5">${{ number_format($cartera_total ?? 0, 0) }}</h3>
                        <p class="mb-0">Total Cartera</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Vendedores -->
        @php
            $vendedoresArray = is_array($vendedores) ? $vendedores : $vendedores->toArray();
            $totalVendedores = count($vendedoresArray);
        @endphp
        @if(!empty($vendedoresArray))
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users"></i> Ventas por Vendedor</h3>
                    </div>
                    <div class="card-body table-responsive" style="max-height: 400px;">
                        <table class="table table-bordered table-striped table-hover" id="vendedoresTable">
                            <thead class="thead-dark sticky-top">
                                <tr>
                                    <th>Clave Vendedor</th>
                                    <th>Tiendas</th>
                                    <th>Ventas</th>
                                    <th>Devoluciones</th>
                                    <th>Venta Neta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendedoresArray as $index => $v)
                                <tr class="{{ $index >= 5 ? 'vendedor-extra' : '' }}" {{ $index >= 5 ? 'style=display:none' : '' }}>
                                    <td>{{ $v['clave_vendedor'] }}</td>
                                    <td>{{ implode(', ', $v['tiendas']) }}</td>
                                    <td>${{ number_format($v['ventas'], 2) }}</td>
                                    <td>${{ number_format($v['devoluciones'], 2) }}</td>
                                    <td><strong>${{ number_format($v['ventas_net'], 2) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
        @if($totalVendedores > 5)
                        <div class="text-center mt-2">
                            <button class="btn btn-primary btn-sm" onclick="mostrarMasVendedores()">
                                Ver más ({{ $totalVendedores - 5 }} restantes)
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Ventas por Plaza y Tienda -->
        @php
            $ventasPlazaArray = is_array($ventas_plaza) ? $ventas_plaza : $ventas_plaza->toArray();
            $ventasTiendaArray = is_array($ventas_tienda) ? $ventas_tienda : $ventas_tienda->toArray();
            $plazaDataJson = json_encode($ventasPlazaArray);
            $tiendaDataJson = json_encode($ventasTiendaArray);
        @endphp
        @if(!empty($ventasPlazaArray) || !empty($ventasTiendaArray))
        <div class="row mt-3">
            @if(!empty($ventasPlazaArray))
            <div class="col-lg-6 col-md-6 col-12 mb-3">
                <div class="card bg-gradient-primary" style="position: relative;">
                    <div class="card-header border-0 ui-sortable-handle">
                        <h3 class="card-title">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            Ventas por Plaza
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-primary btn-sm" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                            <canvas id="plazasChart"></canvas>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="row text-center">
                            @php
                                $totalVentas = array_sum(array_column($ventasPlazaArray, 'ventas'));
                                $totalDevoluciones = array_sum(array_column($ventasPlazaArray, 'devoluciones'));
                                $totalNeto = array_sum(array_column($ventasPlazaArray, 'neto'));
                            @endphp
                            <div class="col-4">
                                <div class="text-white small">Ventas</div>
                                <div class="text-white font-weight-bold" style="font-size: 0.9rem;">${{ number_format($totalVentas, 0) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-white small">Devoluciones</div>
                                <div class="text-white font-weight-bold text-danger" style="font-size: 0.9rem;">${{ number_format($totalDevoluciones, 0) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-white small">Neto</div>
                                <div class="text-white font-weight-bold text-success" style="font-size: 0.9rem;">${{ number_format($totalNeto, 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if(!empty($ventasTiendaArray))
            <div class="col-lg-6 col-md-6 col-12 mb-3">
                <div class="card bg-gradient-info" style="position: relative;">
                    <div class="card-header border-0 ui-sortable-handle">
                        <h3 class="card-title">
                            <i class="fas fa-store mr-1"></i>
                            Ventas por Tienda
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-info btn-sm" data-card-widget="collapse" title="Collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display: block;">
                        <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                            <canvas id="tiendasChart"></canvas>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="row text-center">
                            @php
                                $totalVentasTienda = array_sum(array_column($ventasTiendaArray, 'ventas'));
                                $totalDevTienda = array_sum(array_column($ventasTiendaArray, 'devoluciones'));
                                $totalNetoTienda = array_sum(array_column($ventasTiendaArray, 'neto'));
                            @endphp
                            <div class="col-4">
                                <div class="text-white small">Ventas</div>
                                <div class="text-white font-weight-bold" style="font-size: 0.9rem;">${{ number_format($totalVentasTienda, 0) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-white small">Devoluciones</div>
                                <div class="text-white font-weight-bold text-danger" style="font-size: 0.9rem;">${{ number_format($totalDevTienda, 0) }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-white small">Neto</div>
                                <div class="text-white font-weight-bold text-success" style="font-size: 0.9rem;">${{ number_format($totalNetoTienda, 0) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var plazaData = {!! $plazaDataJson !!};
            if (plazaData.length > 0) {
                var labelsPlaza = plazaData.map(function(p) { return p.plaza; });
                var ventasPlaza = plazaData.map(function(p) { return p.ventas; });
                var devPlaza = plazaData.map(function(p) { return p.devoluciones; });
                var netoPlaza = plazaData.map(function(p) { return p.neto; });
                
                var ctxPlaza = document.getElementById('plazasChart').getContext('2d');
                new Chart(ctxPlaza, {
                    type: 'bar',
                    data: {
                        labels: labelsPlaza,
                        datasets: [
                            {
                                label: 'Ventas',
                                data: ventasPlaza,
                                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Devoluciones',
                                data: devPlaza,
                                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                                borderColor: 'rgba(220, 53, 69, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Neto',
                                data: netoPlaza,
                                backgroundColor: 'rgba(23, 162, 184, 0.8)',
                                borderColor: 'rgba(23, 162, 184, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: false, ticks: { color: 'white' } },
                            y: { stacked: false, ticks: { color: 'white', callback: function(value) { return '$' + value.toLocaleString(); } } }
                        },
                        plugins: {
                            legend: { labels: { color: 'white' } },
                            tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': $' + context.raw.toLocaleString('es-MX', {minimumFractionDigits: 2}); } } }
                        }
                    }
                });
            }

            var tiendaData = {!! $tiendaDataJson !!};
            if (tiendaData.length > 0) {
                var labelsTienda = tiendaData.map(function(t) { return t.tienda; });
                var ventasTienda = tiendaData.map(function(t) { return t.ventas; });
                var devTienda = tiendaData.map(function(t) { return t.devoluciones; });
                var netoTienda = tiendaData.map(function(t) { return t.neto; });
                
                var ctxTienda = document.getElementById('tiendasChart').getContext('2d');
                new Chart(ctxTienda, {
                    type: 'bar',
                    data: {
                        labels: labelsTienda,
                        datasets: [
                            {
                                label: 'Ventas',
                                data: ventasTienda,
                                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                                borderColor: 'rgba(40, 167, 69, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Devoluciones',
                                data: devTienda,
                                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                                borderColor: 'rgba(220, 53, 69, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Neto',
                                data: netoTienda,
                                backgroundColor: 'rgba(23, 162, 184, 0.8)',
                                borderColor: 'rgba(23, 162, 184, 1)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { stacked: false, ticks: { color: 'white' } },
                            y: { stacked: false, ticks: { color: 'white', callback: function(value) { return '$' + value.toLocaleString(); } } }
                        },
                        plugins: {
                            legend: { labels: { color: 'white' } },
                            tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': $' + context.raw.toLocaleString('es-MX', {minimumFractionDigits: 2}); } } }
                        }
                    }
                });
            }
        });
        </script>
        @endif

        <!-- Accesos Rápidos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-link"></i> Accesos Rápidos</h3>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6">
                                <a href="{{ route('reportes.metas-ventas') }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-chart-bar"></i> Metas Ventas
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="{{ route('reportes.metas-matricial.index') }}" class="btn btn-info btn-block">
                                    <i class="fas fa-table"></i> Metas Matricial
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="{{ route('reportes.vendedores') }}" class="btn btn-success btn-block">
                                    <i class="fas fa-users"></i> Vendedores
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="{{ route('reportes.vendedores.matricial') }}" class="btn btn-warning btn-block">
                                    <i class="fas fa-users"></i> Vendedores Matricial
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('css')
    <style>
        .progress {
            height: 30px;
        }
        .progress-bar {
            font-size: 14px;
            line-height: 30px;
        }
        .small-box .inner {
            min-height: 60px;
        }
        .small-box h3 {
            font-size: 1.25rem;
            margin: 0 0 5px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .small-box p {
            font-size: 0.85rem;
            margin: 0;
        }
        .small-box .icon {
            width: 50px;
            height: 50px;
            font-size: 2rem;
            right: 10px;
            top: 10px;
        }
        @media (max-width: 576px) {
            .small-box {
                margin-bottom: 0.5rem;
            }
            .small-box .inner {
                min-height: 50px;
                padding: 10px !important;
            }
            .small-box h3 {
                font-size: 1rem;
            }
            .small-box p {
                font-size: 0.75rem;
            }
            .small-box .icon {
                width: 35px;
                height: 35px;
                font-size: 1.5rem;
                right: 5px;
                top: 5px;
            }
            .small-box .icon i {
                font-size: 1.5rem;
            }
        }
        .plaza-map-item {
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 0.85;
        }
        .plaza-map-item:hover {
            transform: scale(1.05);
            opacity: 1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .bg-gradient-primary {
            background: linear-gradient(to right, #1e3c72 0%, #2a5298 100%) !important;
            color: white;
        }
        #mexico-map {
            width: 100%;
            height: 400px;
            background-color: #f8f9fa;
        }
        .jqvmap-region {
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .jqvmap-region:hover {
            opacity: 0.8;
        }
        .map-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 14px;
            z-index: 1000;
            pointer-events: none;
            display: none;
        }
    </style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function mostrarMasVendedores() {
    var elementos = document.querySelectorAll('.vendedor-extra');
    elementos.forEach(function(el) {
        el.style.display = 'table-row';
    });
    event.target.style.display = 'none';
}
</script>
@endsection
