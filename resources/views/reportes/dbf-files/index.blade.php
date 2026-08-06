@extends('adminlte::page')
@section('title', 'Archivos DBF - Computadoras')

@section('content_header')
<h1>Dashboard Archivos DBF</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card bg-light mb-3">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-filter"></i> Filtros
      </h5>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Plazas</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_plazas" class="form-check-input">
              <label for="select_all_plazas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($plazas as $plaza)
            <div class="form-check">
              <input type="checkbox" name="plaza[]" value="{{ $plaza }}" id="plaza_{{ $plaza }}" class="form-check-input plaza-checkbox">
              <label for="plaza_{{ $plaza }}" class="form-check-label">{{ $plaza }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Grupos</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_groups" class="form-check-input">
              <label for="select_all_groups" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @foreach($groups as $group)
            <div class="form-check">
              <input type="checkbox" name="group_id[]" value="{{ $group->id }}" id="group_{{ $group->id }}" class="form-check-input group-checkbox">
              <label for="group_{{ $group->id }}" class="form-check-label">{{ $group->name }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Categorias Archivos</label>
          <select id="file_category_filter" class="form-control form-control-sm">
            <option value="">Todos los archivos</option>
            <option value="exe">Solo .EXE</option>
            <option value="quickbck">Solo QuickBCK</option>
            <option value="other">Otros (no .exe / no quickbck)</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Hash MD5</label>
          <input type="text" id="hash_filter" class="form-control form-control-sm" placeholder="Ej. 8B7060">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Buscar Computadora</label>
          <input type="text" id="computer_search" class="form-control form-control-sm" placeholder="Nombre o IP">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Estado Actualizacion</label>
          <select id="estado_filter" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="actualizado">Actualizado</option>
            <option value="desactualizado">Desactualizado</option>
          </select>
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="total_computadoras" class="badge bg-info align-self-center"></span>
          </div>
          <div class="d-flex gap-1 flex-wrap">
            <button id="btn_search" class="btn btn-success btn-sm">
              <i class="fas fa-search"></i> <span class="d-none d-sm-inline">Buscar</span>
            </button>
            <button id="btn_refresh" class="btn btn-primary btn-sm">
              <i class="fas fa-sync-alt"></i> <span class="d-none d-sm-inline">Actualizar</span>
            </button>
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm">
              <i class="fas fa-undo"></i> <span class="d-none d-sm-inline">Limpiar</span>
            </button>
            <button id="btn_export" class="btn btn-info btn-sm">
              <i class="fas fa-file-csv"></i> <span class="d-none d-sm-inline">Exportar CSV</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="summarySection" class="row g-2 mb-3">
    <div class="col-md-3 col-sm-6">
      <div class="card text-bg-light h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statTotalFiles">0</span>
          <small class="text-muted">Total Archivos</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-bg-success h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statMatchedFiles">0</span>
          <small>Actualizados</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-bg-danger h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statUnmatchedFiles">0</span>
          <small>Desactualizados</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card text-bg-info h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statPercent">0%</span>
          <small>Cumplimiento</small>
        </div>
      </div>
    </div>
  </div>

  <div id="categoryBreakdown" class="mb-3 d-none">
    <div class="card">
      <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="fas fa-chart-bar text-primary"></i>
        <small class="fw-bold">Conciliacion por Categoria</small>
      </div>
      <div class="card-body py-2">
        <div class="row g-2">
          <div class="col-md-4">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="fas fa-cog text-info" style="font-size:0.8rem;"></i>
              <small class="fw-bold">.EXE</small>
              <span class="badge bg-info" id="catExeBadge">0/0</span>
            </div>
            <div class="pct-bar" style="height:10px;">
              <div class="pct-bar-fill" id="catExeBar" style="width:0%;background:#0dcaf0;"></div>
            </div>
            <small class="text-muted" id="catExePercent">0%</small>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="fas fa-database text-warning" style="font-size:0.8rem;"></i>
              <small class="fw-bold">QuickBCK</small>
              <span class="badge bg-warning text-dark" id="catQuickbckBadge">0/0</span>
            </div>
            <div class="pct-bar" style="height:10px;">
              <div class="pct-bar-fill" id="catQuickbckBar" style="width:0%;background:#ffc107;"></div>
            </div>
            <small class="text-muted" id="catQuickbckPercent">0%</small>
          </div>
          <div class="col-md-4">
            <div class="d-flex align-items-center gap-2 mb-1">
              <i class="fas fa-file text-secondary" style="font-size:0.8rem;"></i>
              <small class="fw-bold">Otros</small>
              <span class="badge bg-secondary" id="catOtherBadge">0/0</span>
            </div>
            <div class="pct-bar" style="height:10px;">
              <div class="pct-bar-fill" id="catOtherBar" style="width:0%;background:#6c757d;"></div>
            </div>
            <small class="text-muted" id="catOtherPercent">0%</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="chartsSection" class="row g-3 mb-3 d-none">
    <div class="col-lg-4 col-md-6">
      <div class="card h-100">
        <div class="card-header py-2 d-flex align-items-center gap-2">
          <i class="fas fa-chart-pie text-info"></i>
          <small class="fw-bold">Actualizacion Archivos</small>
        </div>
        <div class="card-body py-3 text-center">
          <canvas id="pieFilesChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card h-100">
        <div class="card-header py-2 d-flex align-items-center gap-2">
          <i class="fas fa-plug text-success"></i>
          <small class="fw-bold">Estado de Conexion</small>
        </div>
        <div class="card-body py-3 text-center">
          <canvas id="pieConnectionChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card h-100">
        <div class="card-header py-2 d-flex align-items-center gap-2">
          <i class="fas fa-layer-group text-primary"></i>
          <small class="fw-bold">Computadoras por Grupo</small>
        </div>
        <div class="card-body py-3">
          <canvas id="barGroupChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card h-100">
        <div class="card-header py-2 d-flex align-items-center gap-2">
          <i class="fas fa-map-marker-alt text-warning"></i>
          <small class="fw-bold">Actualizacion por Plaza</small>
        </div>
        <div class="card-body py-3">
          <canvas id="barPlazaChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card h-100">
        <div class="card-header py-2 d-flex align-items-center gap-2">
          <i class="fas fa-file-alt text-success"></i>
          <small class="fw-bold">Top Archivos DBF</small>
        </div>
        <div class="card-body py-3">
          <canvas id="barFileChart"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="card h-100">
        <div class="card-header py-2 d-flex align-items-center gap-2">
          <i class="fas fa-exclamation-triangle text-danger"></i>
          <small class="fw-bold">Computadoras mas Desactualizadas</small>
        </div>
        <div class="card-body py-3">
          <canvas id="barOutdatedChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div id="plazaBreakdown" class="mb-3 d-none">
    <div class="card">
      <div class="card-header py-2 d-flex align-items-center gap-2">
        <i class="fas fa-map-marker-alt text-primary"></i>
        <small class="fw-bold">Resumen por Plaza</small>
      </div>
      <div class="card-body py-2" id="plazaBadges"></div>
    </div>
  </div>

  <div class="card" id="computersCard">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
      <h5 class="mb-0">
        <i class="fas fa-desktop"></i> Computadoras
      </h5>
    </div>
    <div class="card-body p-0">
      <div id="computersLoading" class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Cargando computadoras...</p>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover table-striped mb-0" id="computersTable">
          <thead class="table-dark">
            <tr>
              <th style="cursor:pointer" data-sort="nombre_instalacion">Computadora <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="plaza">Plaza <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="group_name">Grupo <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="status">Estado <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="last_seen">Ultima Conexion <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="dbf_files_count" class="text-center">Archivos <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="dbf_files_matched" class="text-center">Actualizados <i class="fas fa-sort"></i></th>
              <th class="text-center"><i class="fas fa-cog text-info"></i> .EXE</th>
              <th class="text-center"><i class="fas fa-database text-warning"></i> QuickBCK</th>
              <th class="text-center"><i class="fas fa-file text-secondary"></i> Otros</th>
              <th style="cursor:pointer" data-sort="pct" class="text-center">% Total <i class="fas fa-sort"></i></th>
              <th class="text-center">Detalle</th>
            </tr>
          </thead>
          <tbody id="computersTableBody">
          </tbody>
        </table>
      </div>
      <div id="computersPagination" class="d-flex justify-content-between align-items-center mt-2 p-2 d-none flex-wrap gap-2">
        <small class="text-muted" id="paginationInfo"></small>
        <nav><ul class="pagination pagination-sm mb-0" id="paginationNumbers"></ul></nav>
      </div>
    </div>
  </div>
</div>
@endsection

@section('css')
<style>
.card-header { border-bottom: 2px solid #dee2e6; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.badge { font-size: 0.7rem; }
.form-label { font-size: 0.75rem; }
.form-control-sm { font-size: 0.75rem; }
.card.text-bg-light .card-body,
.card.text-bg-success .card-body,
.card.text-bg-danger .card-body,
.card.text-bg-info .card-body {
  padding: 0.75rem 0.5rem;
}
.card.text-bg-light .fs-4,
.card.text-bg-success .fs-4,
.card.text-bg-danger .fs-4,
.card.text-bg-info .fs-4 {
  line-height: 1.2;
}
#plazaBadges .badge {
  border: 1px solid rgba(0,0,0,0.1);
}
#chartsSection .card-body {
  height: 280px;
  position: relative;
}
#chartsSection canvas {
  max-height: 100% !important;
  max-width: 100% !important;
}
#computersTable { font-size: 0.8rem; }
#computersTable thead th { white-space: nowrap; font-size: 0.75rem; }
#computersTable tbody tr { cursor: pointer; }
#computersTable tbody tr.row-detail-expanded { background-color: #e8f4fd !important; }
#computersTable tbody tr.file-row { background-color: #f8f9fa !important; cursor: default; }
#computersTable tbody tr.file-row td { font-size: 0.7rem; padding: 0.2rem 0.5rem; }
.detail-panel { display: none; }
.detail-panel.show { display: table-row; }
.file-table { width: 100%; font-size: 0.7rem; }
.file-table th { background: #e9ecef; font-weight: 600; white-space: nowrap; }
.file-table td { padding: 0.2rem 0.4rem; }
.pct-bar { height: 6px; border-radius: 3px; background: #dee2e6; overflow: hidden; }
.pct-bar-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
@media (max-width: 768px) {
  .btn-sm { padding: 0.2rem 0.4rem; font-size: 0.7rem; }
}
.detail-panel .accordion .card-header { transition: background 0.2s; }
.detail-panel .accordion .card-header:hover { filter: brightness(0.95); }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function formatAgentModifiedDate(modified) {
  if (!modified) return 'N/A';
  const value = String(modified).trim();
  if (/\b(?:AM|PM|am|pm)\b/.test(value)) return value;
  const patterns = [
    /^(\d{4}-\d{2}-\d{2})[ T](\d{1,2}:\d{2}(?::\d{2})?)(?:\.\d+)?(?:\s?(AM|PM|am|pm))?(?:[+-].*)?$/,
    /^(\d{2}\/\d{2}\/\d{4})[ T](\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(AM|PM|am|pm))?$/,
    /^(\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(AM|PM|am|pm))?$/,
  ];
  for (const pattern of patterns) {
    const match = value.match(pattern);
    if (match) {
      const datePart = match[1] || '';
      let timePart = match[2] || '';
      let ampm = match[3] ? match[3].toUpperCase() : '';
      const parts = timePart.split(':').map(Number);
      const hour = parts[0] || 0;
      const minute = parts[1] || 0;
      const second = parts[2] || 0;
      let hour12 = hour % 12;
      if (hour12 === 0) hour12 = 12;
      if (ampm === '') ampm = hour >= 12 ? 'PM' : 'AM';
      timePart = hour12 + ':' + String(minute).padStart(2, '0') + (second ? ':' + String(second).padStart(2, '0') : '');
      return (datePart ? datePart + ' ' : '') + timePart + ' ' + ampm;
    }
  }
  return value;
}

function getFilters() {
  var d = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  var grupos = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) d.plaza = plazas;
  if (grupos.length) d.group_id = grupos;
  if ($('#file_category_filter').val()) d.file_category = $('#file_category_filter').val();
  if ($('#hash_filter').val()) d.hash = $('#hash_filter').val();
  if ($('#computer_search').val()) d.search = $('#computer_search').val();
  if ($('#estado_filter').val()) d.estado = $('#estado_filter').val();
  return d;
}

var currentPage = 0;
var pageSize = 50;
var totalRecords = 0;
var lastJson = null;
var expandedRows = {};
var sortColumn = 'nombre_instalacion';
var sortDirection = 'asc';

function loadData() {
  var filters = getFilters();
  filters.draw = 1;
  filters.start = currentPage * pageSize;
  filters.length = pageSize;
  filters.search = filters.search || '';
  filters.sort = sortColumn;
  filters.direction = sortDirection;

  $('#computersLoading').removeClass('d-none');
  $('#computersTableBody').empty();

  $.ajax({
    url: "{{ url('/reportes/dbf-files/data') }}",
    type: 'GET',
    data: filters,
    success: function(json) {
      lastJson = json;
      if (json.error) { console.error(json.error); return; }
      renderStats(json);
      renderTable(json);
    },
    error: function(xhr) {
      $('#computersLoading').addClass('d-none');
      console.error('Error loading data');
    }
  });
}

function renderStats(json) {
  $('#total_computadoras').text('Total: ' + (json.recordsTotal || 0) + ' computadoras');

  if (!json.rbf_stats) return;
  var s = json.rbf_stats;
  $('#statTotalFiles').text(s.total_files);
  $('#statMatchedFiles').text(s.total_matched);
  $('#statUnmatchedFiles').text(s.total_unmatched);
  $('#statPercent').text(s.percent + '%');

  if (s.per_category) {
    var cats = s.per_category;
    var exe = cats.exe || {total:0,matched:0,percent:0};
    var qbck = cats.quickbck || {total:0,matched:0,percent:0};
    var oth = cats.other || {total:0,matched:0,percent:0};

    $('#catExeBadge').text(exe.matched + '/' + exe.total);
    $('#catExeBar').css('width', exe.percent + '%').css('background', exe.percent === 100 ? '#28a745' : exe.percent >= 50 ? '#ffc107' : '#dc3545');
    $('#catExePercent').text(exe.percent + '% (' + exe.matched + '/' + exe.total + ')');

    $('#catQuickbckBadge').text(qbck.matched + '/' + qbck.total);
    $('#catQuickbckBar').css('width', qbck.percent + '%').css('background', qbck.percent === 100 ? '#28a745' : qbck.percent >= 50 ? '#ffc107' : '#dc3545');
    $('#catQuickbckPercent').text(qbck.percent + '% (' + qbck.matched + '/' + qbck.total + ')');

    $('#catOtherBadge').text(oth.matched + '/' + oth.total);
    $('#catOtherBar').css('width', oth.percent + '%').css('background', oth.percent === 100 ? '#28a745' : oth.percent >= 50 ? '#ffc107' : '#dc3545');
    $('#catOtherPercent').text(oth.percent + '% (' + oth.matched + '/' + oth.total + ')');

    $('#categoryBreakdown').removeClass('d-none');
  }

  if (s.total_files === 0) {
    $('#chartsSection').addClass('d-none');
    $('#plazaBreakdown').addClass('d-none');
    $('#categoryBreakdown').addClass('d-none');
    return;
  }

  $('#chartsSection').removeClass('d-none');

  requestAnimationFrame(function() {
    initAllCharts(s);
  });

  var $badges = $('#plazaBadges').empty();
  if (s.per_plaza && s.per_plaza.length > 0) {
    s.per_plaza.forEach(function(p) {
      var color = p.percent === 100 ? 'success' : p.percent >= 50 ? 'warning text-dark' : 'danger';
      $badges.append('<span class="badge bg-'+color+' me-1 mb-1 p-2" style="font-size:0.8rem;">' +
        p.plaza + ': ' + p.matched + '/' + p.total + ' (' + p.percent + '%)</span>');
    });
    $('#plazaBreakdown').removeClass('d-none');
  }
}

var chartInstances = {};
function initChart(id, config) {
  var canvas = document.getElementById(id);
  if (!canvas) return null;
  if (chartInstances[id]) { chartInstances[id].destroy(); }
  var c = new Chart(canvas, config);
  chartInstances[id] = c;
  return c;
}

function initAllCharts(s) {
  var green = '#28a745', red = '#dc3545', blue = '#007bff', orange = '#fd7e14', purple = '#6f42c1';

  initChart('pieFilesChart', {
    type: 'doughnut',
    data: {
      labels: ['Actualizados', 'Desactualizados'],
      datasets: [{ data: [s.total_matched, s.total_unmatched], backgroundColor: [green, red], borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, resizeDelay: 100, cutout: '60%',
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
        tooltip: { callbacks: { label: function(ctx) {
          var pct = s.total_files > 0 ? ((ctx.parsed / s.total_files) * 100).toFixed(1) : 0;
          return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
        }}}
      }
    }
  });

  var onlineCount = 0, offlineCount = 0;
  if (s.per_group) {
    s.per_group.forEach(function(g) { onlineCount += g.online; offlineCount += g.offline; });
  }

  initChart('pieConnectionChart', {
    type: 'doughnut',
    data: {
      labels: ['Online', 'Offline'],
      datasets: [{ data: [onlineCount, offlineCount], backgroundColor: [green, red], borderWidth: 0 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, resizeDelay: 100, cutout: '60%',
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
        tooltip: { callbacks: { label: function(ctx) {
          var total = onlineCount + offlineCount;
          var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
          return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
        }}}
      }
    }
  });

  if (s.per_group && s.per_group.length > 0) {
    var gLabels = s.per_group.map(function(g) { return g.name; });
    initChart('barGroupChart', {
      type: 'bar',
      data: {
        labels: gLabels,
        datasets: [
          { label: 'Online', data: s.per_group.map(function(g) { return g.online; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Offline', data: s.per_group.map(function(g) { return g.offline; }), backgroundColor: red, borderRadius: 3 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false, resizeDelay: 100,
        scales: {
          x: { stacked: true, ticks: { font: { size: 10 } }, grid: { display: false } },
          y: { stacked: true, beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f0f0f0' } }
        },
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
          tooltip: { callbacks: { label: function(ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y; }} }
        }
      }
    });
  }

  if (s.per_plaza && s.per_plaza.length > 0) {
    var pLabels = s.per_plaza.map(function(p) { return p.plaza; });
    initChart('barPlazaChart', {
      type: 'bar',
      data: {
        labels: pLabels,
        datasets: [
          { label: 'Actualizados', data: s.per_plaza.map(function(p) { return p.matched; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Desactualizados', data: s.per_plaza.map(function(p) { return p.unmatched; }), backgroundColor: red, borderRadius: 3 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false, resizeDelay: 100,
        scales: {
          x: { stacked: true, ticks: { font: { size: 10 } }, grid: { display: false } },
          y: { stacked: true, beginAtZero: true, ticks: { font: { size: 10 } }, grid: { color: '#f0f0f0' } }
        },
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
          tooltip: { callbacks: { label: function(ctx) {
            var plaza = s.per_plaza[ctx.dataIndex];
            var total = plaza.matched + plaza.unmatched;
            var pct = total > 0 ? ((ctx.parsed.y / total) * 100).toFixed(1) : 0;
            return ctx.dataset.label + ': ' + ctx.parsed.y + ' (' + pct + '%)';
          }}}
        }
      }
    });
  }

  if (s.per_file && s.per_file.length > 0) {
    var fLabels = s.per_file.map(function(f) { return f.name; });
    initChart('barFileChart', {
      type: 'bar',
      data: {
        labels: fLabels,
        datasets: [
          { label: 'Actualizados', data: s.per_file.map(function(f) { return f.matched; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Desactualizados', data: s.per_file.map(function(f) { return f.unmatched; }), backgroundColor: red, borderRadius: 3 }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false, resizeDelay: 100,
        scales: {
          x: { stacked: true, beginAtZero: true, ticks: { font: { size: 9 } }, grid: { color: '#f0f0f0' } },
          y: { stacked: true, ticks: { font: { size: 8 } }, grid: { display: false } }
        },
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
          tooltip: { callbacks: { label: function(ctx) {
            var file = s.per_file[ctx.dataIndex];
            var pct = file.total > 0 ? ((ctx.parsed.x / file.total) * 100).toFixed(1) : 0;
            return ctx.dataset.label + ': ' + ctx.parsed.x + ' (' + pct + '%)';
          }}}
        }
      }
    });
  }

  if (s.top_outdated && s.top_outdated.length > 0) {
    var oLabels = s.top_outdated.map(function(o) { return o.name; });
    initChart('barOutdatedChart', {
      type: 'bar',
      data: {
        labels: oLabels,
        datasets: [
          { label: 'Actualizados', data: s.top_outdated.map(function(o) { return o.matched; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Desactualizados', data: s.top_outdated.map(function(o) { return o.unmatched; }), backgroundColor: red, borderRadius: 3 }
        ]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false, resizeDelay: 100,
        scales: {
          x: { stacked: true, beginAtZero: true, ticks: { font: { size: 9 } }, grid: { color: '#f0f0f0' } },
          y: { stacked: true, ticks: { font: { size: 8 } }, grid: { display: false } }
        },
        plugins: {
          legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
          tooltip: { callbacks: { label: function(ctx) {
            var item = s.top_outdated[ctx.dataIndex];
            var total = item.matched + item.unmatched;
            var pct = total > 0 ? ((ctx.parsed.x / total) * 100).toFixed(1) : 0;
            return ctx.dataset.label + ': ' + ctx.parsed.x + ' (' + pct + '%)';
          }}}
        }
      }
    });
  }
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var $tbody = $('#computersTableBody');
  var $loading = $('#computersLoading');
  $loading.addClass('d-none');
  $tbody.empty();

  if (data.length === 0) {
    $tbody.html('<tr><td colspan="11" class="text-center py-4 text-muted">No se encontraron computadoras</td></tr>');
    $('#computersPagination').addClass('d-none');
    return;
  }

  data.forEach(function(comp) {
    var total = comp.dbf_files_count || 0;
    var matched = comp.dbf_files_matched || 0;
    var pct = total > 0 ? Math.round((matched / total) * 100) : 0;
    var barColor = pct === 100 ? '#28a745' : pct >= 50 ? '#ffc107' : '#dc3545';
    var statusBadge = comp.status === 'online'
      ? '<span class="badge bg-success">Online</span>'
      : '<span class="badge bg-danger">Offline</span>';
    var compId = comp.id || comp.nombre_instalacion;

    var catExe = comp.exe || {matched:0,total:0};
    var catQbck = comp.quickbck || {matched:0,total:0};
    var catOth = comp.other || {matched:0,total:0};
    var catBar = function(cat) {
      var t = cat.total || 0;
      var m = cat.matched || 0;
      var p = t > 0 ? Math.round((m/t)*100) : 0;
      var c = p === 100 ? '#28a745' : p >= 50 ? '#ffc107' : '#dc3545';
      return '<div class="d-flex align-items-center gap-1">' +
        '<div class="pct-bar flex-grow-1" style="height:5px;"><div class="pct-bar-fill" style="width:'+p+'%;background:'+c+';"></div></div>' +
        '<small style="color:'+c+';font-size:0.65rem;">'+p+'%</small>' +
      '</div>';
    };

    var mainRow = '<tr class="computer-row" data-comp-id="' + compId + '">' +
      '<td><strong>' + (comp.nombre_instalacion || 'N/A') + '</strong></td>' +
      '<td>' + (comp.plaza || 'N/A') + '</td>' +
      '<td>' + (comp.group_name || 'N/A') + '</td>' +
      '<td>' + statusBadge + '</td>' +
      '<td style="font-size:0.75rem;">' + (comp.last_seen || 'N/A') + '</td>' +
      '<td class="text-center"><span class="badge bg-secondary">' + total + '</span></td>' +
      '<td class="text-center"><span class="badge bg-success">' + matched + '</span></td>' +
      '<td style="min-width:60px;">' + catBar(catExe) + '</td>' +
      '<td style="min-width:60px;">' + catBar(catQbck) + '</td>' +
      '<td style="min-width:60px;">' + catBar(catOth) + '</td>' +
      '<td class="text-center" style="min-width:80px;">' +
        '<div class="d-flex align-items-center gap-1">' +
          '<div class="pct-bar flex-grow-1"><div class="pct-bar-fill" style="width:' + pct + '%;background:' + barColor + ';"></div></div>' +
          '<small class="fw-bold" style="color:' + barColor + ';">' + pct + '%</small>' +
        '</div>' +
      '</td>' +
      '<td class="text-center"><button class="btn btn-outline-info btn-sm btn-toggle-detail"><i class="fas fa-chevron-down"></i></button></td>' +
    '</tr>';

    var filesHtml = '';
    if (comp.dbf_files && comp.dbf_files.length > 0) {
      var filesExe = [], filesQbck = [], filesOth = [];
      comp.dbf_files.forEach(function(file) {
        var name = (file.name || '').toUpperCase();
        var path = (file.path || '').toLowerCase();
        var ext = name.split('.').pop();
        if (ext === 'EXE') { filesExe.push(file); }
        else if (path.indexOf('quickbck') !== -1 || name.indexOf('QUICKBCK') !== -1) { filesQbck.push(file); }
        else { filesOth.push(file); }
      });

      var renderFileTable = function(files) {
        if (files.length === 0) return '<p class="text-muted small mb-2">Sin archivos</p>';
        var html = '<div class="table-responsive mt-1 mb-2"><table class="table table-sm table-bordered file-table mb-0">' +
          '<thead><tr><th>Nombre</th><th>Ruta</th><th>Tamano</th><th>Modificacion</th>' +
          '<th>MD5</th><th>Ruta RBF</th><th>Hash RBF</th><th>Estado</th></tr></thead><tbody>';
        files.forEach(function(file) {
          var size = file.size ? (file.size / 1024).toFixed(2) + ' KB' : 'N/A';
          var modified = formatAgentModifiedDate(file.modified || '');
          var rbfStatus = file.rbf_matched
            ? '<span class="badge bg-success">OK</span>'
            : '<span class="badge bg-danger">Falta</span>';
          html += '<tr>' +
            '<td><strong>' + (file.name || 'N/A') + '</strong></td>' +
            '<td style="word-break:break-all;">' + (file.path || 'N/A') + '</td>' +
            '<td>' + size + '</td>' +
            '<td style="white-space:nowrap;">' + modified + '</td>' +
            '<td style="word-break:break-all;"><code style="font-size:0.65rem;">' + (file.hash_md5 || '') + '</code></td>' +
            '<td style="word-break:break-all;">' + (file.rbf_path || '') + '</td>' +
            '<td style="word-break:break-all;"><code style="font-size:0.65rem;">' + (file.rbf_hash || '') + '</code></td>' +
            '<td class="text-center">' + rbfStatus + '</td>' +
          '</tr>';
        });
        html += '</tbody></table></div>';
        return html;
      };

      var badgeExe = filesExe.length ? '<span class="badge bg-info ms-1">' + filesExe.length + '</span>' : '';
      var badgeQbck = filesQbck.length ? '<span class="badge bg-warning text-dark ms-1">' + filesQbck.length + '</span>' : '';
      var badgeOth = filesOth.length ? '<span class="badge bg-secondary ms-1">' + filesOth.length + '</span>' : '';

      filesHtml = '<tr class="detail-panel" id="detail-' + compId + '">' +
        '<td colspan="12" class="p-0">' +
          '<div class="p-2">' +
            '<small class="text-muted fw-bold mb-2 d-block">Archivos de ' + (comp.nombre_instalacion || '') + '</small>' +
            '<div class="accordion" id="acc-' + compId + '">' +
              '<div class="card mb-1 border-0">' +
                '<div class="card-header py-1 px-2" style="background:#e3f2fd;cursor:pointer;" data-toggle="collapse" data-target="#acc-exe-' + compId + '">' +
                  '<small class="fw-bold text-info"><i class="fas fa-cog"></i> .EXE</small>' + badgeExe +
                  '<i class="fas fa-chevron-down float-end mt-1" style="font-size:0.7rem;"></i>' +
                '</div>' +
                '<div id="acc-exe-' + compId + '" class="collapse show" data-parent="#acc-' + compId + '">' +
                  '<div class="card-body p-0">' + renderFileTable(filesExe) + '</div>' +
                '</div>' +
              '</div>' +
              '<div class="card mb-1 border-0">' +
                '<div class="card-header py-1 px-2" style="background:#fff8e1;cursor:pointer;" data-toggle="collapse" data-target="#acc-qbck-' + compId + '">' +
                  '<small class="fw-bold text-warning"><i class="fas fa-database"></i> QuickBCK</small>' + badgeQbck +
                  '<i class="fas fa-chevron-down float-end mt-1" style="font-size:0.7rem;"></i>' +
                '</div>' +
                '<div id="acc-qbck-' + compId + '" class="collapse" data-parent="#acc-' + compId + '">' +
                  '<div class="card-body p-0">' + renderFileTable(filesQbck) + '</div>' +
                '</div>' +
              '</div>' +
              '<div class="card mb-1 border-0">' +
                '<div class="card-header py-1 px-2" style="background:#f5f5f5;cursor:pointer;" data-toggle="collapse" data-target="#acc-oth-' + compId + '">' +
                  '<small class="fw-bold text-secondary"><i class="fas fa-file"></i> Otros</small>' + badgeOth +
                  '<i class="fas fa-chevron-down float-end mt-1" style="font-size:0.7rem;"></i>' +
                '</div>' +
                '<div id="acc-oth-' + compId + '" class="collapse" data-parent="#acc-' + compId + '">' +
                  '<div class="card-body p-0">' + renderFileTable(filesOth) + '</div>' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</td></tr>';
    }

    $tbody.append(mainRow + filesHtml);
  });

  updatePagination();
}

function updatePagination() {
  var totalPages = Math.ceil(totalRecords / pageSize);
  if (totalPages <= 1) {
    $('#computersPagination').addClass('d-none');
    return;
  }
  $('#computersPagination').removeClass('d-none');
  var from = currentPage * pageSize + 1;
  var to = Math.min((currentPage + 1) * pageSize, totalRecords);
  $('#paginationInfo').text('Mostrando ' + from + ' a ' + to + ' de ' + totalRecords + ' computadoras');

  var $ul = $('#paginationNumbers').empty();
  var startPage = Math.max(0, currentPage - 2);
  var endPage = Math.min(totalPages, startPage + 5);
  if (endPage - startPage < 5) {
    startPage = Math.max(0, endPage - 5);
  }

  if (currentPage > 0) {
    $ul.append('<li class="page-item"><a class="page-link page-btn" href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>');
  }
  for (var i = startPage; i < endPage; i++) {
    var active = i === currentPage ? ' active' : '';
    $ul.append('<li class="page-item' + active + '"><a class="page-link page-btn" href="#" data-page="' + i + '">' + (i + 1) + '</a></li>');
  }
  if (currentPage < totalPages - 1) {
    $ul.append('<li class="page-item"><a class="page-link page-btn" href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>');
  }
}

$(function() {
  loadData();

  $('#btn_search').on('click', function() { currentPage = 0; loadData(); });
  $('#btn_refresh').on('click', function() { currentPage = 0; loadData(); });

  $('#paginationNumbers').on('click', '.page-btn', function(e) {
    e.preventDefault();
    currentPage = parseInt($(this).data('page'));
    loadData();
  });

  $('#btn_reset_filters').on('click', function() {
    $('.plaza-checkbox').prop('checked', false);
    $('.group-checkbox').prop('checked', false);
    $('#select_all_plazas').prop('checked', false);
    $('#select_all_groups').prop('checked', false);
    $('#file_category_filter').val('');
    $('#hash_filter').val('');
    $('#computer_search').val('');
    $('#estado_filter').val('');
    currentPage = 0;
    loadData();
  });

  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
  });
  $('#select_all_groups').on('change', function() {
    $('.group-checkbox').prop('checked', $(this).prop('checked'));
  });
  $('.plaza-checkbox, .group-checkbox').on('change', function() {
    currentPage = 0; loadData();
  });
  $('#archivo_filter').on('change', function() {
    currentPage = 0; loadData();
  });
  $('#file_category_filter').on('change', function() {
    currentPage = 0; loadData();
  });
  $('#estado_filter').on('change', function() {
    currentPage = 0; loadData();
  });
  $('#hash_filter').on('keypress', function(e) {
    if (e.which === 13) { currentPage = 0; loadData(); }
  });
  $('#computer_search').on('keypress', function(e) {
    if (e.which === 13) { currentPage = 0; loadData(); }
  });

  $('#btn_export').on('click', function() {
    var plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
    var gruposSeleccionados = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
    var fileCategory = $('#file_category_filter').val();
    var hash = $('#hash_filter').val();
    var params = new URLSearchParams();
    plazasSeleccionadas.forEach(function(val) { params.append('plaza[]', val); });
    gruposSeleccionados.forEach(function(val) { params.append('group_id[]', val); });
    if (fileCategory) params.append('file_category', fileCategory);
    if (hash) params.append('hash', hash);
    params.append('_t', Date.now());
    window.open("{{ url('/reportes/dbf-files/export') }}?" + params.toString(), '_blank');
  });

  $('#computersTableBody').on('click', '.btn-toggle-detail', function(e) {
    e.stopPropagation();
    var $row = $(this).closest('.computer-row');
    var compId = $row.data('comp-id');
    var $detail = $('#detail-' + compId);
    if ($detail.length) {
      var isExpanded = $detail.hasClass('show');
      $detail.toggleClass('show');
      $row.toggleClass('row-detail-expanded');
      $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
    }
  });

  $('#computersTableBody').on('click', '.computer-row', function(e) {
    if ($(e.target).closest('.btn-toggle-detail').length) return;
    $(this).find('.btn-toggle-detail').trigger('click');
  });

  $('#computersTable thead th[data-sort]').on('click', function() {
    var col = $(this).data('sort');
    if (sortColumn === col) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = col;
      sortDirection = 'asc';
    }
    $('#computersTable thead th i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
    $(this).find('i').removeClass('fa-sort').addClass(sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
    currentPage = 0;
    loadData();
  });
});
</script>
@endsection
