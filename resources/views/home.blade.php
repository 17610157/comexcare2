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
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>${{ number_format($ventas ?? 0, 2) }}</h3>
                        <p>Ventas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>

            <!-- Devoluciones -->
            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>${{ number_format($devoluciones ?? 0, 2) }}</h3>
                        <p>Devoluciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Venta Neta -->
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${{ number_format(($ventas ?? 0) - ($devoluciones ?? 0), 2) }}</h3>
                        <p>Venta Neta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>

            <!-- Meta -->
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($meta ?? 0, 2) }}</h3>
                        <p>Meta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                </div>
            </div>

            <!-- Objetivo -->
            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>${{ number_format($objetivo ?? 0, 2) }}</h3>
                        <p>Objetivo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-crosshairs"></i>
                    </div>
                </div>
            </div>

            <!-- Alcance -->
            <div class="col-lg-2 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ number_format($alcance ?? 0, 1) }}%</h3>
                        <p>Alcance</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-pie"></i>
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
        <script>
        function mostrarMasVendedores() {
            var elementos = document.querySelectorAll('.vendedor-extra');
            elementos.forEach(function(el) {
                el.style.display = 'table-row';
            });
            event.target.style.display = 'none';
        }
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
    </style>
@stop
