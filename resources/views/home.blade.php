@extends('adminlte::page')

@section('title', 'Dashboard ComexCare')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-line text-primary"></i> Dashboard ComexCare</h1>
        <span class="badge badge-info">Periodo: {{ $periodo ?? date('Y-m') }}</span>
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
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>${{ number_format($ventas ?? 0, 2) }}</h3>
                        <p>Ventas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <a href="{{ route('reportes.metas-ventas') }}" class="small-box-footer">
                        Ver detalle <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Devoluciones -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>${{ number_format($devoluciones ?? 0, 2) }}</h3>
                        <p>Devoluciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <a href="{{ route('reportes.metas-ventas') }}" class="small-box-footer">
                        Ver detalle <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Neto -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>${{ number_format($neto ?? 0, 2) }}</h3>
                        <p>Neto</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <a href="{{ route('reportes.metas-ventas') }}" class="small-box-footer">
                        Ver detalle <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Meta -->
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>${{ number_format($meta ?? 0, 2) }}</h3>
                        <p>Meta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <a href="{{ route('reportes.metas-ventas') }}" class="small-box-footer">
                        Ver detalle <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Alcance -->
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie"></i> Alcance de Meta
                        </h3>
                    </div>
                    <div class="card-body">
                        @php
                            $alcanceValor = $alcance ?? 0;
                            $porcentaje = min($alcanceValor, 100);
                            $color = 'bg-success';
                            if ($alcanceValor < 50) $color = 'bg-danger';
                            elseif ($alcanceValor < 80) $color = 'bg-warning';
                        @endphp
                        <div class="progress mb-3">
                            <div class="progress-bar {{ $color }}" role="progressbar" 
                                 style="width: {{ $porcentaje }}%" 
                                 aria-valuenow="{{ $alcanceValor }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                {{ number_format($alcanceValor, 1) }}%
                            </div>
                        </div>
                        <p class="text-center">
                            <strong>Alcance: {{ number_format($alcanceValor, 2) }}%</strong>
                            @if($alcanceValor >= 100)
                                <span class="badge badge-success ml-2"><i class="fas fa-check"></i> Meta Cumplida</span>
                            @elseif($alcanceValor >= 80)
                                <span class="badge badge-warning ml-2"><i class="fas fa-exclamation"></i> Cerca de Meta</span>
                            @else
                                <span class="badge badge-danger ml-2"><i class="fas fa-times"></i> Por debajo de Meta</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Período -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-calendar"></i> Período</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Inicio:</strong> {{ $fecha_inicio ?? date('Y-m-01') }}</p>
                        <p><strong>Fin:</strong> {{ $fecha_fin ?? date('Y-m-d') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-filter"></i> Filtros Aplicados</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Plazas:</strong> {{ !empty($plaza) ? $plaza : 'Todas' }}</p>
                        <p><strong>Tiendas:</strong> {{ !empty($tienda) ? $tienda : 'Todas' }}</p>
                    </div>
                </div>
            </div>
        </div>

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
