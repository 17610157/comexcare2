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

    /* ===== Mapa de equipos ===== */
    [data-card-id="map"] .card-body { position: relative; overflow: hidden; }
    #map-equipos {
        position: absolute; inset: 0;
        background: transparent;
        border-radius: 10px; overflow: hidden;
    }
    .leaflet-container { background: rgba(11,18,32,0) !important; font-family: inherit; }
    .map-region { transition: fill-opacity .2s ease; cursor: pointer; }
    .map-region:hover { fill-opacity: .95 !important; stroke: #93c5fd !important; }
    .map-legend {
        position: absolute; left: 8px; bottom: 8px; z-index: 500;
        display: flex; flex-direction: column; gap: 3px;
        background: rgba(14,23,41,.85); border: 1px solid rgba(148,163,184,.18);
        border-radius: 8px; padding: 6px 9px; font-size: .62rem; color: #cbd5e1;
        pointer-events: none;
    }
    .map-legend span { display: inline-flex; align-items: center; gap: 5px; }
    .map-legend i.swatch { width: 10px; height: 10px; border-radius: 3px; display: inline-block; }

    /* ===== Cards arrastrables ===== */
    .dash-card .card-header { cursor: grab; user-select: none; touch-action: none; }
    .dash-card .card-header:active { cursor: grabbing; }
    .sortable-ghost {
        opacity: .35;
        border: 1px dashed #60a5fa !important;
        background: rgba(96,165,250,.08) !important;
    }
    .sortable-chosen { box-shadow: 0 16px 40px rgba(0,0,0,.55) !important; }
    .drag-hint { color:#475569; font-size:.65rem; margin-left:auto; margin-right:8px; }

    /* ===== Cards redimensionables ===== */
    .dash-grid { grid-auto-rows: 280px; }
    .dash-card.card-size-wide  { grid-column: span 2; }
    .dash-card.card-size-tall  { grid-row:    span 2; }
    .dash-card.card-size-large { grid-column: span 2; grid-row: span 2; }
    .resize-btn { cursor:pointer; opacity:.4; font-size:.68rem; margin-left:5px; color:#94a3b8; transition:.2s; padding:3px 5px; border-radius:5px; }
    .resize-btn:hover { opacity:1; color:#60a5fa; background:rgba(96,165,250,.12); }

    /* ===== Tabla de plazas ===== */
    .plaza-tbl { width:100%; border-collapse:separate; border-spacing:0 3px; font-size:.78rem; }
    .plaza-tbl th { color:#7c8db5; font-weight:600; font-size:.66rem; text-transform:uppercase; letter-spacing:.05em; padding:3px 8px; text-align:left; }
    .plaza-tbl td { padding:5px 8px; color:#cbd5e1; background:rgba(148,163,184,.04); }
    .plaza-tbl tr:hover td { background:rgba(96,165,250,.06); }
    .plaza-tbl .pct-cell { width:52px; text-align:right; font-weight:700; font-variant-numeric:tabular-nums; }
    .plaza-bar-wrap { width:54px; }
    .plaza-bar { height:5px; border-radius:3px; background:#1e293b; overflow:hidden; }
    .plaza-bar-fill { height:100%; border-radius:3px; transition:width .3s ease; }

    /* ===== Card Reporte de Precios ===== */
    .dbf-big { font-size:1.9rem; font-weight:800; line-height:1; }
    .dbf-sub { font-size:.72rem; color:#7c8db5; margin-top:2px; }
    .dbf-row { display:flex; align-items:center; gap:10px; padding:4px 0; font-size:.8rem; }
    .dbf-row .dbf-dot { width:8px; height:8px; border-radius:50%; flex:0 0 auto; }
    .dbf-row small { margin-left:auto; color:#64748b; }
    .dbf-kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:6px; text-align:center; }
    .dbf-kpi { background:rgba(148,163,184,.04); border-radius:8px; padding:10px 4px; }
    .dbf-table { width:100%; font-size:.76rem; border-collapse:separate; border-spacing:0 2px; }
    .dbf-table td { padding:3px 6px; color:#cbd5e1; background:rgba(148,163,184,.04); }
    .dbf-table tr:hover td { background:rgba(96,165,250,.06); }

    /* ===== Modal detalle equipos por región ===== */
    #regionModal .modal-body { padding: .5rem .75rem; }
    .region-list { max-height: 320px; overflow-y: auto; }
    .region-plaza-label {
        font-size: .66rem; text-transform: uppercase; letter-spacing: .06em;
        color: #60a5fa; font-weight: 700; margin: 8px 4px 4px;
    }
    .region-item {
        display: flex; align-items: center; gap: 8px;
        padding: 4px 8px; border-radius: 7px;
        border: 1px solid rgba(148,163,184,.1);
        background: rgba(148,163,184,.04);
        margin-bottom: 4px; font-size: .76rem; color: #e2e8f0;
    }
    .region-item .st-dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
    .region-item.online .st-dot { background: #34d399; box-shadow: 0 0 6px rgba(52,211,153,.7); }
    .region-item.offline .st-dot { background: #f87171; box-shadow: 0 0 6px rgba(248,113,113,.6); }
    .region-item small { color: #64748b; margin-left: auto; }

    /* ===== Popup del mapa ===== */
    .leaflet-control-zoom {
        border: none !important;
        box-shadow: 0 6px 18px rgba(0,0,0,.45) !important;
        border-radius: 8px !important; overflow: hidden;
    }
    .leaflet-bar a {
        background: #0e1729 !important;
        color: #cbd5e1 !important;
        border-bottom: 1px solid rgba(148,163,184,.15) !important;
    }
    .leaflet-bar a:hover { background: #16213a !important; color: #fff !important; }
    .leaflet-popup-content-wrapper, .leaflet-popup-tip {
        background: #0e1729 !important; color: #fff !important;
        border-radius: 10px !important;
        box-shadow: 0 12px 34px rgba(0,0,0,.55) !important;
    }
    .leaflet-popup-content-wrapper { border: 1px solid rgba(148,163,184,.25) !important; }
    .leaflet-popup-content { margin: 10px 14px !important; font-size: .78rem; min-width: 170px; }
    .leaflet-container a.leaflet-popup-close-button { color: #94a3b8 !important; }
    .map-pop-title { font-weight: 700; font-size: .84rem; color: #fff; margin-bottom: 2px; }
    .map-pop-sub { color: #94a3b8; font-size: .68rem; margin-bottom: 6px; }
    .map-pop-row { display: flex; justify-content: space-between; gap: 14px; }
    .map-pop-row b { font-variant-numeric: tabular-nums; }
    .btn-ver-equipos {
        margin-top: 8px; width: 100%;
        background: rgba(59,130,246,.15); color: #93c5fd;
        border: 1px solid rgba(59,130,246,.45); border-radius: 7px;
        font-size: .72rem; font-weight: 600; padding: 4px 0; cursor: pointer;
        transition: background .15s ease;
    }
    .btn-ver-equipos:hover { background: rgba(59,130,246,.32); color: #fff; }

    /* ===== Actividad de agentes ===== */
    .act-rate {
        font-size:.66rem; font-weight:800; color:#34d399;
        background:rgba(52,211,153,.1); border:1px solid rgba(52,211,153,.3);
        padding:2px 8px; border-radius:999px; margin-right:6px;
        font-variant-numeric:tabular-nums;
    }
    .act-filters { display:flex; gap:5px; flex-wrap:wrap; margin-bottom:6px; flex:0 0 auto; }
    .act-chip {
        font-size:.62rem; font-weight:700; color:#94a3b8; cursor:pointer;
        background:rgba(148,163,184,.07); border:1px solid rgba(148,163,184,.18);
        border-radius:999px; padding:2px 9px; transition:all .15s ease;
    }
    .act-chip:hover { color:#e2e8f0; }
    .act-chip.active { color:#fff; background:rgba(59,130,246,.25); border-color:#60a5fa; }
    .activity-feed { flex:1 1 auto; min-height:0; overflow-y:auto; display:flex; flex-direction:column; gap:4px; }
    .feed-item {
        display:flex; gap:8px; align-items:flex-start;
        background:rgba(148,163,184,.04); border:1px solid rgba(148,163,184,.09);
        border-radius:8px; padding:5px 8px; flex:0 0 auto;
        cursor:pointer; transition:border-color .15s ease, background .15s ease;
    }
    .feed-item:hover { border-color:rgba(96,165,250,.45); background:rgba(59,130,246,.08); }
    .actm-message {
        margin:8px 0 0; padding:8px 10px;
        background:#0f172a; border:1px solid rgba(148,163,184,.15);
        border-radius:8px; color:#cbd5e1;
        font-size:.68rem; line-height:1.5; font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
        white-space:pre-wrap; word-break:break-word;
        max-height:260px; overflow-y:auto;
    }
    .feed-item.fresh { animation:feedIn .5s ease both; }
    @keyframes feedIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
    .feed-item > i { font-size:.72rem; width:16px; text-align:center; margin-top:2px; }
    .feed-item .fi-body { min-width:0; flex:1; }
    .feed-item .fi-head b { font-size:.7rem; color:#f1f5f9; }
    .feed-item .fi-head small { font-size:.58rem; color:#60a5fa; margin-left:5px; }
    .feed-item p { margin:0; font-size:.64rem; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .feed-item time { font-size:.58rem; color:#64748b; white-space:nowrap; margin-top:2px; }
    .kind-vales > i { color:#fbbf24; } .kind-rbf > i { color:#a78bfa; }
    .kind-descargas > i { color:#38bdf8; } .kind-comandos > i { color:#34d399; }
    .kind-sistema > i { color:#94a3b8; }

    /* ===== Matriz de salud ===== */
    .hm-counters { display:flex; gap:5px; align-items:center; }
    .hm-counters .badge { font-size:.58rem; border-radius:999px; padding:2px 7px; }
    .hm-matrix { flex:1 1 auto; min-height:0; overflow-y:auto; padding-right:2px; }
    .hm-group { margin-bottom:7px; }
    .hm-group-label { font-size:.6rem; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; font-weight:700; margin-bottom:3px; }
    .hm-group-label small { color:#475569; text-transform:none; letter-spacing:0; }
    .hm-squares { display:flex; flex-wrap:wrap; gap:3px; }
    .hm-square {
        width:15px; height:15px; border-radius:4px; cursor:pointer;
        transition:transform .12s ease, box-shadow .12s ease;
    }
    .hm-square:hover { transform:scale(1.45); z-index:5; box-shadow:0 0 0 2px #93c5fd; position:relative; }
    .s-ok   { background:#059669; box-shadow:inset 0 0 3px rgba(255,255,255,.25); }
    .s-warn { background:#b45309; }
    .s-crit { background:#dc2626; }
    .s-off  { background:#334155; }

    /* ===== Perfil PC (modal) ===== */
    .pcp-score-bar { height:7px; border-radius:99px; background:rgba(148,163,184,.15); overflow:hidden; margin-top:4px; }
    .pcp-score-fill { height:100%; border-radius:99px; transition:width .4s ease; }
    .pcp-row { display:flex; justify-content:space-between; gap:10px; padding:4px 2px; font-size:.74rem; color:#cbd5e1; border-bottom:1px dashed rgba(148,163,184,.1); }

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
    <div class="card dash-card" data-card-id="server">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-server text-success"></i> Servidor en Tiempo Real</h3>
            <div class="card-tools">
                <span id="srv-live" class="live-badge offline"><span class="dot"></span> LIVE</span>
                <i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i>
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
    <div class="card dash-card" data-card-id="fleet">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-desktop"></i> Flota y Agente</h3>
            <div class="card-tools"><span class="badge badge-secondary" id="fleet-window-label"></span><i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i></div>
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

    {{-- (plaza card eliminada) --}}

    {{-- Monitoreo y sistema --}}
    <div class="card dash-card" data-card-id="monitoring">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt"></i> Monitoreo y Sistema</h3>
            <div class="card-tools"><i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i></div>
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
    <div class="card dash-card" data-card-id="pvsi">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-code"></i> Versiones PVSI Instaladas</h3>
            <div class="card-tools"><i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i></div>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="chart-fill"><canvas id="chart-pvsi"></canvas></div>
        </div>
    </div>

    {{-- Distribuciones --}}
    <div class="card dash-card" data-card-id="distributions">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-download"></i> Distribuciones</h3>
            <div class="card-tools">
                <a href="/admin/distributions" class="btn btn-tool"><i class="fas fa-external-link-alt"></i></a>
                <i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i>
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

    {{-- Mapa de equipos --}}
    <div class="card dash-card" data-card-id="map">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-map-marked-alt"></i> Mapa de Equipos</h3>
            <div class="card-tools"><span class="drag-hint" title="Arrastra para reordenar"><i class="fas fa-arrows-alt"></i></span><i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i></div>
        </div>
        <div class="card-body d-flex flex-column">
            <div id="map-equipos"><div class="map-legend">
                <span><i class="swatch" style="background:#059669"></i> &gt; 80% en línea</span>
                <span><i class="swatch" style="background:#b45309"></i> 50–80% en línea</span>
                <span><i class="swatch" style="background:#dc2626"></i> &lt; 50% en línea</span>
                <span><i class="swatch" style="background:#334155"></i> Sin equipos</span>
            </div></div>
        </div>
    </div>

    {{-- Actividad de agentes en vivo --}}
    <div class="card dash-card" data-card-id="activity">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-wave-square text-info"></i> Actividad de Agentes</h3>
            <div class="card-tools">
                <i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i>
                <span class="act-rate" id="act-rate">— /min</span>
                <button type="button" class="btn btn-tool" id="act-pause" title="Pausar feed"><i class="fas fa-pause"></i></button>
                <span class="drag-hint"><i class="fas fa-arrows-alt"></i></span>
            </div>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="act-filters" id="act-filters">
                <span class="act-chip active" data-kind="todo">Todo</span>
                <span class="act-chip" data-kind="vales">Vales</span>
                <span class="act-chip" data-kind="rbf">RBF</span>
                <span class="act-chip" data-kind="descargas">Descargas</span>
                <span class="act-chip" data-kind="comandos">Comandos</span>
                <span class="act-chip" data-kind="sistema">Sistema</span>
            </div>
            <div class="activity-feed" id="activity-feed"></div>
        </div>
    </div>

    {{-- Matriz de salud del parque --}}
    <div class="card dash-card" data-card-id="health">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-th text-success"></i> Matriz de Salud</h3>
            <div class="card-tools hm-counters">
                <span class="badge badge-success" id="hm-c-ok">0</span>
                <span class="badge badge-warning" id="hm-c-warn">0</span>
                <span class="badge badge-danger" id="hm-c-crit">0</span>
                <span class="badge badge-secondary" id="hm-c-off">0</span>
                <span class="drag-hint"><i class="fas fa-arrows-alt"></i></span>
                <i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i>
            </div>
        </div>
        <div class="card-body d-flex flex-column">
            <div class="act-filters" style="margin-bottom:5px;">
                <span class="act-chip active" data-hplaza="">Todas las plazas</span>
            </div>
            <div class="hm-matrix" id="health-matrix"></div>
        </div>
    </div>

    {{-- Reporte de Precios (DBF) --}}
    <div class="card dash-card" data-card-id="dbf">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-tag text-warning"></i> Reporte de Precios</h3>
            <div class="card-tools">
                <a href="/admin/reportes/dbf-especificos" target="_blank" class="btn btn-tool" title="Ver reporte completo"><i class="fas fa-external-link-alt"></i></a>
                <i class="fas fa-expand-arrows-alt resize-btn" title="Cambiar tamaño"></i>
            </div>
        </div>
        <div class="card-body d-flex flex-column" id="dbf-overview">
            <div class="text-muted text-center py-3" style="font-size:.82rem"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
        </div>
    </div>

</div>

{{-- Modal detalle de equipos por región --}} región --}}
<div class="modal fade" id="regionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-sm-plus">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-map-marker-alt"></i> <span id="region-modal-title">Equipos</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div id="region-list" class="region-list"><div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal perfil de PC --}}
<div class="modal fade" id="pcProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-desktop"></i> <span id="pcp-title">Equipo</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div id="pcp-score-wrap">
                    <div style="display:flex;justify-content:space-between;font-size:.7rem;color:#94a3b8;">
                        <span>Salud</span><b id="pcp-score-num">0</b>
                    </div>
                    <div class="pcp-score-bar"><div class="pcp-score-fill" id="pcp-score-fill" style="width:0%"></div></div>
                </div>
                <div id="pcp-rows" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

{{-- Modal detalle de evento de actividad --}}
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-wave-square"></i> <span id="actm-title">Evento</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body">
                <div id="actm-rows"></div>
                <pre class="actm-message" id="actm-message"></pre>
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
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script src="{{ asset('vendor/sortablejs/sortable.min.js') }}"></script>
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
                /* updatePlazasTable eliminada */
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

    function updateDbfOverview(dbf) {
        var el = document.getElementById('dbf-overview');
        if (!el || !dbf) return;
        var p = dbf.cumplimiento_pct;
        var pctStr = p != null ? p.toFixed(1) + '%' : '—';
        var pctColor = p != null ? (p >= 95 ? '#34d399' : (p >= 80 ? '#fbbf24' : '#f87171')) : '#64748b';
        var html = '<div class="dbf-kpi-grid">'
            + '<div class="dbf-kpi"><div class="dbf-big" style="color:' + pctColor + '">' + pctStr + '</div><div class="dbf-sub">Cumplimiento</div></div>'
            + '<div class="dbf-kpi"><div class="dbf-big" style="color:#60a5fa">' + (dbf.total || 0) + '</div><div class="dbf-sub">Total Archivos</div></div>'
            + '<div class="dbf-kpi"><div class="dbf-big" style="color:#34d399">' + (dbf.actualizado || 0) + '</div><div class="dbf-sub">Actualizados</div></div>'
            + '<div class="dbf-kpi"><div class="dbf-big" style="color:#fbbf24">' + (dbf.cambio_manual || 0) + '</div><div class="dbf-sub">Cambio Manual</div></div>'
            + '<div class="dbf-kpi"><div class="dbf-big" style="color:#f87171">' + (dbf.desactualizado || 0) + '</div><div class="dbf-sub">Desactualizados</div></div>'
            + '</div>';
        el.innerHTML = html;
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
        wsSocket.on('stats.updated', function () { lastWsEvent = Date.now(); setWsStatus(true); debounceRefresh(); window.dispatchEvent(new CustomEvent('dash:data-refresh')); });
        wsSocket.on('distribution.progress', function () { lastWsEvent = Date.now(); debounceRefresh(); window.dispatchEvent(new CustomEvent('dash:data-refresh')); });
        wsSocket.on('server.metrics', function () { renderServerMetrics.apply(null, arguments); window.dispatchEvent(new CustomEvent('dash:srv-metrics')); });
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

    /* ===== Polling del reporte de precios (DBF) ===== */
    function pollDbf() {
        fetch('/home/dbf-overview', { credentials:'same-origin' })
            .then(function (r) { if (r.ok) return r.json(); return null; })
            .then(function (j) { if (j) updateDbfOverview(j); })
            .catch(function () {});
    }
    pollDbf();
    setInterval(pollDbf, 60000);

    init();
})();
</script>
<script>
(function () {
    'use strict';

    /* ===== Orden persistente de las cards ===== */
    var ORDER_KEY = 'dash-card-order';
    var grid = document.querySelector('.dash-grid');

    if (grid && window.Sortable) {
        try {
            var saved = JSON.parse(localStorage.getItem(ORDER_KEY) || '[]');
            saved.forEach(function (id) {
                var el = grid.querySelector('[data-card-id="' + id + '"]');
                if (el) grid.appendChild(el);
            });
        } catch (e) {}

        Sortable.create(grid, {
            animation: 180,
            handle: '.card-header',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function () {
                localStorage.setItem(ORDER_KEY, JSON.stringify(
                    Array.prototype.map.call(grid.children, function (c) { return c.getAttribute('data-card-id'); })
                ));
            }
        });
    }

    /* ===== Cards redimensionables: restaurar tamaño + toggle ===== */
    var SIZE_KEY = 'dash-card-sizes';
    var SIZES = ['', 'card-size-wide', 'card-size-tall', 'card-size-large'];

    try {
        var sizes = JSON.parse(localStorage.getItem(SIZE_KEY) || '{}');
        document.querySelectorAll('.dash-card[data-card-id]').forEach(function (card) {
            var id = card.getAttribute('data-card-id');
            var cls = sizes[id];
            if (cls && SIZES.indexOf(cls) !== -1) card.classList.add(cls);
        });
    } catch (e) {}

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.resize-btn');
        if (!btn) return;
        var card = btn.closest('.dash-card');
        if (!card) return;
        var cur = SIZES.filter(function (c) { return card.classList.contains(c); }).pop() || '';
        var idx = (SIZES.indexOf(cur) + 1) % SIZES.length;
        card.classList.remove.apply(card.classList, SIZES.filter(Boolean));
        if (SIZES[idx]) card.classList.add(SIZES[idx]);
        var id = card.getAttribute('data-card-id');
        if (id) {
            var saved = {};
            try { saved = JSON.parse(localStorage.getItem(SIZE_KEY) || '{}'); } catch (e) {}
            saved[id] = SIZES[idx];
            localStorage.setItem(SIZE_KEY, JSON.stringify(saved));
        }
    });

    /* ===== Mapa de equipos ===== */
    var MAP_COLORS = [
        { min: 80, color: '#059669' },
        { min: 50, color: '#b45309' },
        { min: 0,  color: '#dc2626' }
    ];

    var map = null;
    var regionLayers = {};
    var regionStats = {};
    var cityMarkers = [];

    var CITY_MARKERS = [
        { id: 'guatemala', name: 'Ciudad de Guatemala', country: 'Guatemala', latlng: [14.6349, -90.5069] }
    ];

    function colorFor(pctOnline) {
        for (var i = 0; i < MAP_COLORS.length; i++) {
            if (pctOnline >= MAP_COLORS[i].min) return MAP_COLORS[i].color;
        }
        return '#334155';
    }

    function initMap() {
        if (map || !window.L || !document.getElementById('map-equipos')) return;
        map = L.map('map-equipos', {
            zoomControl: true,
            attributionControl: false,
            dragging: true,
            scrollWheelZoom: true,
            doubleClickZoom: true,
            boxZoom: false,
            keyboard: false,
            minZoom: 2,
            maxZoom: 12
        });

        Promise.all([
            fetch('{{ asset("vendor/geojson/mx.json") }}').then(function (r) { return r.json(); }),
            fetch('{{ asset("vendor/geojson/gtm.json") }}').then(function (r) { return r.json(); }),
            fetch('{{ asset("vendor/geojson/nic.json") }}').then(function (r) { return r.json(); })
        ]).then(function (all) {
            var features = [];
            all.forEach(function (gj) { features = features.concat(gj.features); });
            var layer = L.geoJSON({ type: 'FeatureCollection', features: features }, {
                style: baseStyle,
                onEachFeature: attachRegion
            }).addTo(map);
            map.fitBounds(layer.getBounds(), { padding: [4, 4] });

            CITY_MARKERS.forEach(function (cm) {
                var m = L.circleMarker(cm.latlng, {
                    radius: 6, color: '#f8fafc', weight: 1.5,
                    fillColor: '#38bdf8', fillOpacity: .95
                }).addTo(map);
                m._cityDef = cm;
                m.bindTooltip(cm.name, { direction: 'top', offset: [0, -4] });
                m.on('click', function () {
                    var st = m._regionStats;
                    if (!st) return;
                    m.bindPopup(popupHtml(st), { closeButton: true }).openPopup();
                });
                cityMarkers.push(m);
            });

            fetchMapStats();
        });
    }

    function baseStyle(feature) {
        return {
            color: 'rgba(148,163,184,.35)',
            weight: .8,
            fillColor: '#334155',
            fillOpacity: .55
        };
    }

    function attachRegion(feature, layer) {
        var name = feature.properties && feature.properties.name;
        regionLayers[String(name).toLowerCase()] = layer;
        applyStatToLayer(layer);
        layer.on('click', function () {
            var st = layer._regionStats;
            if (!st) return;
            layer.bindPopup(popupHtml(st), { closeButton: true }).openPopup();
        });
    }

    function applyStatToLayer(layer) {
        var st = layer._regionStats;
        var pct = st && st.total > 0 ? Math.round((st.online / st.total) * 100) : -1;
        layer.setStyle({
            fillColor: pct < 0 ? '#334155' : colorFor(pct),
            fillOpacity: pct < 0 ? .3 : .7
        });
    }

    function popupHtml(st) {
        var pct = st.total > 0 ? Math.round((st.online / st.total) * 100) : 0;
        return '<div class="map-pop-title">' + esc(st.name) + '</div>' +
               '<div class="map-pop-sub">' + esc(st.country) + '</div>' +
               '<div class="map-pop-row"><span>🟢 Prendidos</span><b style="color:#34d399">' + st.online + '</b></div>' +
               '<div class="map-pop-row"><span>🔴 Apagados</span><b style="color:#f87171">' + st.offline + '</b></div>' +
               '<div class="map-pop-row"><span>Total</span><b>' + st.total + '</b></div>' +
               '<div class="map-pop-row"><span>% en línea</span><b style="color:#60a5fa">' + pct + '%</b></div>' +
               '<button type="button" class="btn-ver-equipos" data-region="' + esc(st.id) + '" data-region-name="' + esc(st.name) + '">Ver equipos</button>';
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    var statsTimer = null;

    function fetchMapStats() {
        fetch('{{ route('home.map-stats') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                var byGeo = {};
                (data.regions || []).forEach(function (rg) {
                    regionStats[rg.id] = rg;
                    (rg.geo_names || []).forEach(function (gn) { byGeo[String(gn).toLowerCase()] = rg; });
                });
                Object.keys(regionLayers).forEach(function (key) {
                    var rg = byGeo[key];
                    if (!rg) return;
                    var layer = regionLayers[key];
                    layer._regionStats = rg;
                    applyStatToLayer(layer);
                });
                cityMarkers.forEach(function (m) {
                    var st = regionStats[m._cityDef.id];
                    if (!st) return;
                    m._regionStats = st;
                    var pct = st.total > 0 ? Math.round((st.online / st.total) * 100) : 0;
                    m.setStyle({ fillColor: colorFor(pct) });
                });
                renderUnassigned(data.unassigned);
            })
            .catch(function () {});
    }

    function renderUnassigned(u) {
        var card = document.querySelector('[data-card-id="map"] .card-title');
        if (!card || !u) return;
        var badge = document.getElementById('unassigned-badge');
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'unassigned-badge';
            badge.className = 'badge badge-secondary ml-2';
            badge.style.cssText = 'font-size:.6rem;cursor:pointer;vertical-align:middle;';
            badge.title = 'Equipos sin plaza asignada — clic para ver detalle';
            badge.addEventListener('click', function () { openRegionModal('sin_ubicacion', 'Sin ubicación'); });
            card.parentNode.appendChild(badge);
        }
        badge.textContent = 'Sin ubicación: ' + u.total;
    }

    function openRegionModal(regionId, regionName) {
        document.getElementById('region-modal-title').textContent = regionName;
        var list = document.getElementById('region-list');
        list.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>';
        jQuery('#regionModal').modal('show');

        fetch('{{ route('home.map-computers') }}?region=' + encodeURIComponent(regionId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                var html = '';
                var lastPlaza = null;
                (data.computers || []).forEach(function (c) {
                    var plaza = c.plaza || 'Sin plaza';
                    if (plaza !== lastPlaza) {
                        html += '<div class="region-plaza-label">' + esc(plaza) + '</div>';
                        lastPlaza = plaza;
                    }
                    html += '<div class="region-item ' + (c.online ? 'online' : 'offline') + '">' +
                            '<span class="st-dot"></span>' + esc(c.name) +
                            '<small>' + (c.online ? 'En línea' : 'Apagado') + '</small></div>';
                });
                list.innerHTML = html || '<div class="text-center text-muted py-3">Sin equipos</div>';
            })
            .catch(function () {
                list.innerHTML = '<div class="text-center text-muted py-3">Error al cargar</div>';
            });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-ver-equipos');
        if (!btn) return;
        openRegionModal(btn.getAttribute('data-region'), btn.getAttribute('data-region-name'));
    });

    window.addEventListener('dash:data-refresh', function () {
        clearTimeout(statsTimer);
        statsTimer = setTimeout(fetchMapStats, 600);
    });
    setInterval(fetchMapStats, 15000);

    if (document.readyState === 'complete') {
        initMap();
    } else {
        window.addEventListener('load', initMap);
    }
})();
</script>
<script>
(function () {
    'use strict';

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    /* ================= Actividad de agentes en vivo ================= */
    var KIND_ICONS = {
        vales: 'fa-coins',
        rbf: 'fa-fingerprint',
        descargas: 'fa-download',
        comandos: 'fa-terminal',
        sistema: 'fa-info-circle'
    };

    var actPaused = false;
    var actLastId = 0;
    var actFilter = 'todo';
    var arrivals = [];
    var actTimer = null;

    var feedEl = document.getElementById('activity-feed');
    var rateEl = document.getElementById('act-rate');
    var pauseBtn = document.getElementById('act-pause');

    if (pauseBtn) {
        pauseBtn.addEventListener('click', function () {
            actPaused = !actPaused;
            pauseBtn.innerHTML = actPaused ? '<i class="fas fa-play"></i>' : '<i class="fas fa-pause"></i>';
            pauseBtn.title = actPaused ? 'Reanudar feed' : 'Pausar feed';
        });
    }

    var filtersEl = document.getElementById('act-filters');
    if (filtersEl) {
        filtersEl.addEventListener('click', function (e) {
            var chip = e.target.closest('.act-chip');
            if (!chip) return;
            filtersEl.querySelectorAll('.act-chip').forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            actFilter = chip.getAttribute('data-kind');
            applyFeedFilter();
        });
    }

    function applyFeedFilter() {
        if (!feedEl) return;
        feedEl.querySelectorAll('.feed-item').forEach(function (item) {
            item.style.display = (actFilter === 'todo' || item._kind === actFilter) ? '' : 'none';
        });
    }

    function relAge(s) {
        if (s == null) return '';
        if (s < 5) return 'ahora';
        if (s < 60) return s + 's';
        if (s < 3600) return Math.floor(s / 60) + 'm';
        if (s < 86400) return Math.floor(s / 3600) + 'h';
        return Math.floor(s / 86400) + 'd';
    }

    function renderEvents(events, isFirst) {
        var newOnes = events.filter(function (ev) { return ev.id > actLastId; });
        if (!newOnes.length) return;

        var frag = document.createDocumentFragment();
        newOnes.sort(function (a, b) { return a.id - b.id; }).forEach(function (ev) {
            var div = document.createElement('div');
            div.className = 'feed-item kind-' + ev.kind + (isFirst ? '' : ' fresh');
            div._kind = ev.kind;
            div._evid = ev.id;
            eventsById[ev.id] = ev;
            div.innerHTML =
                '<i class="fas ' + (KIND_ICONS[ev.kind] || 'fa-info-circle') + '"></i>' +
                '<div class="fi-body"><div class="fi-head"><b>' + esc(ev.pc || 'PC') + '</b>' +
                '<small>' + esc(ev.plaza || '') + '</small></div>' +
                '<p title="' + esc(ev.message) + '">' + esc(ev.message) + '</p></div>' +
                '<time>' + relAge(ev.age_s) + '</time>';
            frag.appendChild(div);
        });
        feedEl.insertBefore(frag, feedEl.firstChild);

        setTimeout(function () {
            feedEl.querySelectorAll('.feed-item.fresh').forEach(function (el) { el.classList.remove('fresh'); });
        }, 800);

        while (feedEl.children.length > 40) {
            var last = feedEl.lastChild;
            if (last && last._evid != null) delete eventsById[last._evid];
            feedEl.removeChild(last);
        }

        if (!isFirst) {
            arrivals.push(Date.now());
            if (arrivals.length > 600) arrivals.splice(0, arrivals.length - 600);
        }
        applyFeedFilter();
    }

    function fetchActivity() {
        if (!feedEl || actPaused) return;
        fetch('{{ route('home.activity') }}?limit=30', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                if (!data.events || !data.events.length) return;
                var isFirst = actLastId === 0;
                renderEvents(data.events, isFirst);
                actLastId = Math.max(actLastId, data.events[0].id);
            })
            .catch(function () {});
    }

    setInterval(function () {
        var cutoff = Date.now() - 60000;
        while (arrivals.length && arrivals[0] < cutoff) arrivals.shift();
        if (rateEl) rateEl.textContent = arrivals.length + ' /min';
    }, 5000);

    /* ================= Matriz de salud del parque ================= */
    var healthData = [];
    var healthPlaza = '';
    var chipsBuilt = false;
    var hmTimer = null;

    var hmEl = document.getElementById('health-matrix');
    var hmChipsRow = document.querySelector('[data-card-id="health"] .act-filters');

    function stateLabel(st) {
        return st === 'ok' ? 'Sano' : st === 'warn' ? 'Degradado' : st === 'crit' ? 'Crítico' : 'Apagado';
    }

    function fetchHealth() {
        if (!hmEl) return;
        fetch('{{ route('home.fleet-health') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (data) {
                if (!data.computers) return;
                healthData = data.computers;
                ['ok', 'warn', 'crit', 'off'].forEach(function (k) {
                    var el = document.getElementById('hm-c-' + k);
                    if (el) el.textContent = data.counts[k] || 0;
                });
                buildPlazaChips();
                renderMatrix();
            })
            .catch(function () {});
    }

    function buildPlazaChips() {
        if (chipsBuilt || !hmChipsRow) return;
        chipsBuilt = true;
        var seen = {};
        healthData.forEach(function (pc) {
            var pz = pc.plaza || 'Sin plaza';
            if (seen[pz]) return;
            seen[pz] = 1;
            var chip = document.createElement('span');
            chip.className = 'act-chip';
            chip.setAttribute('data-hplaza', pz === 'Sin plaza' ? '__none__' : pz);
            chip.textContent = pz;
            hmChipsRow.appendChild(chip);
        });
        hmChipsRow.addEventListener('click', function (e) {
            var chip = e.target.closest('.act-chip');
            if (!chip) return;
            hmChipsRow.querySelectorAll('.act-chip').forEach(function (c) { c.classList.remove('active'); });
            chip.classList.add('active');
            healthPlaza = chip.getAttribute('data-hplaza');
            renderMatrix();
        });
    }

    function renderMatrix() {
        var groups = {};
        healthData.forEach(function (pc) {
            var pz = pc.plaza || 'Sin plaza';
            (groups[pz] = groups[pz] || []).push(pc);
        });
        var html = '';
        Object.keys(groups).sort().forEach(function (pz) {
            if (healthPlaza && pz !== (healthPlaza === '__none__' ? 'Sin plaza' : healthPlaza)) return;
            var pcs = groups[pz];
            html += '<div class="hm-group"><div class="hm-group-label">' + esc(pz) +
                ' <small>· ' + pcs.length + ' equipos</small></div><div class="hm-squares">';
            pcs.forEach(function (pc) {
                html += '<span class="hm-square s-' + pc.state + '" data-pcid="' + pc.id + '" title="' +
                    esc(pc.name) + ' · ' + stateLabel(pc.state) + ' (' + pc.score + ')"></span>';
            });
            html += '</div></div>';
        });
        hmEl.innerHTML = html || '<div class="text-center text-muted py-3">Sin equipos</div>';
    }

    if (hmEl) {
        hmEl.addEventListener('click', function (e) {
            var sq = e.target.closest('.hm-square');
            if (!sq) return;
            var id = parseInt(sq.getAttribute('data-pcid'), 10);
            var pc = null;
            for (var i = 0; i < healthData.length; i++) {
                if (healthData[i].id === id) { pc = healthData[i]; break; }
            }
            if (pc) openPcProfile(pc);
        });
    }

    function openPcProfile(pc) {
        var t = document.getElementById('pcp-title');
        if (t) t.textContent = pc.name;
        document.getElementById('pcp-score-num').textContent = pc.score;
        var fill = document.getElementById('pcp-score-fill');
        fill.style.width = pc.score + '%';
        fill.style.background = pc.state === 'ok' ? '#059669' : pc.state === 'warn' ? '#b45309' : pc.state === 'crit' ? '#dc2626' : '#334155';

        var d = pc.details || {};
        var rows = [
            ['Estado', pc.online ? '🟢 En línea' : '🔴 Apagado'],
            ['Plaza', pc.plaza || '—'],
            ['Agente', d.agente ? (d.agente + (d.agente_ok ? ' · ✓ actualizado' : ' · ✗ desactualizado')) : '— sin agente'],
            ['BitLocker', d.bitlocker || '—'],
            ['RAM', d.ram_gb != null ? d.ram_gb + ' GB' : '—'],
            ['PVSI', d.pvsi || '—'],
            ['Última conexión', d.last_seen || '—']
        ];
        document.getElementById('pcp-rows').innerHTML = rows.map(function (r) {
            return '<div class="pcp-row"><span style="color:#94a3b8">' + esc(r[0]) + '</span><b>' + esc(r[1]) + '</b></div>';
        }).join('');
        jQuery('#pcProfileModal').modal('show');
    }

    /* ================= Modal detalle de evento ================= */
    var eventsById = {};

    var KIND_LABELS = {
        vales: 'Vales',
        rbf: 'RBF',
        descargas: 'Descargas',
        comandos: 'Comandos',
        sistema: 'Sistema'
    };

    if (feedEl) {
        feedEl.addEventListener('click', function (e) {
            var item = e.target.closest('.feed-item');
            if (!item || item._evid == null) return;
            var ev = eventsById[item._evid];
            if (ev) openActivityModal(ev);
        });
    }

    function openActivityModal(ev) {
        var t = document.getElementById('actm-title');
        if (t) t.textContent = ev.pc || 'Evento';
        var rows = [
            ['Tipo', KIND_LABELS[ev.kind] || ev.kind],
            ['Nivel', ev.level || 'info'],
            ['Plaza', ev.plaza || '—'],
            ['Hace', relAge(ev.age_s)],
            ['Fecha y hora', ev.at || '—']
        ];
        document.getElementById('actm-rows').innerHTML = rows.map(function (r) {
            return '<div class="pcp-row"><span style="color:#94a3b8">' + esc(r[0]) + '</span><b>' + esc(r[1]) + '</b></div>';
        }).join('');
        document.getElementById('actm-message').textContent = ev.message;
        jQuery('#activityModal').modal('show');
    }

    window.addEventListener('dash:data-refresh', function () {
        clearTimeout(actTimer);
        actTimer = setTimeout(fetchActivity, 600);
        clearTimeout(hmTimer);
        hmTimer = setTimeout(fetchHealth, 1200);
    });

    setInterval(fetchActivity, 3000);
    setInterval(fetchHealth, 10000);
    fetchActivity();
    fetchHealth();
})();
</script>
@stop
