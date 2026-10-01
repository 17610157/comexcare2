@extends('adminlte::page')
@section('title', 'Reporte de Archivos de Stock')

@section('content_header')
<h1>Reporte de Archivos de Stock</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card bg-light mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-filter"></i> Filtros
      </h5>
      <button type="button" class="btn-card-minimize" title="Minimizar">
        <i class="fas fa-minus"></i>
      </button>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipos de Grupo</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar tipo..." aria-label="Buscar tipo de grupo"
                 data-target="#group_type_list" autocomplete="off">
          <div class="border rounded p-2" id="group_type_list" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_types" class="form-check-input">
              <label for="select_all_types" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @php
            $groupTypes = $groups->pluck('type')->filter()->unique()->sort()->values();
            @endphp
            @foreach($groupTypes as $type)
            <div class="form-check group-type-item" data-label="{{ strtolower($type) }}">
              <input type="checkbox" name="type[]" value="{{ $type }}" id="type_{{ $type }}" class="form-check-input group-type-checkbox">
              <label for="type_{{ $type }}" class="form-check-label">{{ ucfirst($type) }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results" data-empty-for="#group_type_list">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Plazas</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar plaza..." aria-label="Buscar plaza"
                 data-target="#plaza_list" autocomplete="off">
          <div class="border rounded p-2" id="plaza_list" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_plazas" class="form-check-input">
              <label for="select_all_plazas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($plazas as $plaza)
            <div class="form-check plaza-item" data-label="{{ strtolower($plaza) }}">
              <input type="checkbox" name="plaza[]" value="{{ $plaza }}" id="plaza_{{ $plaza }}" class="form-check-input plaza-checkbox">
              <label for="plaza_{{ $plaza }}" class="form-check-label">{{ $plaza }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results" data-empty-for="#plaza_list">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Archivos de Stock</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar archivo..." aria-label="Buscar archivo de stock"
                 data-target="#archivo_list" autocomplete="off">
          <div class="border rounded p-1" id="archivo_list" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check px-1">
              <input type="checkbox" id="select_all_archivos" class="form-check-input">
              <label for="select_all_archivos" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @foreach($archivos as $archivo)
            <div class="form-check archivo-item px-1" data-label="{{ strtolower($archivo) }}">
              <input type="checkbox" name="archivo[]" value="{{ $archivo }}" id="archivo_{{ $loop->index }}"
                     class="form-check-input archivo-checkbox" checked>
              <label for="archivo_{{ $loop->index }}" class="form-check-label">{{ $archivo }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results px-1" data-empty-for="#archivo_list">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
          <small class="text-muted d-block mt-1 text-end"><span id="archivo_selected_count">0</span> sel.</small>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Buscar Agente</label>
          <input type="text" id="agent_search" class="form-control form-control-sm mb-1"
                 placeholder="Nombre, clave o IP" autocomplete="off">
          <div class="border rounded p-1" id="agent_list" style="max-height: 130px; overflow-y: auto;">
            <div class="text-muted small px-1 py-1" id="agent_list_empty">Escribe para buscar agentes</div>
          </div>
          <div class="d-flex align-items-center justify-content-between mt-1">
            <div class="form-check mb-0">
              <input type="checkbox" id="agent_select_all" class="form-check-input">
              <label for="agent_select_all" class="form-check-label small mb-0">Todos</label>
            </div>
            <small class="text-muted"><span id="agent_selected_count">0</span> sel.</small>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Conexión</label>
          <select id="conexion_filter" class="form-control form-control-sm">
            <option value="">Todas</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Estado</label>
          <select id="estado_filter" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="actualizado">Actualizado</option>
            <option value="desactualizado">Desactualizado</option>
            <option value="vacio">Archivo vacío</option>
          </select>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="total_records" class="badge bg-info"></span>
          </div>
          <div class="d-flex gap-1 flex-wrap">
            <button id="btn_search" class="btn btn-success btn-sm">
              <i class="fas fa-search"></i> Buscar
            </button>
            <button id="btn_refresh" class="btn btn-primary btn-sm" title="Actualizar">
              <i class="fas fa-sync-alt"></i>
            </button>
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm" title="Limpiar">
              <i class="fas fa-undo"></i>
            </button>
            <button id="btn_export" class="btn btn-info btn-sm" title="Descargar CSV con los filtros actuales">
              <i class="fas fa-file-csv"></i> CSV
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3" id="chartsCard">
    <div class="card-header py-2 d-flex align-items-center gap-2">
      <i class="fas fa-chart-bar text-info"></i>
      <small class="fw-bold">Graficas</small>
      <button type="button" class="btn-card-minimize ms-auto" title="Minimizar"><i class="fas fa-minus"></i></button>
    </div>
    <div class="card-body py-2">
      <div class="row g-2 mb-3">
        <div class="col-md col-sm-6">
          <div class="card text-bg-light h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statTotalFiles">0</span>
              <small class="text-muted">Total Archivos</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-success h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statMatchedFiles">0</span>
              <small>Actualizados</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-danger h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statUnmatchedFiles">0</span>
              <small>Desactualizados</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-secondary h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statEmptyFiles">0</span>
              <small>Archivos vacíos</small>
            </div>
          </div>
        </div>
        <div class="col-md col-sm-6">
          <div class="card text-bg-info h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statPercent">0%</span>
              <small>Cumplimiento</small>
            </div>
          </div>
        </div>
      </div>
      <div id="chartsSection" class="row g-3 mb-0 d-none">
        <div class="col-lg-6 col-md-6">
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
        <div class="col-lg-6 col-md-6">
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
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-end align-items-center flex-wrap gap-2">
      <div class="d-flex align-items-center" style="gap: .25rem;">
        <label for="pageSizeSelect" class="mb-0 small text-white">Mostrar</label>
        <select id="pageSizeSelect" class="form-control form-control-sm" style="width: auto;">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
      <div id="paginationControls" class="d-flex align-items-center flex-wrap gap-2 d-none">
        <small class="text-white" id="paginationInfo"></small>
        <nav><ul class="pagination pagination-sm mb-0" id="paginationNumbers"></ul></nav>
      </div>
      <button type="button" class="btn-card-minimize" title="Minimizar">
        <i class="fas fa-minus"></i>
      </button>
    </div>
    <div class="card-body p-0">
      <div id="tableLoading" class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Cargando datos...</p>
      </div>
      <div class="table-responsive table-scroll">
        <table class="table table-sm table-hover table-striped mb-0" id="filesTable">
          <thead class="table-dark">
            <tr>
              <th style="cursor:pointer" data-sort="plaza">Plaza <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="nombre_instalacion">Agente <i class="fas fa-sort"></i></th>
              <th class="text-center">Estado Equipo</th>
              <th colspan="4" class="text-center border-start border-light">RBF</th>
              <th colspan="4" class="text-center border-start border-light">Rebsamen</th>
              <th class="text-center" style="cursor:pointer" data-sort="estado">Estado <i class="fas fa-sort"></i></th>
            </tr>
            <tr>
              <th></th>
              <th></th>
              <th></th>
              <th style="cursor:pointer" data-sort="archivo">Archivo <i class="fas fa-sort"></i></th>
              <th>Hash</th>
              <th>Fecha Mod</th>
              <th class="text-center">Peso (KB)</th>
              <th>Archivo</th>
              <th>Hash</th>
              <th>Fecha Mod</th>
              <th class="text-center">Peso (KB)</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="filesTableBody">
          </tbody>
        </table>
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
#chartsSection .card-body {
  height: 280px;
  position: relative;
}
#chartsSection canvas {
  max-height: 100% !important;
  max-width: 100% !important;
}
#filesTable { font-size: 0.75rem; }
#filesTable thead th { white-space: nowrap; font-size: 0.7rem; }
#filesTable thead tr:nth-child(2) th { font-size: 0.65rem; font-weight: 500; }
#filesTable tbody td { font-size: 0.75rem; vertical-align: middle; }
.table-scroll { max-height: 55vh; overflow-y: auto; }
.table-scroll #filesTable thead tr:first-child th {
  position: sticky;
  top: 0;
  z-index: 3;
  background-color: #23272f;
}
.table-scroll #filesTable thead tr:nth-child(2) th {
  position: sticky;
  top: 28px;
  z-index: 2;
  background-color: #343a40;
}
#paginationNumbers .page-link { padding: 0.15rem 0.45rem; }
.btn-card-minimize {
  background: transparent;
  border: none;
  color: inherit;
  opacity: .65;
  padding: 0.15rem 0.45rem;
  font-size: 0.85rem;
  line-height: 1.4;
  cursor: pointer;
}
.btn-card-minimize:hover { opacity: 1; }
.btn-card-minimize:focus { outline: none; box-shadow: none; }
.hash-chip {
  display: inline-block;
  font-family: monospace;
  font-size: 0.68rem;
  font-weight: 600;
  color: #fff;
  border-radius: 3px;
  padding: 0 4px;
  white-space: nowrap;
}
.celda-vacia { font-size: 0.62rem; color: #6c757d; font-style: italic; }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var DATA_URL = "{{ url('/reportes/archivos-stock/data') }}";
var EXPORT_URL = "{{ url('/reportes/archivos-stock/export') }}";
var AGENTES_URL = "{{ url('/reportes/archivos-stock/agentes') }}";

// Agentes marcados con checkbox. Se conservan al cambiar plaza/tipo/busqueda
// para que el filtro no se pierda, pero si no hay ninguno no se envía.
var selectedAgents = {};

function selectedAgentIds() {
  return Object.keys(selectedAgents).filter(function(id) { return selectedAgents[id]; }).map(Number);
}

function refreshAgentCount() {
  $('#agent_selected_count').text(selectedAgentIds().length);
  var total = Object.keys(selectedAgents).length;
  var checked = selectedAgentIds().length;
  $('#agent_select_all').prop('checked', total > 0 && checked === total).prop('indeterminate', checked > 0 && checked < total);
}

function esc(s) {
  return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
    return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
  });
}

