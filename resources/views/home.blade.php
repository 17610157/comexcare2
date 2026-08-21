@extends('adminlte::page')

@section('title', 'Comexcare')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('css')

<style>
    /* ===== Layout uniforme del dashboard ===== */
    section.content { padding: 14px 16px !important; }

    .dash-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        grid-auto-rows: 310px;
    }

    .dash-grid > .card {
        margin: 0 !important;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .dash-grid .card-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
    }

    .dash-grid .card-header {
        padding: .55rem .75rem;
        flex: 0 0 auto;
    }

    .fade-in { animation: fadeInUp .45s ease both; }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: none; }
    }

    /* ===== Servidor en tiempo real ===== */
    .live-badge {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: .68rem; font-weight: 700; letter-spacing: .08em;
        padding: 3px 10px; border-radius: 999px;
        background: rgba(52,211,153,.12); color: #34d399;
        border: 1px solid rgba(52,211,153,.35);
    }
    .live-badge.offline { background: rgba(148,163,184,.1); color: #94a3b8; border-color: rgba(148,163,184,.3); }
    .live-badge .dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .live-badge.online .dot { animation: pulseDot 1.6s ease-in-out infinite; }
    @keyframes pulseDot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(52,211,153,.5); }
        50%      { box-shadow: 0 0 0 5px rgba(52,211,153,0); }
    }

    .srv-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; flex: 0 0 auto; }
    .chip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: .68rem; color: #cbd5e1;
        background: rgba(148,163,184,.08);
        border: 1px solid rgba(148,163,184,.15);
        border-radius: 999px; padding: 2px 9px;
    }
    .chip i { font-size: .62rem; color: #60a5fa; }
    .chip b { color: #f1f5f9; font-weight: 600; }

    .srv-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: 8px; flex: 1 1 auto; min-height: 0;
    }
    .srv-metric {
        background: rgba(148,163,184,.05);
        border: 1px solid rgba(148,163,184,.12);
        border-radius: 10px; padding: 7px 10px;
        display: flex; flex-direction: column; min-height: 0;
    }
    .srv-metric-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px; }
    .srv-metric-label { font-size: .66rem; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; font-weight: 700; }
    .srv-val { font-size: 1rem; font-weight: 800; line-height: 1; }
    .srv-val small { font-size: .6em; font-weight: 600; opacity: .7; }
    .srv-net-vals { display: flex; gap: 8px; font-size: .68rem; font-weight: 700; }
    .srv-net-vals .rx { color: #34d399; }
    .srv-net-vals .tx { color: #38bdf8; }
    .srv-canvas { position: relative; flex: 1 1 auto; min-height: 48px; }
    .srv-canvas > canvas { position: absolute; left: 0; top: 0; width: 100% !important; height: 100% !important; }

    /* ===== Flota y agente ===== */
    .kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; flex: 0 0 auto; }
    .mini-stat.kpi b { font-size: 1.25rem; }

    .spark-wrap { position: relative; height: 30px; margin-top: 8px; flex: 0 0 auto; }
    .spark-wrap > canvas { width: 100% !important; height: 100% !important; }

    .dash-sep { border-color: rgba(148,163,184,.15); margin: 10px 0 8px; flex: 0 0 auto; }

    .agent-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; flex: 0 0 auto; }
    .agent-row .mini-stat { flex: 1 1 90px; }
    .agent-badge { font-size: .72rem; color: #94a3b8; white-space: nowrap; }

    /* ===== Mini-stats ===== */
    .stat-strip { display: grid; gap: 8px; }
    .mini-stat {
        display: flex; align-items: center; gap: 9px;
        background: rgba(148,163,184,.05);
        border: 1px solid rgba(148,163,184,.12);
        border-radius: 10px; padding: 7px 10px;
    }
    .mini-stat > i { font-size: .95rem; color: #60a5fa; width: 18px; text-align: center; }
    .mini-stat b { display: block; font-size: .95rem; font-weight: 800; color: #f1f5f9; line-height: 1.1; }
    .mini-stat span { font-size: .64rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; }

    /* ===== Gráficas que llenan la card ===== */
    .chart-fill { position: relative; flex: 1 1 auto; min-height: 110px; }
    .chart-fill > canvas { position: absolute; left: 0; top: 0; width: 100% !important; height: 100% !important; }

    /* ===== Distribuciones ===== */
    .dist-strip { grid-template-columns: repeat(4, 1fr); margin-bottom: 8px; flex: 0 0 auto; }
    .dist-body { display: flex; gap: 10px; flex: 1 1 auto; min-height: 0; }
    .dist-body .chart-fill { flex: 0 0 38%; min-width: 0; }
    .dist-table { flex: 1 1 auto; overflow: auto; min-width: 0; }
    .dist-table table { margin-bottom: 0; }
    .dist-table th {
        position: sticky; top: 0; z-index: 1;
        background: #111a2c; color: #94a3b8;
        font-size: .64rem; text-transform: uppercase; letter-spacing: .05em;
        border-color: rgba(148,163,184,.15);
    }
    .dist-table td { font-size: .74rem; color: #cbd5e1; border-color: rgba(148,163,184,.1); vertical-align: middle; }
    .dist-table .progress { height: 6px; background: rgba(148,163,184,.15); border-radius: 999px; margin: 0; }
    .dist-table .progress-bar { border-radius: 999px; }

    /* ===== Responsive ===== */
    @media (max-width: 1500px) {
        .dash-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 1100px) {
        .dash-grid { grid-template-columns: 1fr; grid-auto-rows: auto; }
        .dash-grid .card-body { overflow: visible; }
        .chart-fill { min-height: 160px; }
        .dist-body { flex-direction: column; }
        .dist-body .chart-fill { flex: none; height: 150px; }
    }
</style>

@stop

@section('content')

<div class="dash-grid fade-in">

    {{-- Servidor en tiempo real --}}
    <div class="card dash-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-server text-success"></i> Servidor en Tiempo Real</h3>
            <div class="card-tools">
                <span id="srv-live" class="live-badge offline"><span class="dot"></span> LIVE</span>
            </div>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="srv-chips">
                <span class="chip"><i class="fas fa-microchip"></i> Núcleos <b id="srv-chip-cores">—</b></span>
                <span class="chip"><i class="fas fa-tachometer-alt"></i> Load <b id="srv-chip-load">—</b></span>
                <span class="chip"><i class="fas fa-memory"></i> RAM <b id="srv-chip-mem">—</b></span>
                <span class="chip"><i class="fas fa-hdd"></i> Disco <b id="srv-chip-disk">—</b></span>
                <span class="chip"><i class="fas fa-exchange-alt"></i> Swap <b id="srv-chip-swap">—</b></span>
                <span class="chip"><i class="fas fa-clock"></i> Uptime <b id="srv-chip-uptime">—</b></span>
            </div>
            <div class="srv-grid">
                <div class="srv-metric">
                    <div class="srv-metric-head">
                        <span class="srv-metric-label">CPU</span>
                        <span class="srv-val" id="srv-cpu-val" style="color:#60a5fa">—<small>%</small></span>
                    </div>
                    <div class="srv-canvas"><canvas id="chart-srv-cpu"></canvas></div>
                </div>
                <div class="srv-metric">
                    <div class="srv-metric-head">
                        <span class="srv-metric-label">Memoria RAM</span>
                        <span class="srv-val" id="srv-mem-val" style="color:#a78bfa">—<small>%</small></span>
                    </div>
                    <div class="srv-canvas"><canvas id="chart-srv-mem"></canvas></div>
                </div>
                <div class="srv-metric">
                    <div class="srv-metric-head">
                        <span class="srv-metric-label">Disco /</span>
                        <span class="srv-val" id="srv-disk-val" style="color:#fbbf24">—<small>%</small></span>
                    </div>
                    <div class="srv-canvas"><canvas id="chart-srv-disk"></canvas></div>
                </div>
                <div class="srv-metric">
                    <div class="srv-metric-head">
                        <span class="srv-metric-label">Red</span>
                        <span class="srv-net-vals">
                            <span class="rx"><i class="fas fa-arrow-down"></i> <span id="srv-rx-val">—</span></span>
                            <span class="tx"><i class="fas fa-arrow-up"></i> <span id="srv-tx-val">—</span></span>
                        </span>
                    </div>
                    <div class="srv-canvas"><canvas id="chart-srv-net"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Flota y agente --}}
    <div class="card dash-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-desktop"></i> Flota y Agente</h3>
            <div class="card-tools"><span class="badge badge-secondary" id="fleet-window-label"></span></div>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="kpi-grid">
                <div class="mini-stat kpi">
                    <i class="fas fa-check-circle" style="color:#34d399"></i>
                    <div><b id="kpi-computers-online">0</b><span>En Línea</span></div>
                </div>
                <div class="mini-stat kpi">
                    <i class="fas fa-times-circle" style="color:#f87171"></i>
                    <div><b id="kpi-computers-offline">0</b><span>Fuera de Línea</span></div>
                </div>
                <div class="mini-stat kpi">
                    <i class="fas fa-desktop" style="color:#38bdf8"></i>
                    <div><b id="kpi-computers-total">0</b><span>Total PCs</span></div>
                </div>
                <div class="mini-stat kpi">
                    <i class="fas fa-signal" style="color:#60a5fa"></i>
                    <div><b id="kpi-computers-pct">0%</b><span>% En Línea</span></div>
                </div>
            </div>
            <div class="spark-wrap"><canvas id="spark-online"></canvas></div>
            <hr class="dash-sep">
            <div class="agent-row">
                <div class="mini-stat">
                    <i class="fas fa-user-check" style="color:#34d399"></i>
                    <div><b id="kpi-agent-active">0</b><span>Agente Activo</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-exclamation-triangle" style="color:#fbbf24"></i>
                    <div><b id="kpi-agent-outdated">0</b><span>Desactualiz.</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-robot" style="color:#38bdf8"></i>
                    <div><b id="kpi-agent-with-agent">0</b><span>Con Agente</span></div>
                </div>
                <div class="agent-badge">Versión <span id="agent-active-version" class="badge badge-success">—</span></div>
            </div>
        </div>
    </div>

    {{-- Computadoras por plaza --}}
    <div class="card dash-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-network-wired"></i> Computadoras por Plaza</h3>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="chart-fill"><canvas id="chart-fleet-plaza"></canvas></div>
        </div>
    </div>

    {{-- Monitoreo y sistema --}}
    <div class="card dash-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt"></i> Monitoreo y Sistema</h3>
        </div>
        <div class="card-body">
            <div class="stat-strip" style="grid-template-columns:repeat(2,1fr);">
                <div class="mini-stat">
                    <i class="fas fa-file-alt"></i>
                    <div><b id="kpi-mon-files">0</b><span>Archivos Monit.</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-list" style="color:#38bdf8"></i>
                    <div><b id="kpi-mon-lists">0</b><span>Listas</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-fingerprint" style="color:#94a3b8"></i>
                    <div><b id="kpi-mon-hashes">0</b><span>Hashes RBF</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-tasks" style="color:#fbbf24"></i>
                    <div><b id="kpi-sys-cmds-pending">0</b><span>Cmd. Pendientes</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-exclamation-triangle" style="color:#f87171"></i>
                    <div><b id="kpi-sys-failed-24h">0</b><span>Fallos 24h</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-clock" style="color:#38bdf8"></i>
                    <div><b id="kpi-sys-jobs">0</b><span>Jobs en Cola</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-file-medical" style="color:#94a3b8"></i>
                    <div><b id="kpi-sys-failed-jobs">0</b><span>Fallos (jobs)</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-database" style="color:#34d399"></i>
                    <div><b id="kpi-sys-syncs">0</b><span>Sinc. Reportes</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Versiones PVSI --}}
    <div class="card dash-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-code"></i> Versiones PVSI Instaladas</h3>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="chart-fill"><canvas id="chart-pvsi"></canvas></div>
        </div>
    </div>

    {{-- Distribuciones --}}
    <div class="card dash-card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-download"></i> Distribuciones</h3>
            <div class="card-tools">
                <a href="/admin/distributions" class="btn btn-tool"><i class="fas fa-external-link-alt"></i></a>
            </div>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="stat-strip dist-strip">
                <div class="mini-stat">
                    <i class="fas fa-cog" style="color:#60a5fa"></i>
                    <div><b id="kpi-distributions-in_progress">0</b><span>En Progreso</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-check" style="color:#34d399"></i>
                    <div><b id="kpi-distributions-completed">0</b><span>Completadas</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-times" style="color:#f87171"></i>
                    <div><b id="kpi-distributions-failed">0</b><span>Fallidas</span></div>
                </div>
                <div class="mini-stat">
                    <i class="fas fa-layer-group" style="color:#94a3b8"></i>
                    <div><b id="kpi-distributions-total">0</b><span>Total</span></div>
                </div>
            </div>
            <div class="dist-body">
                <div class="chart-fill"><canvas id="chart-distributions"></canvas></div>
                <div class="dist-table">
                    <table class="table table-sm table-hover" id="tbl-distributions">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th class="text-center">Obj.</th>
                                <th style="min-width:90px">Progreso</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@stop

