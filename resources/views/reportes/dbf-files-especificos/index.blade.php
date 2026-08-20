@extends('adminlte::page')
@section('title', 'Archivos DBF Especificos')

@section('content_header')
<h1>Reporte de Archivos de Precios </h1>
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
          <label class="form-label small mb-1">Categorias de Archivo</label>
          <select id="archivo_filter" class="form-control form-control-sm">
            <option value="">Todos los archivos</option>
            @foreach($archivoGrupos as $grupo => $files)
            <option value="{{ $grupo }}">{{ $grupo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2 d-none" id="archivo_individual_wrapper">
          <label class="form-label small mb-1">Archivo específico</label>
          <select id="archivo_individual" class="form-control form-control-sm">
            <option value="">Todos de esta categoría</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipos de Grupo</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_types" class="form-check-input">
              <label for="select_all_types" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @php
            $groupTypes = $groups->pluck('type')->filter()->unique()->sort()->values();
            @endphp
            @foreach($groupTypes as $type)
            <div class="form-check group-type-item">
              <input type="checkbox" name="type[]" value="{{ $type }}" id="type_{{ $type }}" class="form-check-input group-type-checkbox">
              <label for="type_{{ $type }}" class="form-check-label">{{ ucfirst($type) }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <div class="col-6 col-md-3">
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
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Buscar Computadora</label>
          <input type="text" id="computer_search" class="form-control form-control-sm" placeholder="Nombre o IP">
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
            <option value="cambio_manual">Cambio Manual</option>
            <option value="desactualizado">Desactualizado</option>
          </select>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="total_records" class="badge bg-info"></span>
            <span id="selected_records" class="badge bg-warning text-dark ms-1 d-none"></span>
          </div>
          <div class="d-flex gap-1 flex-wrap">
            <button id="btn_search" class="btn btn-success btn-sm">
              <i class="fas fa-search"></i> Buscar
            </button>
            <button id="btn_refresh" class="btn btn-primary btn-sm">
              <i class="fas fa-sync-alt"></i>
            </button>
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm">
              <i class="fas fa-undo"></i>
            </button>
            <button id="btn_export" class="btn btn-info btn-sm">
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
          <div class="card text-bg-warning h-100">
            <div class="card-body py-2 px-3 text-center">
              <span class="d-block fs-4 fw-bold" id="statCambioManual">0</span>
              <small class="text-dark">Cambio Manual</small>
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
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="d-flex align-items-center flex-wrap">
        <button class="btn btn-success btn-sm" id="btn_run_lista" style="margin-right:4px; margin-bottom:2px; margin-top:2px;"><i class="fas fa-play"></i> LISTA</button>
        <button class="btn btn-info btn-sm" id="btn_run_promocion" style="margin-right:4px; margin-bottom:2px; margin-top:2px;"><i class="fas fa-play"></i> PROMOCION</button>
        <button class="btn btn-warning btn-sm" id="btn_run_oferta" style="margin-right:4px; margin-bottom:2px; margin-top:2px;"><i class="fas fa-play"></i> OFERTA</button>
        <button class="btn btn-danger btn-sm" id="btn_run_combo" style="margin-right:4px; margin-bottom:2px; margin-top:2px;"><i class="fas fa-play"></i> COMBO</button>
        <button class="btn btn-secondary btn-sm" id="btn_bitacora" style="margin-bottom:2px; margin-top:2px;"><i class="fas fa-history"></i> Bitacora</button>
      </div>
      <div class="d-flex align-items-center flex-wrap gap-2">
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
              <th style="width:30px;"><input type="checkbox" id="select_all_computers"></th>
              <th style="cursor:pointer" data-sort="nombre_instalacion">Computadora <i class="fas fa-sort"></i></th>
              <th class="text-center" style="cursor:pointer" data-sort="status">Conexion <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="plaza">Plaza <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="archivo">Archivo <i class="fas fa-sort"></i></th>
              <th>Ruta</th>
              <th class="text-center">Tamano</th>
              <th>Ultima Modificacion</th>
              <th>MD5</th>
              <th>Ruta RBF</th>
              <th>Hash RBF</th>
              <th>Mod. RBF</th>
              <th style="cursor:pointer" data-sort="rbf_matched" class="text-center">Estado <i class="fas fa-sort"></i></th>
            </tr>
          </thead>
          <tbody id="filesTableBody">
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-play-circle"></i> Confirmar Ejecucion</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <p id="confirmModalMessage" class="mb-2"></p>
        <div id="confirmModalList" class="mb-0" style="max-height:300px; overflow-y:auto;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="confirmEjecutarBtn"><i class="fas fa-play"></i> Ejecutar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="bitacoraModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="fas fa-history"></i> Bitacora de Ejecuciones</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="bitacoraLoading" class="text-center py-4">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Cargando bitacora...</p>
        </div>
        <div id="bitacoraContent" class="d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="historialModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fas fa-eye"></i> Historial de Hash</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div id="historialLoading" class="text-center py-4">
          <i class="fas fa-spinner fa-spin fa-2x"></i>
          <p class="mt-2">Cargando historial (últimos 3 días)...</p>
        </div>
        <div id="historialContent" class="d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
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
#filesTable tbody td { font-size: 0.75rem; vertical-align: middle; }
.table-scroll { max-height: 55vh; overflow-y: auto; }
.table-scroll #filesTable thead th {
  position: sticky;
  top: 0;
  z-index: 2;
  background-color: #343a40;
  color: #fff;
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
.pct-bar { height: 6px; border-radius: 3px; background: #dee2e6; overflow: hidden; }
.pct-bar-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function formatAgentModifiedDate(modified) {
  if (!modified) return 'N/A';
  var value = String(modified).trim();
  if (/\b(?:AM|PM|am|pm)\b/.test(value)) return value;
  var patterns = [
    /^(\d{4}-\d{2}-\d{2})[ T](\d{1,2}:\d{2}(?::\d{2})?)(?:\.\d+)?(?:\s?(AM|PM|am|pm))?(?:[+-].*)?$/,
    /^(\d{2}\/\d{2}\/\d{4})[ T](\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(AM|PM|am|pm))?$/,
    /^(\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(AM|PM|am|pm))?$/,
  ];
  for (var i = 0; i < patterns.length; i++) {
    var match = value.match(patterns[i]);
    if (match) {
      var datePart = match[1] || '';
      var timePart = match[2] || '';
      var ampm = match[3] ? match[3].toUpperCase() : '';
      var parts = timePart.split(':').map(Number);
      var hour = parts[0] || 0;
      var minute = parts[1] || 0;
      var second = parts[2] || 0;
      var hour12 = hour % 12;
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
  if (plazas.length) d.plaza = plazas;
  var tipos = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (tipos.length) d.type = tipos;
  if ($('#archivo_individual').val()) d.archivo = $('#archivo_individual').val();
  else if ($('#archivo_filter').val()) d.archivo = $('#archivo_filter').val();
  if ($('#computer_search').val()) d.search = $('#computer_search').val();
  if ($('#conexion_filter').val()) d.conexion = $('#conexion_filter').val();
  if ($('#estado_filter').val()) d.estado = $('#estado_filter').val();
  return d;
}

function updateSelectedCount() {
  var count = selectedComputerIds.size;
  $('#selected_records').toggleClass('d-none', count === 0).text('Seleccionados: ' + count + ' equipos');
}

function updateHeaderCheckbox() {
  var $boxes = $('.computer-checkbox');
  var total = $boxes.length;
  var checked = $boxes.filter(':checked').length;
  $('#select_all_computers').prop('checked', total > 0 && checked === total).prop('indeterminate', checked > 0 && checked < total);
}

function clearSelection() {
  selectedComputerIds.clear();
  $('.computer-checkbox').prop('checked', false);
  $('#select_all_computers').prop('checked', false).prop('indeterminate', false);
  updateSelectedCount();
}

function addToSelection(id) {
  selectedComputerIds.add(String(id));
  $('.computer-checkbox[value="' + id + '"]').prop('checked', true);
  updateSelectedCount();
  updateHeaderCheckbox();
}

function removeFromSelection(id) {
  selectedComputerIds.delete(String(id));
  $('.computer-checkbox[value="' + id + '"]').prop('checked', false);
  updateSelectedCount();
  updateHeaderCheckbox();
}

var currentPage = 0;
var pageSize = 10;
var totalRecords = 0;
var sortColumn = 'nombre_instalacion';
var sortDirection = 'asc';
var selectedComputerIds = new Set();
var plazaGroupsMap = @json($plazaGroups ?? []);
var typePlazaMap = @json($typePlazaMap ?? []);
var typeGroupsMap = @json($groups->groupBy('type')->map(fn ($g) => $g->pluck('id')->values()->toArray())->filter()->toArray());
var archivoGruposMap = @json($archivoGrupos ?? []);

function updateGroupVisibility() {
  var visAll = $('.group-type-item').length;
  var visChecked = $('.group-type-item .group-type-checkbox:checked').length;
  $('#select_all_types').prop('checked', visAll > 0 && visAll === visChecked);
}

function updateArchivoIndividual() {
  var val = $('#archivo_filter').val().toUpperCase();
  var $wrap = $('#archivo_individual_wrapper');
  var $sel = $('#archivo_individual');
  $sel.empty().append('<option value="">Todos de esta categoría</option>');
  if (!val || !archivoGruposMap[val]) {
    $wrap.addClass('d-none');
    $sel.val('');
    return;
  }
  var files = archivoGruposMap[val];
  files.forEach(function(f) {
    $sel.append('<option value="' + f + '">' + f + '</option>');
  });
  $wrap.removeClass('d-none');
}

function loadData() {
  var filters = getFilters();
  filters.draw = 1;
  filters.start = currentPage * pageSize;
  filters.length = pageSize;
  filters.search = filters.search || '';
  filters.sort = sortColumn;
  filters.direction = sortDirection;

  $('#tableLoading').removeClass('d-none');
  $('#filesTableBody').empty();

  $.ajax({
    url: "{{ url('/reportes/dbf-files-especificos/data') }}",
    type: 'GET',
    data: filters,
    success: function(json) {
      if (json.error) { console.error(json.error); return; }
      renderStats(json);
      renderTable(json);
    },
    error: function() {
      $('#tableLoading').addClass('d-none');
    }
  });
}

function renderStats(json) {
  $('#total_records').text('Total: ' + (json.recordsTotal || 0) + ' registros');
  if (!json.rbf_stats) return;
  var s = json.rbf_stats;
  $('#statTotalFiles').text(s.total_files);
  $('#statMatchedFiles').text(s.total_matched);
  $('#statCambioManual').text(s.total_cambio_manual || 0);
  $('#statUnmatchedFiles').text(s.total_unmatched);
  $('#statPercent').text(s.percent + '%');

  if (s.total_files === 0) {
    $('#chartsSection').addClass('d-none');
    return;
  }

  $('#chartsSection').removeClass('d-none');
  requestAnimationFrame(function() { initAllCharts(s); });
}

var chartInstances = {};
function initChart(id, config) {
  var canvas = document.getElementById(id);
  if (!canvas) return null;
  if (chartInstances[id]) { chartInstances[id].destroy(); }
  chartInstances[id] = new Chart(canvas, config);
  return chartInstances[id];
}

function initAllCharts(s) {
  var green = '#28a745', orange = '#fd7e14', red = '#dc3545';

  initChart('pieFilesChart', {
    type: 'doughnut',
    data: {
      labels: ['Actualizados', 'Cambio Manual', 'Desactualizados'],
      datasets: [{ data: [s.total_matched, s.total_cambio_manual || 0, s.total_unmatched], backgroundColor: [green, orange, red], borderWidth: 0 }]
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

  if (s.per_plaza && s.per_plaza.length > 0) {
    initChart('barPlazaChart', {
      type: 'bar',
      data: {
        labels: s.per_plaza.map(function(p) { return p.plaza; }),
        datasets: [
          { label: 'Actualizados', data: s.per_plaza.map(function(p) { return p.matched; }), backgroundColor: green, borderRadius: 3 },
          { label: 'Cambio Manual', data: s.per_plaza.map(function(p) { return p.cambio_manual || 0; }), backgroundColor: orange, borderRadius: 3 },
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
            var total = plaza.matched + (plaza.cambio_manual || 0) + plaza.unmatched;
            var pct = total > 0 ? ((ctx.parsed.y / total) * 100).toFixed(1) : 0;
            return ctx.dataset.label + ': ' + ctx.parsed.y + ' (' + pct + '%)';
          }}}
        }
      }
    });
  }
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var $tbody = $('#filesTableBody');
  $('#tableLoading').addClass('d-none');
  $tbody.empty();

  if (data.length === 0) {
    $tbody.html('<tr><td colspan="13" class="text-center py-4 text-muted">No se encontraron archivos</td></tr>');
    $('#paginationControls').addClass('d-none');
    return;
  }

  data.forEach(function(row) {
    var statusBadge = row.rbf_status === 'actualizado'
      ? '<span class="badge bg-success">Actualizado</span>'
      : row.rbf_status === 'cambio_manual'
        ? '<span class="badge bg-warning text-dark">Cambio Manual</span>'
        : '<span class="badge bg-danger">Desactualizado</span>';
    var size = row.tamano !== null ? row.tamano + ' KB' : 'N/A';
    var modified = formatAgentModifiedDate(row.modificacion || '');
    var computerId = row.id || '';
    var connectionDot = row.status === 'online'
      ? '<span class="d-inline-block align-middle" style="width:10px;height:10px;border-radius:50%;background:#28a745;" title="Online"></span>'
      : '<span class="d-inline-block align-middle" style="width:10px;height:10px;border-radius:50%;background:#dc3545;" title="Offline"></span>';

    var isChecked = selectedComputerIds.has(String(computerId)) ? ' checked' : '';
    $tbody.append(
      '<tr>' +
        '<td><input type="checkbox" class="computer-checkbox" value="' + computerId + '" data-nombre="' + (row.nombre_instalacion || '') + '" data-plaza="' + (row.plaza || '') + '"' + isChecked + '></td>' +
        '<td><strong>' + (row.nombre_instalacion || 'N/A') + '</strong></td>' +
        '<td class="text-center">' + connectionDot + '</td>' +
        '<td>' + (row.plaza || 'N/A') + '</td>' +
        '<td><strong>' + (row.archivo || 'N/A') + '</strong></td>' +
        '<td style="word-break:break-all;max-width:250px;">' + (row.ruta || 'N/A') + '</td>' +
        '<td class="text-center">' + size + '</td>' +
        '<td style="white-space:nowrap;">' + modified + '</td>' +
        '<td><code style="font-size:0.65rem;">' + (row.md5 ? row.md5.slice(-5) : '') + '</code></td>' +
        '<td style="word-break:break-all;max-width:200px;">' + (row.rbf_path || '') + '</td>' +
        '<td><code style="font-size:0.65rem;">' + (row.rbf_hash || '') + '</code></td>' +
        '<td style="white-space:nowrap;">' + (row.rbf_last_modified || '<span class="text-muted">-</span>') + '</td>' +
        '<td class="text-center"><div class="d-inline-flex align-items-center gap-1">' + statusBadge + '<button type="button" class="btn btn-outline-secondary btn-xs btn-historial" data-computer-id="' + computerId + '" data-archivo="' + (row.archivo || '') + '" title="Ver historial de hash (3 días)" style="padding:0 4px;"><i class="fas fa-eye"></i></button></div></td>' +
      '</tr>'
    );
  });

  updateHeaderCheckbox();
  updateSelectedCount();
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
  updateGroupVisibility();
  loadData();

  $('#btn_search').on('click', function() { currentPage = 0; clearSelection(); loadData(); });
  $('#btn_refresh').on('click', function() { currentPage = 0; loadData(); });

  $('#paginationNumbers').on('click', '.page-btn', function(e) {
    e.preventDefault();
    currentPage = parseInt($(this).data('page'));
    loadData();
  });

  $('#btn_reset_filters').on('click', function() {
    $('.group-type-checkbox').prop('checked', false);
    $('#select_all_types').prop('checked', false);
    $('.plaza-checkbox').prop('checked', false);
    $('.plaza-checkbox').closest('.form-check').show();
    $('#select_all_plazas').prop('checked', false);
    updateGroupVisibility();
    $('#archivo_filter').val('');
    $('#archivo_individual').val('');
    $('#archivo_individual_wrapper').addClass('d-none');
    $('#computer_search').val('');
    $('#conexion_filter').val('');
    $('#estado_filter').val('');
    currentPage = 0;
    clearSelection();
    loadData();
  });

  $('#select_all_types').on('change', function() {
    $('.group-type-item:visible .group-type-checkbox').prop('checked', $(this).prop('checked'));
    updateGroupVisibility();
    currentPage = 0;
    clearSelection();
    loadData();
  });
  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox:visible').prop('checked', $(this).prop('checked'));
    updateGroupVisibility();
    currentPage = 0;
    clearSelection();
    loadData();
  });
  $('#select_all_computers').on('change', function() {
    if ($(this).prop('checked')) {
      var filters = getFilters();
      $.ajax({
        url: "{{ url('/reportes/dbf-files-especificos/ids') }}",
        type: 'GET',
        data: filters,
        success: function(json) {
          if (json.success && json.ids) {
            json.ids.forEach(function(id) { selectedComputerIds.add(String(id)); });
            $('.computer-checkbox').prop('checked', true);
            updateSelectedCount();
            updateHeaderCheckbox();
          }
        },
        error: function() {
          alert('Error al obtener los equipos para seleccionar.');
          $('#select_all_computers').prop('checked', false);
        }
      });
    } else {
      clearSelection();
    }
  });
  $('#filesTableBody').on('change', '.computer-checkbox', function() {
    var id = $(this).val();
    if ($(this).prop('checked')) {
      addToSelection(id);
    } else {
      removeFromSelection(id);
    }
  });
  $('.plaza-checkbox').on('change', function() {
    updateGroupVisibility();
    var visAll = $('.plaza-checkbox:visible').length;
    var visChecked = $('.plaza-checkbox:visible:checked').length;
    $('#select_all_plazas').prop('checked', visAll > 0 && visAll === visChecked);
    currentPage = 0;
    clearSelection();
    loadData();
  });
  $('.group-type-checkbox').on('change', function() {
    var visAll = $('.group-type-item:visible').length;
    var visChecked = $('.group-type-item:visible .group-type-checkbox:checked').length;
    $('#select_all_types').prop('checked', visAll > 0 && visAll === visChecked);
    updateGroupVisibility();
    currentPage = 0;
    clearSelection();
    loadData();
  });
  $('#archivo_filter').on('change', function() {
    updateArchivoIndividual();
    currentPage = 0;
    clearSelection();
    loadData();
  });
  $('#archivo_individual').on('change', function() { currentPage = 0; clearSelection(); loadData(); });
  $('#estado_filter').on('change', function() { currentPage = 0; clearSelection(); loadData(); });
  $('#conexion_filter').on('change', function() { currentPage = 0; clearSelection(); loadData(); });
  $('#pageSizeSelect').on('change', function() {
    pageSize = parseInt($(this).val(), 10) || 10;
    currentPage = 0;
    loadData();
  });
  $(document).on('click', '.btn-card-minimize', function() {
    var $card = $(this).closest('.card');
    var $body = $card.children('.card-body');
    var $icon = $(this).find('i');
    $body.slideToggle(200, function() {
      window.dispatchEvent(new Event('resize'));
    });
    $icon.toggleClass('fa-minus fa-plus');
  });
  $('#computer_search').on('keypress', function(e) {
    if (e.which === 13) { currentPage = 0; clearSelection(); loadData(); }
  });

  var csrfToken = '{{ csrf_token() }}';
  var currentTipo = '';
  var currentComputerIds = [];

  function getSelectedComputerIds() {
    return Array.from(selectedComputerIds);
  }

  function previewAndConfirm(tipo) {
    var ids = getSelectedComputerIds();
    if (ids.length === 0) {
      alert('Selecciona al menos un equipo de la tabla.');
      return;
    }

    currentTipo = tipo;
    currentComputerIds = ids;

    $.ajax({
      url: '{{ url('/reportes/dbf-files-especificos/ejecutar') }}/' + tipo,
      type: 'POST',
      data: {
        _token: csrfToken,
        computer_ids: ids,
        preview: true
      },
      success: function(json) {
        if (!json.success) return;
        if (json.count === 0) {
          alert('Ningun equipo seleccionado tiene el archivo ' + json.dbf + ' desactualizado.');
          return;
        }
        $('#confirmModalMessage').text('Se enviara el comando ' + json.bat + ' a ' + json.count + ' equipo(s):');
        var listHtml = '<div class="list-group list-group-flush">';
        json.computers.forEach(function(c) {
          listHtml += '<div class="list-group-item py-1 px-2"><small><strong>' + c.nombre_instalacion + '</strong> (' + c.plaza + ')</small></div>';
        });
        listHtml += '</div>';
        $('#confirmModalList').html(listHtml);
        $('#confirmModal').modal('show');
      }
    });
  }

  function doEjecutar() {
    $.ajax({
      url: '{{ url('/reportes/dbf-files-especificos/ejecutar') }}/' + currentTipo,
      type: 'POST',
      data: {
        _token: csrfToken,
        computer_ids: currentComputerIds,
        preview: false
      },
      success: function(json) {
        $('#confirmModal').modal('hide');
        if (json.success) {
          alert('Comando ' + json.bat + ' enviado a ' + json.count + ' equipo(s).');
        } else {
          alert('Error: ' + (json.message || 'Desconocido'));
        }
        loadData();
        clearSelection();
      },
      error: function(xhr) {
        $('#confirmModal').modal('hide');
        alert('Error al ejecutar: ' + (xhr.responseJSON?.message || xhr.statusText));
      }
    });
  }

  $('#btn_run_lista').on('click', function() { previewAndConfirm('lista'); });
  $('#btn_run_promocion').on('click', function() { previewAndConfirm('promocion'); });
  $('#btn_run_oferta').on('click', function() { previewAndConfirm('oferta'); });
  $('#btn_run_combo').on('click', function() { previewAndConfirm('combo'); });
  $('#confirmEjecutarBtn').on('click', function() { doEjecutar(); });

  $('#btn_bitacora').on('click', function() {
    $('#bitacoraContent').addClass('d-none').empty();
    $('#bitacoraLoading').removeClass('d-none');
    $('#bitacoraModal').modal('show');

    $.ajax({
      url: '{{ url('/reportes/dbf-files-especificos/bitacora') }}',
      type: 'GET',
      data: { limit: 100 },
      success: function(json) {
        $('#bitacoraLoading').addClass('d-none');
        if (!json.success || !json.groups.length) {
          $('#bitacoraContent').removeClass('d-none').html('<div class="text-center text-muted py-4">No hay ejecuciones registradas.</div>');
          return;
        }

        var html = '<div class="bitacora-list">';
        json.groups.forEach(function(group, idx) {
          var statusBadges = {
            completed: '<span class="badge bg-success">Completado</span>',
            failed: '<span class="badge bg-danger">Failed</span>',
            running: '<span class="badge bg-primary">Running</span>',
            pending: '<span class="badge bg-secondary">Pending</span>'
          };
          var countsHtml = '';
          ['completed','failed','running','pending'].forEach(function(s) {
            if (group.counts[s]) countsHtml += ' ' + (statusBadges[s] || s) + ' ' + group.counts[s];
          });

          html += '<div class="card mb-2">';
          html += '<div class="card-header py-1 px-2 bitacora-toggle" data-target="bitacoraBody' + idx + '" style="cursor:pointer;">';
          html += '<div class="d-flex align-items-center gap-2">';
          html += '<i class="fas fa-chevron-right toggle-icon"></i>';
          html += '<small class="fw-bold">' + group.created_at + '</small>';
          html += '<span class="badge bg-secondary">' + group.total + ' equipos</span>';
          html += countsHtml;
          html += '</div></div>';
          html += '<div id="bitacoraBody' + idx + '" class="card-body p-0" style="display:none;">';

          html += '<table class="table table-sm table-striped mb-0"><thead><tr>';
          html += '<th>Computadora</th><th>Plaza</th><th>Comando</th><th>Estado</th><th>Error</th>';
          html += '</tr></thead><tbody>';

          group.items.forEach(function(item) {
            var statusIcon = item.status === 'completed' ? '✅' : (item.status === 'failed' ? '❌' : (item.status === 'running' ? '🔄' : '⏳'));
            var errorText = '-';
            if (item.error) {
              var escaped = $('<span>').text(item.error).html().replace(/\n/g, '<br>');
              errorText = '<pre style="font-size:0.7rem;max-height:60px;overflow-y:auto;white-space:pre-wrap;word-break:break-all;background:#f8d7da;color:#721c24;padding:4px 6px;border-radius:4px;margin:0;">' + escaped + '</pre>';
            }
            html += '<tr>';
            html += '<td><strong>' + item.computer + '</strong></td>';
            html += '<td>' + item.plaza + '</td>';
            html += '<td>' + item.label + '</td>';
            html += '<td>' + statusIcon + ' ' + item.status + '</td>';
            html += '<td style="max-width:400px;">' + errorText + '</td>';
            html += '</tr>';
          });

          html += '</tbody></table>';
          html += '</div></div>';
        });
        html += '</div>';
        $('#bitacoraContent').html(html).removeClass('d-none');

        $('#bitacoraContent').off('click', '.bitacora-toggle').on('click', '.bitacora-toggle', function() {
          var targetId = $(this).data('target');
          var $body = $('#' + targetId);
          var $icon = $(this).find('.toggle-icon');
          $body.slideToggle(200);
          $icon.toggleClass('fa-chevron-right fa-chevron-down');
        });
      },
      error: function() {
        $('#bitacoraLoading').addClass('d-none');
        $('#bitacoraContent').removeClass('d-none').html('<div class="alert alert-danger mb-0">Error al cargar la bitacora.</div>');
      }
    });
  });

  $('#btn_export').on('click', function() {
    var plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
    var tiposSeleccionados = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
    var archivo = $('#archivo_individual').val() || $('#archivo_filter').val();
    var params = new URLSearchParams();
    plazasSeleccionadas.forEach(function(val) { params.append('plaza[]', val); });
    tiposSeleccionados.forEach(function(val) { params.append('type[]', val); });
    if (archivo) params.append('archivo', archivo);
    params.append('_t', Date.now());
    window.open("{{ url('/reportes/dbf-files-especificos/export') }}?" + params.toString(), '_blank');
  });

  $(document).on('click', '.btn-historial', function() {
    var computerId = $(this).data('computer-id');
    var archivo = $(this).data('archivo');
    $('#historialContent').addClass('d-none').empty();
    $('#historialLoading').removeClass('d-none');
    $('#historialModal').modal('show');

    $.ajax({
      url: '{{ url('/reportes/dbf-files-especificos/historial') }}',
      type: 'GET',
      data: { computer_id: computerId, archivo: archivo },
      success: function(json) {
        $('#historialLoading').addClass('d-none');
        if (!json.success || !json.historial.length) {
          $('#historialContent').removeClass('d-none').html('<div class="alert alert-info mb-0">No hay historial de hash para los últimos 3 días.</div>');
          return;
        }

        var html = '<table class="table table-sm table-striped mb-0"><thead><tr>';
        html += '<th>Hash</th><th>Última modificación</th>';
        html += '</tr></thead><tbody>';

        json.historial.forEach(function(item) {
          html += '<tr>';
          html += '<td><code>' + item.hash + '</code></td>';
          html += '<td>' + formatAgentModifiedDate(item.modified || '') + '</td>';
          html += '</tr>';
        });

        html += '</tbody></table>';
        $('#historialContent').html(html).removeClass('d-none');
      },
      error: function() {
        $('#historialLoading').addClass('d-none');
        $('#historialContent').removeClass('d-none').html('<div class="alert alert-danger mb-0">Error al cargar el historial.</div>');
      }
    });
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