function renderAgentList(data, truncated) {
  var $list = $('#agent_list');
  if (!data || data.length === 0) {
    $list.html('<div class="text-muted small px-1 py-1">Sin agentes para este filtro</div>');
    return;
  }
  var html = '';
  data.forEach(function(a) {
    var checked = selectedAgents[a.id] ? ' checked' : '';
    var label = a.nombre || a.short_key || ('#' + a.id);
    html += '<div class="form-check mb-0 py-1 px-1">' +
      '<input type="checkbox" class="form-check-input agent-checkbox" id="agente_' + a.id + '" value="' + a.id + '"' + checked + '>' +
      '<label class="form-check-label small text-truncate d-block" for="agente_' + a.id + '" title="' + esc(label + ' · ' + a.short_key + (a.ip ? ' · ' + a.ip : '')) + '" style="max-width:100%">' +
      esc(label) +
      ' <span class="text-muted">(' + esc(a.short_key) + ')</span>' +
      '</label></div>';
  });
  if (truncated) {
    html += '<div class="text-muted small px-1 py-1">Use la búsqueda para acotar la lista.</div>';
  }
  $list.html(html);
  refreshAgentCount();
}

function loadAgents() {
  var params = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) params.plaza = plazas;
  var tipos = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (tipos.length) params.type = tipos;
  var q = $.trim($('#agent_search').val());
  if (q) params.q = q;

  $.ajax({
    url: AGENTES_URL,
    type: 'GET',
    data: params,
    dataType: 'json',
    success: function(json) {
      renderAgentList(json.data, json.truncado);
    },
    error: function() {
      $('#agent_list').html('<div class="text-danger small px-1 py-1">No se pudieron cargar los agentes</div>');
    }
  });
}