@section('js')
<script>
    window.Laravel = window.Laravel || {};
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

    function fmtBytes(bytes) {
        var u = ['B', 'KB', 'MB', 'GB', 'TB'], i = 0, v = Number(bytes || 0);
        while (v >= 1024 && i < u.length - 1) { v /= 1024; i++; }
        return v.toFixed(i >= 3 ? 1 : 0) + ' ' + u[i];
    }

    function fmtUptime(s) {
        s = Math.max(0, Math.floor(Number(s || 0)));
        var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600), m = Math.floor((s % 3600) / 60);
        return (d > 0 ? d + 'd ' : '') + h + 'h ' + m + 'm';
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
        if (!el) return;
        var target = Number(n || 0);
        var from = parseFloat(el.dataset.v);
        if (isNaN(from)) from = 0;
        el.dataset.v = target;
        if (from === target) { el.textContent = fmtNum(target); return; }
        var t0 = performance.now(), dur = 550;
        (function step(t) {
            var p = Math.min(1, (t - t0) / dur);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = fmtNum(Math.round(from + (target - from) * eased));
            if (p < 1 && Number(el.dataset.v) === target) requestAnimationFrame(step);
        })(t0);
    }

    function setPct(id, val, decimals) {
        var el = document.getElementById(id);
        if (!el) return;
        var suffix = '<small>%</small>';
        var target = Number(val || 0);
        var from = parseFloat(el.dataset.v);
        if (isNaN(from)) from = 0;
        el.dataset.v = target;
        var dec = decimals == null ? 1 : decimals;
        var t0 = performance.now(), dur = 450;
        (function step(t) {
            var p = Math.min(1, (t - t0) / dur);
            var eased = 1 - Math.pow(1 - p, 3);
            el.innerHTML = (from + (target - from) * eased).toFixed(dec) + suffix;
            if (p < 1 && Number(el.dataset.v) === target) requestAnimationFrame(step);
        })(t0);
    }

    var charts = {};
    var lastData = null;

    function applyChartDefaults() {
        if (typeof window.Chart === 'undefined') return;
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(148,163,184,.1)';
        Chart.defaults.font.family = "'Segoe UI', system-ui, -apple-system, sans-serif";
    }

    function grad(ctx, hex, alphaTop) {
        var g = ctx.createLinearGradient(0, 0, 0, ctx.canvas.height || 120);
        g.addColorStop(0, hex.replace('ALPHA', alphaTop));
        g.addColorStop(1, hex.replace('ALPHA', '0'));
        return g;
    }

    function renderCharts(data) {
        if (typeof window.Chart !== 'undefined') {
            applyChartDefaults();
            try {
                updateFleetChart(data.computers || {});
                updateVersionChart('chart-pvsi', (data.computers || {}).pvsi_versions);
                var d = data.distributions || {};
                var sd = statusData(d.by_status || {}, ['in_progress', 'completed', 'failed', 'pending', 'stopped']);
                updateDoughnut('chart-distributions', sd.labels, sd.values, sd.colors);
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
            data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 1, borderColor: '#0b1220' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 }, usePointStyle: true } }
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
                    { label: 'En línea', data: online, backgroundColor: 'rgba(16,185,129,.85)', borderRadius: 4 },
                    { label: 'Fuera de línea', data: offline, backgroundColor: 'rgba(239,68,68,.8)', borderRadius: 4 }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, ticks: { precision: 0 }, grid: { color: 'rgba(148,163,184,.08)' } },
                    y: { stacked: true, grid: { display: false } }
                },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }
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
        var colors = ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#64748b', '#8b5cf6', '#ec4899'];
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

    function renderSystem(sys) {
        sys = sys || {};
        setNum('kpi-sys-cmds-pending', sys.commands_pending);
        setNum('kpi-sys-failed-24h', sys.commands_failed_24h);
        setNum('kpi-sys-jobs', sys.jobs_queued);
        setNum('kpi-sys-failed-jobs', sys.failed_jobs);
        setNum('kpi-sys-syncs', (sys.report_syncs || []).length);
    }

    function renderStats(data) {
        lastData = data;
        var c = data.computers || {};
        var d = data.distributions || {};
        var ag = data.agent || {};
        var mon = data.monitoring || {};
        var sys = data.system || {};

        setNum('kpi-computers-online', c.online);
        setNum('kpi-computers-offline', c.offline);
        setNum('kpi-computers-total', c.total);
        setPct('kpi-computers-pct', c.online_percentage || 0, 0);

        renderCharts(data);
        updateOnlineSparkline(c.online_percentage || 0);

        setNum('kpi-agent-with-agent', ag.computers_with_agent);
        setNum('kpi-agent-outdated', ag.outdated);
        setNum('kpi-agent-active', ag.on_active_version);
        document.getElementById('agent-active-version').textContent = ag.active_version || '—';

        renderDistributions(d);
        renderSystem(sys);

        setNum('kpi-mon-files', mon.monitored_files);
        setNum('kpi-mon-lists', mon.file_lists);
        setNum('kpi-mon-hashes', mon.rbf_hashes);

        var label = document.getElementById('fleet-window-label');
        if (label && data.online_window_minutes) {
            var w = data.online_window_minutes;
            label.textContent = 'Ventana: ' + (w <= 60 ? w + ' min' : Math.round(w / 60) + ' h');
        }
    }

    function currentParams() {
        return 'plaza=&window=5';
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

        fetch('{{ route('home.stats') }}?' + currentParams(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (resp) { if (!resp.ok) throw new Error(resp.status); return resp.json(); })
            .then(renderStats)
            .catch(function () {});
    }

    function setWsStatus(ok, text) {
        var el = document.getElementById('ws-status');
        if (!el) return;
        if (ok) {
            el.className = 'live-badge mr-2';
            el.innerHTML = '<span class="dot"></span> EN VIVO';
        } else if (text) {
            el.className = 'live-badge offline mr-2';
            el.innerHTML = '<span class="dot"></span> ' + esc(text);
        } else {
            el.className = 'live-badge offline mr-2';
            el.innerHTML = '<span class="dot"></span> POLLING';
        }
    }

    var wsSocket = null;
    var lastWsEvent = 0;

    function connectSocket() {
        if (!window.io || wsSocket) return;
        wsSocket = window.io(window.location.origin, { transports: ['websocket', 'polling'] });

        wsSocket.on('connect', function () {
            setWsStatus(true);
            wsSocket.emit('subscribe', { channel: 'dashboard' });
            wsSocket.emit('subscribe', { channel: 'distributions' });
        });
        wsSocket.on('disconnect', function () { setWsStatus(false); });
        wsSocket.on('connect_error', function () { setWsStatus(false); });
        wsSocket.on('stats.updated', function () { lastWsEvent = Date.now(); setWsStatus(true); debounceRefresh(); });
        wsSocket.on('distribution.progress', function () { lastWsEvent = Date.now(); debounceRefresh(); });
        wsSocket.on('server.metrics', renderServerMetrics);
    }

    (function initWhenReady(attempts) {
        if (window.io) {
            connectSocket();
        } else if (attempts < 100) {
            setTimeout(function () {
                initWhenReady(attempts + 1);
            }, 200);
        } else {
            setWsStatus(false, 'WS NO DISPONIBLE');
        }
    })(0);

    function init() {
        var chartScript = document.getElementById('chartjs-cdn');
        if (chartScript) {
            chartScript.addEventListener('load', function () {
                applyChartDefaults();
                if (lastData) renderCharts(lastData);
                if (srvHist.cpu.length) buildServerCharts();
            });
        }

        setInterval(function () {
            if (Date.now() - lastWsEvent > 45000) loadStats();
        }, 30000);

        setInterval(function () {
            if (!wsSocket || !wsSocket.connected || Date.now() - lastSrvEvent > 8000) fetchServerStats();
        }, 5000);

        loadStats();
        fetchServerStats();
    }

    /* ===== Métricas del servidor en vivo ===== */
    var MAX_PTS = 60;
    var srvHist = { cpu: [], mem: [], disk: [], rx: [], tx: [] };
    var srvCharts = {};
    var lastSrvEvent = 0;

    function pushHist(k, v) {
        srvHist[k].push(Number(v) || 0);
        if (srvHist[k].length > MAX_PTS) srvHist[k].shift();
    }

    function setSrvLive(ok) {
        var el = document.getElementById('srv-live');
        if (!el) return;
        if (ok) {
            el.className = 'live-badge';
            el.innerHTML = '<span class="dot"></span> LIVE';
        } else {
            el.className = 'live-badge offline';
            el.innerHTML = '<span class="dot"></span> SIN DATOS';
        }
    }

    function srvLineConfig(hex, rgb) {
        return {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    data: [],
                    borderColor: hex,
                    borderWidth: 2,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHitRadius: 8,
                    fill: true,
                    backgroundColor: function (ctx) {
                        var c = ctx.chart.ctx;
                        if (!c) return 'transparent';
                        var g = c.createLinearGradient(0, 0, 0, (ctx.chart.height || 120));
                        g.addColorStop(0, 'rgba(' + rgb + ',.32)');
                        g.addColorStop(1, 'rgba(' + rgb + ',0)');
                        return g;
                    }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    x: { display: false },
                    y: { min: 0, max: 100, ticks: { display: false }, grid: { color: 'rgba(148,163,184,.07)' } }
                },
                plugins: { legend: { display: false }, tooltip: { enabled: true, mode: 'index', intersect: false } }
            }
        };
    }

    function buildServerCharts() {
        if (typeof window.Chart === 'undefined') return;
        applyChartDefaults();

        if (!srvCharts.cpu) {
            var mk = function (id, hex, rgb) {
                var el = document.getElementById(id);
                if (!el) return null;
                var cfg = srvLineConfig(hex, rgb);
                cfg.data.labels = srvHist.cpu.map(function (_, i) { return i; });
                return new Chart(el.getContext('2d'), cfg);
            };
            srvCharts.cpu = mk('chart-srv-cpu', '#60a5fa', '96,165,250');
            srvCharts.mem = mk('chart-srv-mem', '#a78bfa', '167,139,250');
            srvCharts.disk = mk('chart-srv-disk', '#fbbf24', '251,191,36');

            var netEl = document.getElementById('chart-srv-net');
            if (netEl) {
                srvCharts.net = new Chart(netEl.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [
                            { label: 'RX', data: [], borderColor: '#34d399', borderWidth: 2, tension: 0.35, pointRadius: 0, fill: true,
                              backgroundColor: function (ctx) { var c = ctx.chart.ctx; var g = c.createLinearGradient(0, 0, 0, ctx.chart.height || 120); g.addColorStop(0, 'rgba(52,211,153,.25)'); g.addColorStop(1, 'rgba(52,211,153,0)'); return g; } },
                            { label: 'TX', data: [], borderColor: '#22d3ee', borderWidth: 2, tension: 0.35, pointRadius: 0, fill: true,
                              backgroundColor: function (ctx) { var c = ctx.chart.ctx; var g = c.createLinearGradient(0, 0, 0, ctx.chart.height || 120); g.addColorStop(0, 'rgba(34,211,238,.2)'); g.addColorStop(1, 'rgba(34,211,238,0)'); return g; } }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            x: { display: false },
                            y: { beginAtZero: true, ticks: { callback: function (v) { return v + ' KB/s'; }, font: { size: 9 }, color: '#64748b', maxTicksLimit: 4 }, grid: { color: 'rgba(148,163,184,.07)' } }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }
        }

        updateServerCharts();
    }

    function updateServerCharts() {
        var pairs = [['cpu', srvCharts.cpu], ['mem', srvCharts.mem], ['disk', srvCharts.disk]];
        pairs.forEach(function (p) {
            if (!p[1]) return;
            p[1].data.labels = srvHist[p[0]].map(function (_, i) { return i; });
            p[1].data.datasets[0].data = srvHist[p[0]].slice();
            p[1].update('none');
        });
        if (srvCharts.net) {
            srvCharts.net.data.labels = srvHist.rx.map(function (_, i) { return i; });
            srvCharts.net.data.datasets[0].data = srvHist.rx.slice();
            srvCharts.net.data.datasets[1].data = srvHist.tx.slice();
            srvCharts.net.update('none');
        }
    }

    function renderServerMetrics(m) {
        if (!m) return;
        lastSrvEvent = Date.now();
        setSrvLive(true);

        if (m.cpu != null) pushHist('cpu', m.cpu);
        if (m.mem_pct != null) pushHist('mem', m.mem_pct);
        if (m.disk && m.disk.pct != null) pushHist('disk', m.disk.pct);
        pushHist('rx', m.net ? (m.net.rx_kbps || 0) : 0);
        pushHist('tx', m.net ? (m.net.tx_kbps || 0) : 0);

        if (m.cpu != null) setPct('srv-cpu-val', m.cpu, 1);
        if (m.mem_pct != null) setPct('srv-mem-val', m.mem_pct, 1);
        if (m.disk && m.disk.pct != null) setPct('srv-disk-val', m.disk.pct, 1);

        var rxEl = document.getElementById('srv-rx-val');
        var txEl = document.getElementById('srv-tx-val');
        if (rxEl) rxEl.textContent = (m.net ? m.net.rx_kbps || 0 : 0) + ' KB/s';
        if (txEl) txEl.textContent = (m.net ? m.net.tx_kbps || 0 : 0) + ' KB/s';

        var chip;
        chip = document.getElementById('srv-chip-cores');
        if (chip) chip.textContent = m.cores != null ? m.cores : '—';

        chip = document.getElementById('srv-chip-load');
        if (chip) chip.textContent = m.load1 != null ? m.load1 : '—';

        chip = document.getElementById('srv-chip-mem');
        if (chip) chip.textContent = m.mem_used != null ? fmtBytes(m.mem_used) + ' / ' + fmtBytes(m.mem_total) : '—';

        chip = document.getElementById('srv-chip-disk');
        if (chip) chip.textContent = m.disk ? fmtBytes(m.disk.used) + ' / ' + fmtBytes(m.disk.total) : '—';

        chip = document.getElementById('srv-chip-swap');
        if (chip) chip.textContent = m.swap && m.swap.total > 0 ? fmtBytes(m.swap.used) + ' (' + m.swap.pct + '%)' : 'N/A';

        chip = document.getElementById('srv-chip-uptime');
        if (chip) chip.textContent = m.uptime_s != null ? fmtUptime(m.uptime_s) : '—';

        buildServerCharts();
    }

    function fetchServerStats() {
        fetch('{{ route('home.server-stats') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (resp) { if (!resp.ok) throw new Error(resp.status); return resp.json(); })
            .then(renderServerMetrics)
            .catch(function () { setSrvLive(false); });
    }

    /* ===== Sparkline % en línea ===== */
    var sparkChart = null;
    var sparkData = [];

    function updateOnlineSparkline(pct) {
        sparkData.push(Number(pct) || 0);
        if (sparkData.length > 40) sparkData.shift();

        var el = document.getElementById('spark-online');
        if (!el || typeof window.Chart === 'undefined') return;

        if (!sparkChart) {
            sparkChart = new Chart(el.getContext('2d'), {
                type: 'line',
                data: {
                    labels: sparkData.map(function (_, i) { return i; }),
                    datasets: [{
                        data: sparkData.slice(),
                        borderColor: 'rgba(255,255,255,.9)',
                        borderWidth: 2,
                        tension: 0.4,
                        pointRadius: 0,
                        fill: true,
                        backgroundColor: function (ctx) {
                            var c = ctx.chart.ctx;
                            var g = c.createLinearGradient(0, 0, 0, ctx.chart.height || 40);
                            g.addColorStop(0, 'rgba(255,255,255,.35)');
                            g.addColorStop(1, 'rgba(255,255,255,0)');
                            return g;
                        }
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: { x: { display: false }, y: { display: false, min: 0, max: 100 } },
                    plugins: { legend: { display: false }, tooltip: { enabled: false } }
                }
            });
        } else {
            sparkChart.data.labels = sparkData.map(function (_, i) { return i; });
            sparkChart.data.datasets[0].data = sparkData.slice();
            sparkChart.update('none');
        }
    }

    init();
})();
</script>
@stop
