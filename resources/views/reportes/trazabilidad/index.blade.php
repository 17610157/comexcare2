@extends('adminlte::page')
@section('title', 'Reporte de Trazabilidad')

@section('content_header')
<h1>Reporte de Trazabilidad</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card bg-light mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
      <button type="button" class="btn-card-minimize" title="Minimizar"><i class="fas fa-minus"></i></button>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-6 col-md-3">
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
          <label class="form-label small mb-1">Buscar Tienda / Clave</label>
          <input type="text" id="computer_search" class="form-control form-control-sm" placeholder="Nombre o clave...">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Estado del Agente</label>
          <select id="estado_filter" class="form-control form-control-sm">
            <option value="">Todos</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="updating">Actualizando</option>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label small mb-1">Archivo (exportación)</label>
          <select id="archivo_filter" class="form-control form-control-sm" multiple size="4">
            <option value="" disabled>Cargando archivos...</option>
          </select>
          <small class="text-muted">Ctrl+clic para varios</small>
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end">
          <div class="d-flex gap-1 flex-wrap">
            <button id="btn_search" class="btn btn-success btn-sm"><i class="fas fa-search"></i> Buscar</button>
            <button id="btn_refresh" class="btn btn-primary btn-sm" title="Actualizar"><i class="fas fa-sync-alt"></i></button>
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm" title="Limpiar"><i class="fas fa-undo"></i></button>
            <button id="btn_export" class="btn btn-outline-success btn-sm" title="Descargar CSV con la selección actual">
              <i class="fas fa-file-csv"></i> Descargar CSV
            </button>
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
      <button type="button" class="btn-card-minimize" title="Minimizar"><i class="fas fa-minus"></i></button>
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
              <th>Tienda</th>
              <th>Clave</th>
              <th>Plaza</th>
              <th>Grupo</th>
              <th class="text-center">Estado del Agente</th>
              <th>Archivo</th>
              <th class="text-center" id="thHashColumns" colspan="1">Hash por Disparador</th>
            </tr>
          </thead>
          <tbody id="filesTableBody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-folder-open"></i> Detalle del archivo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-3">
          <div class="col-md-3"><strong>Tienda:</strong></div><div class="col-md-9" id="view-tienda"></div>
          <div class="col-md-3"><strong>Clave:</strong></div><div class="col-md-9" id="view-clave"></div>
          <div class="col-md-3"><strong>Plaza:</strong></div><div class="col-md-9" id="view-plaza"></div>
          <div class="col-md-3"><strong>Grupo:</strong></div><div class="col-md-9" id="view-grupo"></div>
          <div class="col-md-3"><strong>Archivo:</strong></div><div class="col-md-9"><code id="view-archivo"></code></div>
          <div class="col-md-3"><strong>Fecha de modificación de archivo:</strong></div><div class="col-md-9" id="view-fecha"></div>
        </div>
        <h6 class="text-muted font-weight-bold">Rutas desglosadas</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0" id="viewRutasTable">
            <thead class="table-light">
              <tr>
                <th>Disparador</th>
                <th>Ruta</th>
                <th>Hash</th>
                <th>Fecha modificación de archivo</th>
                <th>Fecha consulta API</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('css')
