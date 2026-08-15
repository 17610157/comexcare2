@extends('adminlte::page')

@section('title', 'Comexcare')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1><i class="fas fa-chart-line text-primary"></i> Panel de Control</h1>
@stop

@section('css')
<style>
    .dash-card { margin-bottom: 1rem; }
    .info-box { min-height: 70px; margin-bottom: 0.5rem; }
    .info-box-icon { font-size: 1.4rem; width: 55px; line-height: 55px; }
    .info-box-number { font-size: 1.15rem; font-weight: 600; }
    #chart-fleet-plaza, #chart-agent, #chart-pvsi, #chart-distributions, #chart-receptions {
        height: 240px;
    }
    .table-sm td, .table-sm th { padding: 0.35rem 0.45rem; }
    .progress { margin-bottom: 0.15rem; }
    #tbl-distributions, #tbl-receptions, #tbl-tokens, #tbl-audit, #tbl-syncs { margin-bottom: 0; }
</style>
@stop

@section('content')

<div class="card card-outline card-primary">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <label class="mb-1" for="filter-plaza">Plaza</label>
                <select id="filter-plaza" class="form-control form-control-sm">
                    <option value="">Todas</option>
                    @foreach($filters['plazas'] ?? [] as $plz)
                        <option value="{{ $plz }}" @selected($selectedPlaza === $plz)>{{ $plz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-3 mb-2 mb-lg-0">
                <label class="mb-1" for="filter-window">Ventana en línea</label>
                <select id="filter-window" class="form-control form-control-sm">
                    @foreach($onlineWindows as $w)
                        <option value="{{ $w }}" @selected(($online_window_minutes ?? 5) == $w)>
                            {{ $w <= 60 ? $w.' min' : round($w / 60).' h' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-lg-2 mb-2 mb-lg-0">
                <label class="mb-1 d-block">&nbsp;</label>
                <button type="button" id="btn-refresh" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-sync"></i> Refrescar
                </button>
            </div>
            <div class="col-6 col-lg-4 text-lg-right">
                <label class="mb-1 d-block d-lg-none">&nbsp;</label>
                <span id="ws-status" class="badge badge-secondary mr-2"><i class="fas fa-circle-notch fa-spin"></i> Conectando...</span>
                <small id="last-updated" class="text-muted d-block d-lg-inline"></small>
            </div>
        </div>
    </div>
</div>

{{-- Flota --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="kpi-computers-online">0</h3>
                <p>Computadoras En Línea</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="/admin/computers" class="small-box-footer">Detalle <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="kpi-computers-offline">0</h3>
                <p>Computadoras Fuera de Línea</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <a href="/admin/computers" class="small-box-footer">Detalle <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="kpi-computers-total">0</h3>
                <p>Total Computadoras</p>
            </div>
            <div class="icon"><i class="fas fa-desktop"></i></div>
            <a href="/admin/computers" class="small-box-footer">Detalle <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="kpi-computers-pct">0%</h3>
                <p>% En Línea</p>
            </div>
            <div class="icon"><i class="fas fa-signal"></i></div>
            <a href="/admin/computers" class="small-box-footer">Detalle <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

{{-- Gráficas --}}
<div class="row mt-2">
    <div class="col-lg-6">
        <div class="card card-outline card-primary dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-desktop"></i> Computadoras por Plaza</h3>
                <div class="card-tools"><span class="badge badge-secondary" id="fleet-window-label"></span></div>
            </div>
            <div class="card-body"><canvas id="chart-fleet-plaza"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-outline card-info dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-microchip"></i> Versiones de Agente Instaladas</h3>
            </div>
            <div class="card-body"><canvas id="chart-agent"></canvas></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card card-outline card-info dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-code"></i> Versiones PVSI Instaladas</h3>
            </div>
            <div class="card-body"><canvas id="chart-pvsi"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="kpi-agent-active">0</h3>
                        <p>Agente Versión Activa</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="kpi-agent-outdated">0</h3>
                        <p>Agente Desactualizado</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-lg-4 col-12">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="kpi-agent-with-agent">0</h3>
                        <p>Con Agente</p>
                    </div>
                    <div class="icon"><i class="fas fa-robot"></i></div>
                </div>
            </div>
        </div>
        <div class="card card-outline card-info dash-card">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Versión activa actual:</span>
                    <span id="agent-active-version" class="badge badge-success text-lg">—</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Distribuciones y Recepciones --}}
<div class="row mt-2">
    <div class="col-lg-6">
        <div class="card card-outline card-primary dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-download"></i> Distribuciones</h3>
                <div class="card-tools">
                    <a href="/admin/distributions" class="btn btn-tool"><i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-cog fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">En Progreso</span>
                                <span class="info-box-number" id="kpi-distributions-in_progress">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-check fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Completadas</span>
                                <span class="info-box-number" id="kpi-distributions-completed">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-times fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Fallidas</span>
                                <span class="info-box-number" id="kpi-distributions-failed">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-layer-group fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total</span>
                                <span class="info-box-number" id="kpi-distributions-total">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-5"><canvas id="chart-distributions"></canvas></div>
                    <div class="col-md-7">
                        <table class="table table-sm table-hover" id="tbl-distributions">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th class="text-center">Objetivos</th>
                                    <th style="min-width:130px">Progreso</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-outline card-warning dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-import"></i> Recepciones</h3>
                <div class="card-tools">
                    <a href="/admin/reception" class="btn btn-tool"><i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-cog fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">En Progreso</span>
                                <span class="info-box-number" id="kpi-receptions-in_progress">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-check fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Completadas</span>
                                <span class="info-box-number" id="kpi-receptions-completed">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-times fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Fallidas</span>
                                <span class="info-box-number" id="kpi-receptions-failed">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-layer-group fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total</span>
                                <span class="info-box-number" id="kpi-receptions-total">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-5"><canvas id="chart-receptions"></canvas></div>
                    <div class="col-md-7">
                        <table class="table table-sm table-hover" id="tbl-receptions">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Estado</th>
                                    <th class="text-center">Objetivos</th>
                                    <th style="min-width:130px">Progreso</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Autorizaciones y Sistema --}}
<div class="row mt-2">
    <div class="col-lg-6">
        <div class="card card-outline card-secondary dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key"></i> Autorizaciones</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-clock fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pendientes</span>
                                <span class="info-box-number" id="kpi-auth-pending">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-check-double fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Usados</span>
                                <span class="info-box-number" id="kpi-auth-used">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-hourglass-end fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Expirados</span>
                                <span class="info-box-number" id="kpi-auth-expired">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-key fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total</span>
                                <span class="info-box-number" id="kpi-auth-total">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <table class="table table-sm table-hover" id="tbl-tokens">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Creación</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-outline card-dark dash-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt"></i> Monitoreo y Sistema</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-file-alt fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Archivos Monit.</span>
                                <span class="info-box-number" id="kpi-mon-files">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-primary"><i class="fas fa-list fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Listas</span>
                                <span class="info-box-number" id="kpi-mon-lists">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-fingerprint fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Hashes RBF</span>
                                <span class="info-box-number" id="kpi-mon-hashes">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-tasks fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Cmd. Pendientes</span>
                                <span class="info-box-number" id="kpi-sys-cmds-pending">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Fallos 24h</span>
                                <span class="info-box-number" id="kpi-sys-failed-24h">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-clock fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Jobs en Cola</span>
                                <span class="info-box-number" id="kpi-sys-jobs">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-secondary"><i class="fas fa-file-medical fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Fallos (jobs)</span>
                                <span class="info-box-number" id="kpi-sys-failed-jobs">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-database fa-fw"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Sinc. Reportes</span>
                                <span class="info-box-number" id="kpi-sys-syncs">0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="fas fa-history"></i> Actividad reciente</h6>
                        <div style="max-height:240px; overflow-y:auto;">
                            <table class="table table-sm table-hover" id="tbl-audit">
                                <thead>
                                    <tr><th>Usuario</th><th>Acción</th><th>Hora</th></tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted"><i class="fas fa-sync-alt"></i> Sincronización de reportes</h6>
                        <table class="table table-sm table-hover" id="tbl-syncs">
                            <thead>
                                <tr><th>Fuente</th><th class="text-center">Filas</th><th>Última actualización</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
    window.Laravel = window.Laravel || {};
    window.Laravel.broadcastingPort = 6001;
    window.Laravel.broadcastingHost = window.location.hostname;
</script>
@vite(['resources/js/app.js'])
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer id="chartjs-cdn"></script>
<script>
(function () {
    'use strict';

    var STATUS_META = {
        completed:   ['success', 'Completado'],
        failed:      ['danger', 'Fallido'],
        in_progress: ['primary', 'En Progreso'],
        pending:     ['warning', 'Pendiente'],
        stopped:     ['secondary', 'Detenido'],
        cancelled:   ['secondary', 'Cancelado'],
        downloading: ['info', 'Descargando'],
        sent:        ['info', 'Enviado'],
        running:     ['primary', 'Ejecutando']
    };
    var COLOR_BY_BADGE = {
        success: '#28a745', danger: '#dc3545', primary: '#007bff',
        warning: '#ffc107', secondary: '#6c757d', info: '#17a2b8'
    };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function fmtNum(n) {
        return Number(n || 0).toLocaleString('es-MX');
    }

    function fmtDateTime(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '—';
        return d.toLocaleString('es-MX', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
    }

    function statusMeta(s) {
        return STATUS_META[s] || ['secondary', s];
    }

    function statusBadge(s) {
        var m = statusMeta(s);
        return '<span class="badge badge-' + m[0] + '">' + esc(m[1]) + '</span>';
    }

    function setNum(id, n) {
        var el = document.getElementById(id);
        if (el) el.textContent = fmtNum(n);
    }

    var charts = {};
    var lastData = null;

    function renderCharts(data) {
        if (typeof window.Chart !== 'undefined') {
            try {
                updateFleetChart(data.computers || {});
                updateVersionChart('chart-agent', (data.computers || {}).agent_versions);
                updateVersionChart('chart-pvsi', (data.computers || {}).pvsi_versions);
                var d = data.distributions || {}, r = data.receptions || {};
                var sd = statusData(d.by_status || {}, ['in_progress', 'completed', 'failed', 'pending', 'stopped']);
                updateDoughnut('chart-distributions', sd.labels, sd.values, sd.colors);
                sd = statusData(r.by_status || {}, ['in_progress', 'completed', 'failed', 'pending', 'stopped']);
                updateDoughnut('chart-receptions', sd.labels, sd.values, sd.colors);
            } catch (e) { console.warn('Chart render error:', e); }
        }
    }

    function updateDoughnut(canvasId, labels, values, colors) {
        var el = document.getElementById(canvasId);
        if (!el) return;
        if (charts[canvasId]) {
            var c = charts[canvasId];
            c.data.labels = labels;
            c.data.datasets[0].data = values;
            c.data.datasets[0].backgroundColor = colors;
            c.update();
            return;
        }
        charts[canvasId] = new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    }

    function updateFleetChart(computers) {
        var el = document.getElementById('chart-fleet-plaza');
        if (!el) return;
        var byPlaza = computers.by_plaza || [];
        var labels = byPlaza.map(function (p) { return p.plaza; });
        var online = byPlaza.map(function (p) { return p.online; });
        var offline = byPlaza.map(function (p) { return p.offline; });
        if (charts['chart-fleet-plaza']) {
            var c = charts['chart-fleet-plaza'];
            c.data.labels = labels;
            c.data.datasets[0].data = online;
            c.data.datasets[1].data = offline;
            c.update();
            return;
        }
        charts['chart-fleet-plaza'] = new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'En línea', data: online, backgroundColor: '#28a745' },
                    { label: 'Fuera de línea', data: offline, backgroundColor: '#dc3545' }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: { x: { stacked: true, ticks: { precision: 0 } }, y: { stacked: true } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    function topN(list, n) {
        list = list || [];
        if (list.length <= n) return list;
        var sorted = list.slice().sort(function (a, b) { return b.total - a.total; });
        var top = sorted.slice(0, n - 1);
        var rest = sorted.slice(n - 1).reduce(function (acc, v) { return acc + v.total; }, 0);
        top.push({ version: 'Otras', total: rest });
        return top;
    }

    function updateVersionChart(canvasId, list) {
        var items = topN(list, 8);
        var labels = items.map(function (v) { return v.version; });
        var values = items.map(function (v) { return v.total; });
        var colors = ['#007bff', '#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6c757d', '#6610f2', '#fd7e14'];
        updateDoughnut(canvasId, labels, values, colors.slice(0, values.length));
    }

    function statusData(byStatus, order) {
        var labels = [], values = [], colors = [];
        (order || []).forEach(function (k) {
            if (byStatus[k]) {
                labels.push(statusMeta(k)[1]);
                values.push(byStatus[k]);
                colors.push(COLOR_BY_BADGE[statusMeta(k)[0]]);
            }
        });
        return { labels: labels, values: values, colors: colors };
    }

    function progressBar(pct) {
        var color = pct >= 100 ? 'bg-success' : (pct >= 60 ? 'bg-primary' : (pct >= 30 ? 'bg-info' : 'bg-warning'));
        return '<div class="progress progress-xs">' +
            '<div class="progress-bar ' + color + '" style="width:' + pct + '%"></div></div>';
    }

    function recentRow(d) {
        var pct = d.percent || 0;
        return '<tr>' +
            '<td><a href="/admin/distributions/' + d.id + '">#' + d.id + '</a> ' +
            '<span class="text-truncate d-inline-block align-middle" style="max-width:130px" title="' + esc(d.name) + '">' + esc(d.name) + '</span></td>' +
            '<td>' + statusBadge(d.status) + '</td>' +
            '<td class="text-center">' + fmtNum(d.total_targets) + '</td>' +
            '<td>' + progressBar(pct) +
            '<small class="text-muted">' + fmtNum(d.completed_targets) + '/' + fmtNum(d.total_targets) + ' · ' + pct + '%</small></td>' +
            '</tr>';
    }

    function renderDistributions(d) {
        d = d || {};
        var bs = d.by_status || {};
        setNum('kpi-distributions-total', d.total);
        setNum('kpi-distributions-in_progress', bs.in_progress);
        setNum('kpi-distributions-completed', bs.completed);
        setNum('kpi-distributions-failed', bs.failed);

        var rows = (d.recent || []).map(recentRow).join('');
        document.getElementById('tbl-distributions').querySelector('tbody').innerHTML =
            rows || '<tr><td colspan="4" class="text-center text-muted">Sin datos</td></tr>';
    }

    function renderReceptions(r) {
        r = r || {};
        var bs = r.by_status || {};
        setNum('kpi-receptions-total', r.total);
        setNum('kpi-receptions-in_progress', bs.in_progress);
        setNum('kpi-receptions-completed', bs.completed);
        setNum('kpi-receptions-failed', bs.failed);

        var rows = (r.recent || []).map(recentRow).join('');
        document.getElementById('tbl-receptions').querySelector('tbody').innerHTML =
            rows || '<tr><td colspan="4" class="text-center text-muted">Sin datos</td></tr>';
    }

    function renderAuth(auth) {
        auth = auth || {};
        setNum('kpi-auth-pending', auth.pending);
        setNum('kpi-auth-used', auth.used);
        setNum('kpi-auth-expired', auth.expired);
        setNum('kpi-auth-total', auth.total);

        var now = Date.now();
        var rows = (auth.recent || []).map(function (t) {
            var st;
            if (t.used_at) st = statusBadge('completed');
            else if (t.expires_at && new Date(t.expires_at).getTime() <= now) st = statusBadge('failed');
            else st = statusBadge('pending');
            return '<tr>' +
                '<td class="text-truncate" style="max-width:160px" title="' + esc(t.file_name) + '">' + esc(t.file_name || '—') + '</td>' +
                '<td>' + esc(t.email || '—') + '</td>' +
                '<td>' + st + '</td>' +
                '<td class="text-nowrap">' + fmtDateTime(t.created_at) + '</td></tr>';
        }).join('');
        document.getElementById('tbl-tokens').querySelector('tbody').innerHTML =
            rows || '<tr><td colspan="4" class="text-center text-muted">Sin datos</td></tr>';
    }

    function renderSystem(sys) {
        sys = sys || {};
        setNum('kpi-sys-cmds-pending', sys.commands_pending);
        setNum('kpi-sys-failed-24h', sys.commands_failed_24h);
        setNum('kpi-sys-jobs', sys.jobs_queued);
        setNum('kpi-sys-failed-jobs', sys.failed_jobs);
        setNum('kpi-sys-syncs', (sys.report_syncs || []).length);

        var auditRows = (sys.audit || []).map(function (a) {
            return '<tr>' +
                '<td>' + esc(a.user || '') + '</td>' +
                '<td class="text-truncate" style="max-width:150px" title="' + esc(a.action) + '">' + esc(a.action || '') + '</td>' +
                '<td class="text-nowrap">' + fmtDateTime(a.created_at) + '</td></tr>';
        }).join('');
        document.getElementById('tbl-audit').querySelector('tbody').innerHTML =
            auditRows || '<tr><td colspan="3" class="text-center text-muted">Sin actividad</td></tr>';

        var syncRows = (sys.report_syncs || []).map(function (s) {
            return '<tr>' +
                '<td>' + esc(s.name) + '</td>' +
                '<td class="text-center">' + fmtNum(s.rows) + '</td>' +
                '<td class="text-nowrap">' + fmtDateTime(s.updated_at) + '</td></tr>';
        }).join('');
        document.getElementById('tbl-syncs').querySelector('tbody').innerHTML =
            syncRows || '<tr><td colspan="3" class="text-center text-muted">Sin sincronizaciones</td></tr>';
    }

    function renderStats(data) {
        lastData = data;
        var c = data.computers || {};
        var d = data.distributions || {};
        var r = data.receptions || {};
        var ag = data.agent || {};
        var auth = data.authorizations || {};
        var mon = data.monitoring || {};
        var sys = data.system || {};

        setNum('kpi-computers-online', c.online);
        setNum('kpi-computers-offline', c.offline);
        setNum('kpi-computers-total', c.total);
        document.getElementById('kpi-computers-pct').textContent = (c.online_percentage || 0) + '%';

        renderCharts(data);

        setNum('kpi-agent-with-agent', ag.computers_with_agent);
        setNum('kpi-agent-outdated', ag.outdated);
        setNum('kpi-agent-active', ag.on_active_version);
        document.getElementById('agent-active-version').textContent = ag.active_version || '—';

        renderDistributions(d);
        renderReceptions(r);
        renderAuth(auth);
        renderSystem(sys);

        setNum('kpi-mon-files', mon.monitored_files);
        setNum('kpi-mon-lists', mon.file_lists);
        setNum('kpi-mon-hashes', mon.rbf_hashes);

        var label = document.getElementById('fleet-window-label');
        if (label && data.online_window_minutes) {
            var w = data.online_window_minutes;
            label.textContent = 'Ventana: ' + (w <= 60 ? w + ' min' : Math.round(w / 60) + ' h');
        }

        var lu = document.getElementById('last-updated');
        if (lu) lu.textContent = 'Actualizado: ' + fmtDateTime(data.generated_at);
    }

    function currentParams() {
        var plaza = document.getElementById('filter-plaza').value;
        var windowEl = document.getElementById('filter-window');
        var w = windowEl ? windowEl.value : 5;
        return 'plaza=' + encodeURIComponent(plaza || '') + '&window=' + encodeURIComponent(w);
    }

    function updateUrl() {
        var q = currentParams();
        if (history.replaceState) history.replaceState(null, '', window.location.pathname + '?' + q);
    }

    var refreshTimer = null;
    var lastLoadAt = 0;
    var trailingReload = null;

    function debounceRefresh() {
        if (refreshTimer) clearTimeout(refreshTimer);
        refreshTimer = setTimeout(loadStats, 800);
    }

    function loadStats() {
        if (trailingReload) { clearTimeout(trailingReload); trailingReload = null; }
        var elapsed = Date.now() - lastLoadAt;
        if (elapsed < 3000) {
            trailingReload = setTimeout(loadStats, 3000 - elapsed);
            return;
        }
        lastLoadAt = Date.now();

        var btn = document.getElementById('btn-refresh');
        var icon = btn ? btn.querySelector('.fa-sync') : null;
        if (icon) icon.classList.add('fa-spin');

        fetch('{{ route('home.stats') }}?' + currentParams(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (resp) { if (!resp.ok) throw new Error(resp.status); return resp.json(); })
            .then(renderStats)
            .catch(function () {})
            .then(function () {
                if (icon) icon.classList.remove('fa-spin');
            });
    }

    function setWsStatus(ok, text) {
        var el = document.getElementById('ws-status');
        if (!el) return;
        if (ok) {
            el.className = 'badge badge-success mr-2';
            el.innerHTML = '<i class="fas fa-wifi"></i> WS En línea';
        } else if (text) {
            el.className = 'badge badge-secondary mr-2';
            el.innerHTML = text;
        } else {
            el.className = 'badge badge-warning mr-2';
            el.innerHTML = '<i class="fas fa-wifi"></i> WS Offline · Polling';
        }
    }

    var wsSocket = null;
    var lastWsEvent = 0;

    function connectSocket() {
        if (!window.io || wsSocket) return;
        var host = (window.Laravel && window.Laravel.broadcastingHost) || window.location.hostname;
        var port = (window.Laravel && window.Laravel.broadcastingPort) || 6001;

        wsSocket = window.io(host + ':' + port, { transports: ['websocket', 'polling'] });

        wsSocket.on('connect', function () {
            setWsStatus(true);
            wsSocket.emit('subscribe', { channel: 'dashboard' });
            wsSocket.emit('subscribe', { channel: 'distributions' });
        });
        wsSocket.on('disconnect', function () { setWsStatus(false); });
        wsSocket.on('connect_error', function () { setWsStatus(false); });
        wsSocket.on('stats.updated', function () { lastWsEvent = Date.now(); setWsStatus(true); debounceRefresh(); });
        wsSocket.on('distribution.progress', function () { lastWsEvent = Date.now(); debounceRefresh(); });
    }

    (function initWhenReady(attempts) {
        if (window.io) {
            connectSocket();
        } else if (attempts < 100) {
            setTimeout(function () {
                initWhenReady(attempts + 1);
            }, 200);
        } else {
            setWsStatus(false, 'WS no disponible');
        }
    })(0);

    function init() {
        document.getElementById('btn-refresh').addEventListener('click', loadStats);
        document.getElementById('filter-plaza').addEventListener('change', function () {
            updateUrl();
            loadStats();
        });
        document.getElementById('filter-window').addEventListener('change', function () {
            updateUrl();
            loadStats();
        });

        var chartScript = document.getElementById('chartjs-cdn');
        if (chartScript) {
            chartScript.addEventListener('load', function () {
                if (lastData) renderCharts(lastData);
            });
        }

        setInterval(function () {
            if (Date.now() - lastWsEvent > 45000) loadStats();
        }, 30000);

        loadStats();
    }

    init();
})();
</script>
@stop