// Filtra una lista de checkboxes por texto, sin deseleccionar lo ya marcado.
function applyListFilter($input) {
  var $list = $($input.data('target'));
  var term = $.trim($input.val()).toLowerCase();
  var visibles = 0;
  $list.find('.group-type-item, .plaza-item, .archivo-item').each(function() {
    var coincide = term === '' || ($(this).data('label') || '').indexOf(term) !== -1;
    $(this).toggle(coincide);
    if (coincide) visibles++;
  });
  $list.find('.no-filter-results').toggle(visibles === 0 && term !== '');
}

// Un checkbox "Todos" que opera sobre lo que se ve tras la busqueda,
// no sobre la lista completa.
function updateSelectAll($selector, $container, $itemSelector) {
  var visibles = $container.find($itemSelector).filter(':visible').length;
  var marcados = $container.find($itemSelector).filter(':visible')
    .find('input[type="checkbox"]').filter(':checked').length;
  $selector.prop('checked', visibles > 0 && marcados === visibles)
    .prop('indeterminate', marcados > 0 && marcados < visibles);
}

function refreshArchivoCount() {
  $('#archivo_selected_count').text($('.archivo-checkbox:checked').length);
}

function refreshAllSelectAll() {
  updateSelectAll($('#select_all_types'), $('#group_type_list'), '.group-type-item');
  updateSelectAll($('#select_all_plazas'), $('#plaza_list'), '.plaza-item');
  updateSelectAll($('#select_all_archivos'), $('#archivo_list'), '.archivo-item');
  refreshArchivoCount();
}