<style>
.card-header { border-bottom: 2px solid #dee2e6; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.form-label { font-size: 0.75rem; }
.form-control-sm { font-size: 0.75rem; }
#filesTable { font-size: 0.75rem; }
#filesTable thead th { white-space: nowrap; font-size: 0.72rem; }
#filesTable tbody td { font-size: 0.75rem; vertical-align: middle; }
.table-scroll { max-height: 60vh; overflow-y: auto; }
.table-scroll #filesTable thead th { position: sticky; top: 0; z-index: 2; background-color: #343a40; color: #fff; }
#paginationNumbers .page-link { padding: 0.15rem 0.45rem; }
.btn-card-minimize { background: transparent; border: none; color: inherit; opacity: .65; padding: 0.15rem 0.45rem; font-size: 0.85rem; line-height: 1.4; cursor: pointer; }
.btn-card-minimize:hover { opacity: 1; }
.btn-card-minimize:focus { outline: none; box-shadow: none; }
.hash-cell { font-family: monospace; }
.hash-ok { color: #198754; font-weight: 600; }
.hash-ko { color: #dc3545; font-weight: 600; }
.hash-chip { display: inline-block; font-family: monospace; font-size: 0.68rem; font-weight: 600; color: #fff; border-radius: 3px; padding: 0 4px; margin: 1px 2px 1px 0; white-space: nowrap; }
.ruta-path { display: block; font-family: monospace; font-size: 0.62rem; color: #6c757d; word-break: break-all; }
.celda-vacia { font-size: 0.62rem; color: #6c757d; font-style: italic; }
.ruta-desglose { font-family: monospace; font-size: 0.72rem; word-break: break-all; }
.ruta-seg { color: #ffffff; display: inline-block; }
.ruta-sep { color: #ffffff; padding: 0 1px; }
.btn-view { padding: 0.1rem 0.35rem; font-size: 0.65rem; line-height: 1.2; }
#viewRutasTable td.hist-fila, tr.hist-fila td { background-color: #343a40; color: #fff; }
tr.hist-fila td { vertical-align: middle; }
tr.hist-fila .text-muted { color: #adb5bd !important; }
tr.hist-fila code, tr.hist-fila small { color: #fff; }
</style>
@endsection

@section('js')
<script>
function getFilters() {
  var d = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  var tipos = $('.group-type-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) d.plaza = plazas;
  if (tipos.length) d.type = tipos;
  if ($('#computer_search').val()) d.search = $('#computer_search').val();
  if ($('#estado_filter').val()) d.estado = $('#estado_filter').val();
  var archivos = $('#archivo_filter').val();
  if (archivos && archivos.length) d.archivo = archivos;
  return d;
}

function getExportUrl() {
  var f = getFilters();
  var params = new URLSearchParams();
  if (f.plaza) f.plaza.forEach(function(v) { params.append('plaza', v); });
  if (f.type) f.type.forEach(function(v) { params.append('type', v); });
  if (f.search) params.append('search', f.search);
  if (f.estado) params.append('estado', f.estado);
  if (f.archivo) f.archivo.forEach(function(v) { params.append('archivo', v); });
  return "{{ url('/reportes/trazabilidad/export') }}?" + params.toString();
}

function loadArchivos(cb) {
  $.ajax({
    url: "{{ url('/reportes/trazabilidad/archivos-disponibles') }}",
    type: 'GET',
    success: function(json) {
      var opts = (json.archivos || []).map(function(a) {
        return '<option value="' + a + '">' + a.toUpperCase() + '</option>';
      }).join('');
      $('#archivo_filter').html(opts);
      if (typeof cb === 'function') cb();
    },
    error: function() {
      $('#archivo_filter').html('<option value="">Sin archivos</option>');
      if (typeof cb === 'function') cb();
    }
  });
}

var currentPage = 0;
var pageSize = 10;
var totalRecords = 0;
var dataStore = [];

function loadData() {
  var filters = getFilters();
  filters.draw = 1;
  filters.start = currentPage * pageSize;
  filters.length = pageSize;

  $('#tableLoading').removeClass('d-none');
  $('#filesTableBody').empty();

  $.ajax({
    url: "{{ url('/reportes/trazabilidad/data') }}",
    type: 'GET',
    data: filters,
    success: function(json) {
      if (json.error) { console.error(json.error); return; }
      renderTable(json);
    },
    error: function() { $('#tableLoading').addClass('d-none'); }
  });
}

function estadoBadge(estado) {
  var e = String(estado || '').toLowerCase();
  if (e === 'online') return '<span class="badge bg-success">Online</span>';
  if (e === 'offline') return '<span class="badge bg-danger">Offline</span>';
  if (e === 'updating') return '<span class="badge bg-warning text-dark">Actualizando</span>';
  return '<span class="badge bg-secondary">' + (estado || 'N/A') + '</span>';
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var columnas = json.columnas || [];
  var $tbody = $('#filesTableBody');
  $('#tableLoading').addClass('d-none');
  $tbody.empty();

  renderHashHeader(columnas);

  if (data.length === 0) {
    var noCols = 6 + columnas.length;
    $tbody.html('<tr><td colspan="' + noCols + '" class="text-center py-4 text-muted">No se encontraron tiendas</td></tr>');
    $('#paginationControls').addClass('d-none');
    return;
  }

  data.forEach(function(row) {
    row.__idx = dataStore.length;
    dataStore.push(row);
    var rowColors = computeRowColors(row, columnas);
    var html = '<tr>';
    html += '<td><strong>' + (row.nombre_instalacion || 'N/A') + '</strong></td>';
    html += '<td><code>' + (row.short_key || 'N/A') + '</code></td>';
    html += '<td>' + (row.plaza || 'N/A') + '</td>';
    html += '<td>' + (row.grupo || 'N/A') + '</td>';
    html += '<td class="text-center">' + estadoBadge(row.estado) + '</td>';
    html += '<td><div class="d-flex align-items-center gap-1"><code>' + (row.archivo || 'N/A') + '</code>' +
            '<button type="button" class="btn btn-info btn-sm btn-view ml-1" data-view-idx="' + row.__idx + '" title="Ver ruta"><i class="fas fa-eye"></i></button></div></td>';

    columnas.forEach(function(disp) {
      var cell = (row.hashes || {})[disp] || null;
      html += '<td>' + renderHashCell(cell, rowColors) + '</td>';
    });

    html += '</tr>';
    $tbody.append(html);
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

var HASH_PALETTE = ['#198754', '#0d6efd', '#6f42c1', '#fd7e14', '#20c997', '#d63384'];

function computeRowColors(row, columnas) {
  var counts = {};
  columnas.forEach(function(disp) {
    var cell = (row.hashes || {})[disp] || null;
    if (!cell || !cell.hash) return;
    counts[cell.hash] = (counts[cell.hash] || 0) + 1;
  });

  var colors = {};
  var groups = [];
  Object.keys(counts).forEach(function(h) {
    if (counts[h] <= 1) { colors[h] = '#dc3545'; return; }
    groups.push({ hash: h, count: counts[h] });
  });

  groups.sort(function(a, b) { return b.count - a.count; });

  groups.forEach(function(g, i) {
    if (i === 0) colors[g.hash] = '#198754';
    else if (i === 1) colors[g.hash] = '#0d6efd';
    else colors[g.hash] = '#dc3545';
  });

  return colors;
}

function renderHashCell(cell, rowColors) {
  if (!cell || !cell.hash) return '<span class="celda-vacia">no se encuentra archivo en ubicacion</span>';
  var color = rowColors[cell.hash] || '#dc3545';
  var title = cell.path ? ' title="' + cell.path + '"' : '';
  return '<span class="hash-chip" style="background:' + color + ';"' + title + '>' + cell.hash + '</span>';
}

function renderHashHeader(columnas) {
  var $headTr = $('#filesTable thead tr');

  $headTr.find('th.dyn-hash').remove();
  var $ph = $headTr.find('#thHashColumns');
  if ($ph.length) $ph.remove();

  if (!columnas || columnas.length === 0) {
    $headTr.append('<th class="text-center dyn-hash" id="thHashColumns">Hash por Disparador</th>');
    return;
  }

  columnas.forEach(function(disp) {
    var label = disp === 'cortefin/pvsi' ? 'CORTEFIN/PVSI' : disp.toUpperCase();
    $headTr.append('<th class="text-center dyn-hash">' + label + '</th>');
  });
}

function debounce(fn, ms) {
  var t;
  return function() {
    clearTimeout(t);
    t = setTimeout(fn, ms);
  };
}

$(function() {
  function urlParam(name) {
    var m = new URLSearchParams(window.location.search).getAll(name);
    return m;
  }

  function restoreFiltersFromUrl() {
    var plazas = urlParam('plaza');
    if (plazas.length) $('.plaza-checkbox').prop('checked', false).filter(function() { return plazas.indexOf($(this).val()) > -1; }).prop('checked', true);
    var tipos = urlParam('type');
    if (tipos.length) $('.group-type-checkbox').prop('checked', false).filter(function() { return tipos.indexOf($(this).val()) > -1; }).prop('checked', true);
    var s = urlParam('search')[0];
    if (s && $('#computer_search').length) $('#computer_search').val(s);
    var e = urlParam('estado')[0];
    if (e && $('#estado_filter').length) $('#estado_filter').val(e);
    var a = urlParam('archivo');
    loadArchivos(function() {
      if (a.length && $('#archivo_filter').length) {
        var vals = a;
        $('#archivo_filter option').each(function() {
          if (vals.indexOf($(this).val()) > -1) $(this).prop('selected', true);
        });
      }
    });
  }

  function syncFiltersToUrl() {
    var params = getFilters();
    var qs = new URLSearchParams();
    (params.plaza || []).forEach(function(v) { qs.append('plaza', v); });
    (params.type || []).forEach(function(v) { qs.append('type', v); });
    if (params.search) qs.append('search', params.search);
    if (params.estado) qs.append('estado', params.estado);
    (params.archivo || []).forEach(function(v) { qs.append('archivo', v); });
    var url = window.location.pathname + (qs.toString() ? '?' + qs.toString() : '');
    window.history.replaceState({}, '', url);
  }

  restoreFiltersFromUrl();

  loadData();

  loadArchivos();

  var debouncedLoad = debounce(function() { currentPage = 0; syncFiltersToUrl(); loadData(); }, 250);

  $('#btn_search').on('click', function() { currentPage = 0; syncFiltersToUrl(); loadData(); });
  $('#btn_refresh').on('click', function() { currentPage = 0; loadData(); });
  $('#estado_filter').on('change', function() { currentPage = 0; syncFiltersToUrl(); loadData(); });
  $('#archivo_filter').on('change', function() { currentPage = 0; syncFiltersToUrl(); loadData(); });
  $('#computer_search').on('input', debouncedLoad);

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
    $('#computer_search').val('');
    $('#estado_filter').val('');
    $('#archivo_filter').val([]);
    var url = window.location.pathname;
    window.history.replaceState({}, '', url);
    currentPage = 0;
    loadData();
  });

  $('#btn_export').on('click', function() {
    window.location.href = getExportUrl();
  });

  $('#select_all_types').on('change', function() {
    $('.group-type-item:visible .group-type-checkbox').prop('checked', $(this).prop('checked'));
    currentPage = 0;
    syncFiltersToUrl();
    loadData();
  });
  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox:visible').prop('checked', $(this).prop('checked'));
    currentPage = 0;
    syncFiltersToUrl();
    loadData();
  });
  $('.group-type-checkbox, .plaza-checkbox').on('change', function() {
    currentPage = 0;
    syncFiltersToUrl();
    loadData();
  });

  $('#pageSizeSelect').on('change', function() {
    pageSize = parseInt($(this).val(), 10) || 10;
    currentPage = 0;
    loadData();
  });

  function formatFecha(v) {
    if (!v) return '—';
    if (typeof v === 'string' && v.length >= 19) return v.substring(0, 19).replace('T', ' ');
    return v;
  }

  function desglosarRuta(ruta) {
    if (!ruta) return '';
    var partes = String(ruta).split(/[\\/]+/).filter(function(p) { return p !== ''; });
    if (partes.length < 2) return ruta;
    var html = '<div class="ruta-desglose">';
    partes.forEach(function(p, i) {
      html += '<span class="ruta-seg">' + p + '</span>';
      if (i < partes.length - 1) html += '<span class="ruta-sep">' + (String(ruta).indexOf('\\') > -1 ? '\\' : '/') + '</span>';
    });
    html += '</div>';
    return html;
  }

  $('#filesTableBody').on('click', '.btn-view', function() {
    var row = dataStore[parseInt($(this).data('view-idx'), 10)];
    if (!row) return;

    function val(v) { return (v === null || v === undefined || v === '') ? '—' : v; }

    $('#view-tienda').text(val(row.nombre_instalacion));
    $('#view-clave').text(val(row.short_key));
    $('#view-plaza').text(val(row.plaza));
    $('#view-grupo').text(val(row.grupo));
    $('#view-archivo').text(val(row.archivo));
    $('#view-fecha').text(formatFecha(row.fecha_modificacion));

    var $tbody = $('#viewRutasTable tbody');
    $tbody.empty();
    var rutas = row.rutas || [];
    if (rutas.length === 0) {
      $tbody.html('<tr><td colspan="5" class="text-center text-muted">No hay rutas para este archivo</td></tr>');
    } else {
      rutas.forEach(function(r, i) {
        var dispLabel = r.disparador === 'cortefin/pvsi' ? 'CORTEFIN/PVSI' : String(r.disparador || '').toUpperCase();
        var hist = r.historial || [];

        $tbody.append(
          '<tr>' +
            '<td class="font-weight-bold">' + dispLabel + '</td>' +
            '<td>' + desglosarRuta(r.ruta) + '</td>' +
            '<td><code>' + val(r.hash) + '</code></td>' +
            '<td>' + formatFecha(r.fecha_modificacion) + '</td>' +
            '<td>' + formatFecha(r.fecha_consulta_api) + '</td>' +
          '</tr>'
        );

        hist.forEach(function(h) {
          var hLabel = h.disparador === 'cortefin/pvsi' ? 'CORTEFIN/PVSI' : String(h.disparador || '').toUpperCase();
          $tbody.append(
            '<tr class="hist-fila">' +
              '<td><span class="small">' + (hLabel === dispLabel ? '' : hLabel) + '</span></td>' +
              '<td><span class="small">↳ ' + desglosarRuta(r.ruta) + '</span></td>' +
              '<td><span class="hash-chip" style="background:#495057;">' + val(h.hash) + '</span></td>' +
              '<td><span class="small">' + formatFecha(h.fecha_modificacion) + '</span></td>' +
              '<td><span class="small">' + formatFecha(h.fecha_consulta_api) + '</span></td>' +
            '</tr>'
          );
        });
      });
    }

    $('#viewModal').modal('show');
  });

  $(document).on('click', '.btn-card-minimize', function() {
    var $btn = $(this);
    var $card = $btn.closest('.card');
    var $body = $card.children('.card-body');
    if ($body.length) {
      $body.slideToggle(function() { $btn.find('i').toggleClass('fa-minus fa-plus'); });
    }
  });
});
</script>
@endsection