function getFilters() {
  var d = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) d.plaza = plazas;
  var tipos = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (tipos.length) d.type = tipos;
  var archivos = $('.archivo-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (archivos.length) d.archivo = archivos;
  var agentes = selectedAgentIds();
  if (agentes.length) d.agente = agentes;
  if ($('#conexion_filter').val()) d.conexion = $('#conexion_filter').val();
  if ($('#estado_filter').val()) d.estado = $('#estado_filter').val();
  return d;
}

var currentPage = 0;
var pageSize = 10;
var totalRecords = 0;
var sortColumn = 'plaza';
var sortDirection = 'asc';
var chartInstances = {};

function loadData() {
  var filters = getFilters();
  filters.draw = 1;
  filters.start = currentPage * pageSize;
  filters.length = pageSize;
  filters.sort = sortColumn;
  filters.direction = sortDirection;

  $('#tableLoading').removeClass('d-none');
  $('#filesTableBody').empty();

  $.ajax({
    url: DATA_URL,
    type: 'GET',
    data: filters,
    success: function(json) {
      if (json.error) { console.error(json.error); return; }
      renderStats(json);
      renderTable(json);
    },
    error: function() {
      $('#tableLoading').addClass('d-none');
      $('#filesTableBody').html('<tr><td colspan="12" class="text-center py-4 text-muted">Error al cargar los datos</td></tr>');
    }
  });
}

function renderStats(json) {
  $('#total_records').text('Total: ' + (json.recordsTotal || 0) + ' registros');
  if (!json.stock_stats) return;
  var s = json.stock_stats;
  $('#statTotalFiles').text(s.total_archivos);
  $('#statMatchedFiles').text(s.total_matched);
  $('#statUnmatchedFiles').text(s.total_unmatched);
  $('#statEmptyFiles').text(s.total_vacios || 0);
  $('#statPercent').text(s.percent + '%');

  if (s.total_archivos === 0) {
    $('#chartsSection').addClass('d-none');
    return;
  }

  $('#chartsSection').removeClass('d-none');
  requestAnimationFrame(function() { initAllCharts(s); });
}

function initChart(id, config) {
  var canvas = document.getElementById(id);
  if (!canvas) return null;
  if (chartInstances[id]) { chartInstances[id].destroy(); }
  chartInstances[id] = new Chart(canvas, config);
  return chartInstances[id];
}

function initAllCharts(s) {
  var green = '#28a745', red = '#dc3545', gray = '#6c757d';
  var vacios = s.total_vacios || 0;

  initChart('pieFilesChart', {
    type: 'doughnut',
    data: {
      labels: vacios > 0 ? ['Actualizados', 'Desactualizados', 'Vacíos'] : ['Actualizados', 'Desactualizados'],
      datasets: [{
        data: vacios > 0 ? [s.total_matched, s.total_unmatched, vacios] : [s.total_matched, s.total_unmatched],
        backgroundColor: [green, red, gray], borderWidth: 0
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, resizeDelay: 100, cutout: '60%',
      plugins: {
        legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 12, padding: 8 } },
        tooltip: { callbacks: { label: function(ctx) {
          var pct = s.total_archivos > 0 ? ((ctx.parsed / s.total_archivos) * 100).toFixed(1) : 0;
          return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
        }}}
      }
    }
  });

  if (s.per_plaza && s.per_plaza.length > 0) {
    initChart('barPlazaChart', {
      type: 'bar',
      data: {
        labels: s.per_plaza.map(function(p) { return p.plaza; }),
        datasets: [
          { label: 'Actualizados', data: s.per_plaza.map(function(p) { return p.matched; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Vacíos', data: s.per_plaza.map(function(p) { return p.vacios || 0; }), backgroundColor: gray, borderRadius: 3 },
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
            var total = plaza.total;
            var pct = total > 0 ? ((ctx.parsed.y / total) * 100).toFixed(1) : 0;
            return ctx.dataset.label + ': ' + ctx.parsed.y + ' (' + pct + '%)';
          }}}
        }
      }
    });
  }
}

function esc(valor) {
  return $('<span>').text(valor === null || valor === undefined ? '' : valor).html();
}

function formatFecha(v) {
  if (!v) return '<span class="celda-vacia">-</span>';
  var value = String(v);
  if (value.length >= 19) return esc(value.substring(0, 19).replace('T', ' '));
  return esc(value);
}

function renderHash(celda) {
  if (!celda || !celda.hash_corto) return '<span class="celda-vacia">no se encuentra archivo en ubicacion</span>';
  var coincide = celda.hash === celda.hash_otro;
  var color = coincide === true ? '#28a745' : (coincide === false ? '#dc3545' : '#6c757d');
  return '<span class="hash-chip" style="background:' + color + ';" title="' + esc(celda.hash) + '">' + esc(celda.hash_corto) + '</span>';
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var $tbody = $('#filesTableBody');
  $('#tableLoading').addClass('d-none');
  $tbody.empty();

  if (data.length === 0) {
    $tbody.html('<tr><td colspan="12" class="text-center py-4 text-muted">No se encontraron archivos de stock</td></tr>');
    $('#paginationControls').addClass('d-none');
    return;
  }

  data.forEach(function(row) {
    var rbf = row.rbf || {};
    var reb = row.rebsamen || {};

    // El color de cada chip depende de si su hash coincide con el del otro disparador.
    var hayRbf = !!rbf.archivo;
    var hayRebsa = !!reb.archivo;

    rbf.hash_otro = reb.hash || null;
    reb.hash_otro = rbf.hash || null;

    var connectionDot = row.estado_equipo === 'online'
      ? '<span class="d-inline-block align-middle" style="width:10px;height:10px;border-radius:50%;background:#28a745;" title="Online"></span>'
      : '<span class="d-inline-block align-middle" style="width:10px;height:10px;border-radius:50%;background:#dc3545;" title="Offline"></span>';

    var statusBadge = row.estado === 'actualizado'
      ? '<span class="badge bg-success">Actualizado</span>'
      : (row.estado === 'vacio'
          ? '<span class="badge bg-secondary">Archivo vacío</span>'
          : '<span class="badge bg-danger">Desactualizado</span>');

    var motivo = '';
    if (row.estado === 'desactualizado') {
      if (!hayRbf || !hayRebsa) {
        motivo = '<div class="celda-vacia">' + (hayRbf ? 'falta en Rebsamen' : 'falta en RBF') + '</div>';
      } else {
        motivo = '<div class="celda-vacia">hash diferente</div>';
      }
    } else if (row.estado === 'vacio') {
      motivo = '<div class="celda-vacia">menos de 1 KB en ambos lados</div>';
    }

    // El peso llega formateado desde el servidor (separador de miles).
    var pesoRbf = rbf.peso_texto !== null && rbf.peso_texto !== undefined
      ? esc(rbf.peso_texto) : '<span class="celda-vacia">-</span>';
    var pesoReb = reb.peso_texto !== null && reb.peso_texto !== undefined
      ? esc(reb.peso_texto) : '<span class="celda-vacia">-</span>';

    $tbody.append(
      '<tr>' +
        '<td>' + esc(row.plaza) + '</td>' +
        '<td><strong>' + esc(row.nombre_instalacion) + '</strong></td>' +
        '<td class="text-center">' + connectionDot + '</td>' +
        '<td>' + (hayRbf ? '<strong>' + esc(rbf.archivo) + '</strong>' : '<span class="celda-vacia">-</span>') + '</td>' +
        '<td>' + renderHash(rbf) + '</td>' +
        '<td style="white-space:nowrap;">' + formatFecha(rbf.fecha_modificacion) + '</td>' +
        '<td class="text-center">' + pesoRbf + '</td>' +
        '<td>' + (hayRebsa ? '<strong>' + esc(reb.archivo) + '</strong>' : '<span class="celda-vacia">-</span>') + '</td>' +
        '<td>' + renderHash(reb) + '</td>' +
        '<td style="white-space:nowrap;">' + formatFecha(reb.fecha_modificacion) + '</td>' +
        '<td class="text-center">' + pesoReb + '</td>' +
        '<td class="text-center"><div>' + statusBadge + motivo + '</div></td>' +
      '</tr>'
    );
  });

  updatePagination();
}

function updatePagination() {
  var totalPages = Math.ceil(totalRecords / pageSize);
  if (totalPages <= 1) {
    $('#paginationControls').addClass('d-none');
    return;
  }
  $('#paginationControls').removeClass('d-none');
  var from = currentPage * pageSize + 1;
  var to = Math.min((currentPage + 1) * pageSize, totalRecords);
  $('#paginationInfo').text('Mostrando ' + from + ' a ' + to + ' de ' + totalRecords);

  var $ul = $('#paginationNumbers').empty();
  var startPage = Math.max(0, currentPage - 2);
  var endPage = Math.min(totalPages, startPage + 5);
  if (endPage - startPage < 5) startPage = Math.max(0, endPage - 5);

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
  refreshAllSelectAll();
  loadAgents();
  loadData();

  $('#btn_search').on('click', function() { currentPage = 0; loadData(); });
  $('#btn_refresh').on('click', function() { currentPage = 0; loadData(); });

  $('#paginationNumbers').on('click', '.page-btn', function(e) {
    e.preventDefault();
    currentPage = parseInt($(this).data('page'), 10);
    loadData();
  });

  $('#btn_reset_filters').on('click', function() {
    $('.group-type-checkbox').prop('checked', false);
    $('.plaza-checkbox').prop('checked', false);
    // Los archivos vuelven a su estado por defecto: los 14 marcados.
    $('.archivo-checkbox').prop('checked', true);
    $('.filter-search').val('');
    $('.group-type-item, .plaza-item, .archivo-item').show();
    $('.no-filter-results').addClass('d-none');
    $('#agent_search').val('');
    selectedAgents = {};
    $('#conexion_filter').val('');
    $('#estado_filter').val('');
    currentPage = 0;
    $('#select_all_types, #select_all_plazas, #select_all_archivos')
      .prop('checked', false).prop('indeterminate', false);
    refreshAllSelectAll();
    loadAgents();
    loadData();
  });

  // Buscadores de cada lista de checkboxes.
  $('.filter-search').on('input', function() {
    applyListFilter($(this));
    refreshAllSelectAll();
  });

  $('#select_all_types').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#group_type_list .group-type-item:visible .group-type-checkbox')
      .prop('checked', marcar);
    currentPage = 0;
    loadAgents();
    loadData();
  });
  $('#select_all_plazas').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#plaza_list .plaza-item:visible .plaza-checkbox').prop('checked', marcar);
    currentPage = 0;
    loadAgents();
    loadData();
  });
  $('#select_all_archivos').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#archivo_list .archivo-item:visible .archivo-checkbox').prop('checked', marcar);
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('.plaza-checkbox, .group-type-checkbox').on('change', function() {
    refreshAllSelectAll();
    currentPage = 0;
    loadAgents();
    loadData();
  });
  $('.archivo-checkbox').on('change', function() {
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#estado_filter, #conexion_filter').on('change', function() {
    currentPage = 0;
    loadData();
  });

  // Buscador de agentes con checkboxes: el texto solo acota la lista,
  // la seleccion se hace marcando los checkboxes.
  var agentSearchTimer = null;
  $('#agent_search').on('input', function() {
    clearTimeout(agentSearchTimer);
    agentSearchTimer = setTimeout(loadAgents, 300);
  });
  $('#agent_list').on('change', '.agent-checkbox', function() {
    selectedAgents[this.value] = this.checked;
    refreshAgentCount();
    currentPage = 0;
    loadData();
  });
  $('#agent_select_all').on('change', function() {
    var marcar = $(this).prop('checked');
    $('#agent_list .agent-checkbox').each(function() {
      this.checked = marcar;
      selectedAgents[this.value] = marcar;
    });
    refreshAgentCount();
    currentPage = 0;
    loadData();
  });
  $('#pageSizeSelect').on('change', function() {
    pageSize = parseInt($(this).val(), 10) || 10;
    currentPage = 0;
    loadData();
  });

  $('#btn_export').on('click', function() {
    var f = getFilters();
    var params = new URLSearchParams();
    (f.plaza || []).forEach(function(v) { params.append('plaza[]', v); });
    (f.type || []).forEach(function(v) { params.append('type[]', v); });
    (f.archivo || []).forEach(function(v) { params.append('archivo[]', v); });
    (f.agente || []).forEach(function(v) { params.append('agente[]', v); });
    if (f.conexion) params.append('conexion', f.conexion);
    if (f.estado) params.append('estado', f.estado);
    params.append('_t', Date.now());
    window.open(EXPORT_URL + '?' + params.toString(), '_blank');
  });

  $(document).on('click', '.btn-card-minimize', function() {
    var $btn = $(this);
    var $card = $btn.closest('.card');
    var $body = $card.children('.card-body');
    $body.slideToggle(200, function() {
      window.dispatchEvent(new Event('resize'));
    });
    $btn.find('i').toggleClass('fa-minus fa-plus');
  });

  $('#filesTable thead th[data-sort]').on('click', function() {
    var col = $(this).data('sort');
    if (sortColumn === col) {
      sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn = col;
      sortDirection = 'asc';
    }
    $('#filesTable thead th i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');
    $(this).find('i').removeClass('fa-sort').addClass(sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
    currentPage = 0;
    loadData();
  });
});
</script>
@endsection
