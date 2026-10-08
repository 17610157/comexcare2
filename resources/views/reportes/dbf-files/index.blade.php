@extends('adminlte::page')
@section('title', 'Archivos DBF - Computadoras')

@section('content_header')
<h1>Dashboard de Archivos</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card bg-light mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-filter"></i> Filtros
      </h5>
      <button type="button" id="btn_toggle_filters" class="btn-card-minimize" title="Minimizar filtros">
        <i class="fas fa-minus"></i>
      </button>
    </div>
    <div class="card-body" id="filtersBody">
      @php
      $groupTypes = $groups->pluck('type')->filter()->unique()->sort()->values();
      $fileCategories = [
        ['value' => 'dbf', 'label' => 'Solo .DBF'],
        ['value' => 'qbck', 'label' => 'Solo QBCK'],
        ['value' => 'exe', 'label' => 'Solo .EXE'],
        ['value' => 'bat', 'label' => 'Solo .BAT'],
      ];
      $conexiones = ['online' => 'Online', 'offline' => 'Offline'];
      $estados = ['actualizado' => 'Actualizado', 'desactualizado' => 'Desactualizado'];
      @endphp
      <div class="row g-2">
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Plazas</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar plaza..." aria-label="Buscar plaza"
                 data-target="#plaza_list" data-items=".plaza-item" autocomplete="off">
          <div class="border rounded p-2 filter-list" id="plaza_list" title="Clic en la fila para marcar o desmarcar">
            <div class="form-check filter-row">
              <input type="checkbox" id="select_all_plazas" class="form-check-input">
              <label for="select_all_plazas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($plazas as $plaza)
            <div class="form-check filter-row plaza-item" data-label="{{ strtolower($plaza) }}">
              <input type="checkbox" name="plaza[]" value="{{ $plaza }}" id="plaza_{{ $plaza }}" class="form-check-input plaza-checkbox">
              <label for="plaza_{{ $plaza }}" class="form-check-label">{{ $plaza }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipos de Grupo</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar tipo..." aria-label="Buscar tipo de grupo"
                 data-target="#group_type_list" data-items=".group-type-item" autocomplete="off">
          <div class="border rounded p-2 filter-list" id="group_type_list" title="Clic en la fila para marcar o desmarcar">
            <div class="form-check filter-row">
              <input type="checkbox" id="select_all_types" class="form-check-input">
              <label for="select_all_types" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @foreach($groupTypes as $type)
            <div class="form-check filter-row group-type-item" data-label="{{ strtolower($type) }}">
              <input type="checkbox" name="type[]" value="{{ $type }}" id="type_{{ $type }}" class="form-check-input group-type-checkbox">
              <label for="type_{{ $type }}" class="form-check-label">{{ ucfirst($type) }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Categorias Archivos</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar categoria..." aria-label="Buscar categoria de archivo"
                 data-target="#file_category_list" data-items=".file-category-item" autocomplete="off">
          <div class="border rounded p-2 filter-list" id="file_category_list" title="Clic en la fila para marcar o desmarcar">
            <div class="form-check filter-row">
              <input type="checkbox" id="select_all_categories" class="form-check-input">
              <label for="select_all_categories" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($fileCategories as $categoria)
            <div class="form-check filter-row file-category-item" data-label="{{ $categoria['value'] }} {{ strtolower($categoria['label']) }}">
              <input type="checkbox" name="file_category[]" value="{{ $categoria['value'] }}" id="category_{{ $categoria['value'] }}" class="form-check-input file-category-checkbox">
              <label for="category_{{ $categoria['value'] }}" class="form-check-label">{{ $categoria['label'] }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Archivo</label>
          <input type="text" id="archivo_search" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar archivo..." aria-label="Buscar archivo"
                 data-target="#archivo_list" data-items=".archivo-item" autocomplete="off">
          <div class="border rounded p-2 filter-list" id="archivo_list" title="Clic en la fila para marcar o desmarcar">
            <div class="form-check filter-row">
              <input type="checkbox" id="select_all_archivos" class="form-check-input">
              <label for="select_all_archivos" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @foreach($archivos as $archivo)
            <div class="form-check filter-row archivo-item" data-label="{{ strtolower($archivo) }}" data-cats="{{ implode(',', $archivoCategorias[$archivo] ?? []) }}">
              <input type="checkbox" name="archivo[]" value="{{ $archivo }}" id="archivo_{{ $loop->index }}" class="form-check-input archivo-checkbox">
              <label for="archivo_{{ $loop->index }}" class="form-check-label">{{ $archivo }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Buscar Computadora</label>
          <input type="text" id="computer_search" class="form-control form-control-sm" placeholder="Nombre o IP" autocomplete="off">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Conexión</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar conexion..." aria-label="Buscar conexion"
                 data-target="#conexion_list" data-items=".conexion-item" autocomplete="off">
          <div class="border rounded p-2 filter-list" id="conexion_list" title="Clic en la fila para marcar o desmarcar">
            <div class="form-check filter-row">
              <input type="checkbox" id="select_all_conexion" class="form-check-input">
              <label for="select_all_conexion" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($conexiones as $value => $label)
            <div class="form-check filter-row conexion-item" data-label="{{ $value }} {{ strtolower($label) }}">
              <input type="checkbox" name="conexion[]" value="{{ $value }}" id="conexion_{{ $value }}" class="form-check-input conexion-checkbox">
              <label for="conexion_{{ $value }}" class="form-check-label">{{ $label }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Estado Actualizacion</label>
          <input type="text" class="form-control form-control-sm mb-1 filter-search"
                 placeholder="Buscar estado..." aria-label="Buscar estado de actualizacion"
                 data-target="#estado_list" data-items=".estado-item" autocomplete="off">
          <div class="border rounded p-2 filter-list" id="estado_list" title="Clic en la fila para marcar o desmarcar">
            <div class="form-check filter-row">
              <input type="checkbox" id="select_all_estado" class="form-check-input">
              <label for="select_all_estado" class="form-check-label font-weight-bold"><strong>Todos</strong></label>
            </div>
            @foreach($estados as $value => $label)
            <div class="form-check filter-row estado-item" data-label="{{ $value }} {{ strtolower($label) }}">
              <input type="checkbox" name="estado[]" value="{{ $value }}" id="estado_{{ $value }}" class="form-check-input estado-checkbox">
              <label for="estado_{{ $value }}" class="form-check-label">{{ $label }}</label>
            </div>
            @endforeach
            <div class="form-check d-none no-filter-results">
              <span class="text-muted small">Sin coincidencias</span>
            </div>
          </div>
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

  <div class="card" id="computersCard">
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
    </div>
    <div class="card-body p-0">
      <div id="computersLoading" class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Cargando computadoras...</p>
      </div>
      <div class="table-responsive table-scroll">
        <table class="table table-sm table-hover table-striped mb-0" id="computersTable">
          <thead class="table-dark">
            <tr>
              <th style="cursor:pointer" data-sort="nombre_instalacion">Computadora <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="plaza">Plaza <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="status">Estado <i class="fas fa-sort"></i></th>
              <th>Categoria</th>
              <th style="cursor:pointer" data-sort="archivo">Nombre <i class="fas fa-sort"></i></th>
              <th>Ruta</th>
              <th>Tamano</th>
              <th>Modificacion</th>
              <th>MD5</th>
              <th>Ruta RBF</th>
              <th>Hash RBF</th>
              <th>Mod. RBF</th>
              <th>Estado Archivo</th>
            </tr>
          </thead>
          <tbody id="computersTableBody">
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

#computersTable { font-size: 0.8rem; }
#computersTable thead th { white-space: nowrap; font-size: 0.75rem; }
.table-scroll { max-height: 55vh; overflow-y: auto; }
.table-scroll #computersTable thead th {
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
.filter-list { max-height: 110px; overflow-y: auto; }
.filter-row { cursor: pointer; padding-left: 1.4em; }
.filter-row:hover { background-color: #eef2f7; }
.filter-row label { cursor: pointer; }
.filter-row input[type="checkbox"] { cursor: pointer; margin-top: .2em; }
#computersTable tbody tr.file-row { background-color: #f8f9fa !important; }
#computersTable tbody tr.file-row td { font-size: 0.7rem; padding: 0.2rem 0.5rem; }
.file-table { width: 100%; font-size: 0.7rem; }
.file-table th { background: #e9ecef; font-weight: 600; white-space: nowrap; }
.file-table td { padding: 0.2rem 0.4rem; }
@media (max-width: 768px) {
  .btn-sm { padding: 0.2rem 0.4rem; font-size: 0.7rem; }
}
</style>
@endsection

@section('js')

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

function checkedValues(selector) {
  return $(selector + ':checked').map(function() { return $(this).val(); }).get();
}

function getFilters() {
  var d = {};
  var plazas = checkedValues('.plaza-checkbox');
  if (plazas.length) d.plaza = plazas;
  var tipos = checkedValues('.group-type-checkbox');
  if (tipos.length) d.type = tipos;
  var categorias = checkedValues('.file-category-checkbox');
  if (categorias.length) d.file_category = categorias;
  var archivos = checkedValues('.archivo-checkbox');
  if (archivos.length) d.archivo = archivos;
  var conexiones = checkedValues('.conexion-checkbox');
  if (conexiones.length) d.conexion = conexiones;
  var estados = checkedValues('.estado-checkbox');
  if (estados.length) d.estado = estados;
  if ($('#computer_search').val()) d.search = $('#computer_search').val();
  return d;
}

var plazaGroupsMap = @json($plazaGroups ?? []);
var typePlazaMap = @json($typePlazaMap ?? []);
var typeGroupsMap = @json($groups->groupBy('type')->map(fn ($g) => $g->pluck('id')->values()->toArray())->filter()->toArray());

// Filtra una lista de checkboxes por texto sin desmarcar lo ya seleccionado.
function applyListFilter($input) {
  var $list = $($input.data('target'));
  var items = $input.data('items') || '.filter-row';
  var term = $.trim($input.val()).toLowerCase();
  var visibles = 0;
  $list.find(items).each(function() {
    var coincide = term === '' || ($(this).attr('data-label') || '').indexOf(term) !== -1;
    $(this).toggle(coincide);
    if (coincide) visibles++;
  });
  $list.find('.no-filter-results').toggle(visibles === 0 && term !== '');
}

// Los archivos visibles dependen del texto buscado y de las categorias marcadas.
function refreshArchivoItems() {
  var $list = $('#archivo_list');
  var term = $.trim($('#archivo_search').val()).toLowerCase();
  var categorias = checkedValues('.file-category-checkbox');
  var visibles = 0;
  $list.find('.archivo-item').each(function() {
    var cats = ($(this).attr('data-cats') || '').split(',').filter(Boolean);
    var coincideTexto = term === '' || ($(this).attr('data-label') || '').indexOf(term) !== -1;
    var coincideCategoria = categorias.length === 0 || categorias.some(function(c) {
      return cats.indexOf(c) !== -1;
    });
    var visible = coincideTexto && coincideCategoria;
    $(this).toggle(visible);
    if (visible) visibles++;
  });
  $list.find('.no-filter-results').toggle(visibles === 0 && (term !== '' || categorias.length > 0));
}

// El checkbox "Todos" opera sobre lo visible tras la busqueda, no sobre la lista completa.
function updateSelectAll($selector, $container, itemSelector) {
  var visibles = $container.find(itemSelector).filter(':visible').length;
  var marcados = $container.find(itemSelector).filter(':visible')
    .find('input[type="checkbox"]').filter(':checked').length;
  $selector.prop('checked', visibles > 0 && marcados === visibles)
    .prop('indeterminate', marcados > 0 && marcados < visibles);
}

function refreshAllSelectAll() {
  updateSelectAll($('#select_all_types'), $('#group_type_list'), '.group-type-item');
  updateSelectAll($('#select_all_plazas'), $('#plaza_list'), '.plaza-item');
  updateSelectAll($('#select_all_categories'), $('#file_category_list'), '.file-category-item');
  updateSelectAll($('#select_all_archivos'), $('#archivo_list'), '.archivo-item');
  updateSelectAll($('#select_all_conexion'), $('#conexion_list'), '.conexion-item');
  updateSelectAll($('#select_all_estado'), $('#estado_list'), '.estado-item');
}

// Clic sobre la fila (no sobre el checkbox ni sobre el label, que ya lo alternan)
// marca o desmarca el checkbox, para no tener que apuntar al cuadrito.
function bindRowToggle($list) {
  $list.on('click', '.filter-row', function(e) {
    if ($(e.target).is('input[type="checkbox"], label')) return;
    var checkbox = $(this).find('input[type="checkbox"]').first();
    if (!checkbox.length) return;
    e.preventDefault();
    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
  });
}

var currentPage = 0;
var pageSize = 10;
var totalRecords = 0;
var lastJson = null;
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
  var txt = 'Computadoras: ' + (json.total_computadoras || 0) + ' | Archivos: ' + (json.recordsTotal || 0);
  $('#total_computadoras').text(txt);
}

function getCategoryInfo(file) {
  var name = (file.name || '').toUpperCase();
  var path = (file.path || '').toUpperCase();
  var ext = name.split('.').pop();
  if (ext === 'EXE') return { label: '.EXE', badge: 'bg-info' };
  if (ext === 'BAT') return { label: '.BAT', badge: 'bg-success' };
  if (ext === 'DBF') {
    if (path.indexOf('QUICKBCK') !== -1 || name.indexOf('QUICKBCK') !== -1) {
      return { label: 'QBCK', badge: 'bg-warning' };
    }
    return { label: '.DBF', badge: 'bg-primary' };
  }
  return { label: 'Otros', badge: 'bg-secondary' };
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var $tbody = $('#computersTableBody');
  var $loading = $('#computersLoading');
  $loading.addClass('d-none');
  $tbody.empty();

  if (data.length === 0) {
    $tbody.html('<tr><td colspan="13" class="text-center py-4 text-muted">No se encontraron registros</td></tr>');
    $('#paginationControls').addClass('d-none');
    return;
  }

  data.forEach(function(row) {
    var comp = {
      nombre_instalacion: row.nombre_instalacion,
      plaza: row.plaza,
      status: row.status
    };
    var file = row.file || {};
    var statusBadge = comp.status === 'online'
      ? '<span class="badge bg-success">Online</span>'
      : '<span class="badge bg-danger">Offline</span>';

    var cat = getCategoryInfo(file);
    var size = file.size ? (file.size / 1024).toFixed(2) + ' KB' : 'N/A';
    var modified = formatAgentModifiedDate(file.modified || '');
    var rbfStatus = file.rbf_matched
      ? '<span class="badge bg-success">OK</span>'
      : '<span class="badge bg-danger">Falta</span>';
    $tbody.append('<tr>' +
      '<td><strong>' + (comp.nombre_instalacion || 'N/A') + '</strong></td>' +
      '<td>' + (comp.plaza || 'N/A') + '</td>' +
      '<td>' + statusBadge + '</td>' +
      '<td class="text-center"><span class="badge ' + cat.badge + '">' + cat.label + '</span></td>' +
      '<td><strong>' + (file.name || 'N/A') + '</strong></td>' +
      '<td style="word-break:break-all;">' + (file.path || 'N/A') + '</td>' +
      '<td>' + size + '</td>' +
      '<td style="white-space:nowrap;">' + modified + '</td>' +
      '<td style="word-break:break-all;"><code style="font-size:0.65rem;">' + (file.hash_md5 ? file.hash_md5.slice(-5) : '') + '</code></td>' +
      '<td style="word-break:break-all;">' + (file.rbf_path || '') + '</td>' +
      '<td style="word-break:break-all;"><code style="font-size:0.65rem;">' + (file.rbf_hash || '') + '</code></td>' +
      '<td style="white-space:nowrap;">' + (file.rbf_last_modified || '<span class="text-muted">-</span>') + '</td>' +
      '<td class="text-center">' + rbfStatus + '</td>' +
    '</tr>');
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
  bindRowToggle($('.filter-list'));
  refreshArchivoItems();
  refreshAllSelectAll();
  loadData();

  $('#btn_search').on('click', function() { currentPage = 0; loadData(); });
  $('#btn_refresh').on('click', function() { currentPage = 0; loadData(); });

  $('#paginationNumbers').on('click', '.page-btn', function(e) {
    e.preventDefault();
    currentPage = parseInt($(this).data('page'));
    loadData();
  });

  $('#btn_reset_filters').on('click', function() {
    $('.plaza-checkbox, .group-type-checkbox, .file-category-checkbox, .archivo-checkbox, .conexion-checkbox, .estado-checkbox')
      .prop('checked', false);
    $('.filter-search').val('');
    $('.filter-list .no-filter-results').addClass('d-none');
    $('#computer_search').val('');
    $('#select_all_plazas, #select_all_types, #select_all_categories, #select_all_archivos, #select_all_conexion, #select_all_estado')
      .prop('checked', false).prop('indeterminate', false);
    refreshArchivoItems();
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });

  // Buscadores de cada lista de checkboxes: solo acotan la lista, la
  // seleccion se sigue haciendo marcando los checkboxes.
  $('.filter-search').on('input', function() {
    var $input = $(this);
    if ($input.data('target') === '#archivo_list') {
      refreshArchivoItems();
    } else {
      applyListFilter($input);
    }
    refreshAllSelectAll();
  });

  $('#select_all_plazas').on('change', function() {
    $('#plaza_list .plaza-item:visible .plaza-checkbox').prop('checked', $(this).prop('checked'));
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#select_all_types').on('change', function() {
    $('#group_type_list .group-type-item:visible .group-type-checkbox').prop('checked', $(this).prop('checked'));
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#select_all_categories').on('change', function() {
    $('#file_category_list .file-category-item:visible .file-category-checkbox').prop('checked', $(this).prop('checked'));
    refreshArchivoItems();
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#select_all_archivos').on('change', function() {
    $('#archivo_list .archivo-item:visible .archivo-checkbox').prop('checked', $(this).prop('checked'));
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#select_all_conexion').on('change', function() {
    $('#conexion_list .conexion-item:visible .conexion-checkbox').prop('checked', $(this).prop('checked'));
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('#select_all_estado').on('change', function() {
    $('#estado_list .estado-item:visible .estado-checkbox').prop('checked', $(this).prop('checked'));
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });

  $('.plaza-checkbox, .group-type-checkbox, .conexion-checkbox, .estado-checkbox').on('change', function() {
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('.file-category-checkbox').on('change', function() {
    refreshArchivoItems();
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });
  $('.archivo-checkbox').on('change', function() {
    refreshAllSelectAll();
    currentPage = 0;
    loadData();
  });

  $('#pageSizeSelect').on('change', function() {
    pageSize = parseInt($(this).val(), 10) || 10;
    currentPage = 0;
    loadData();
  });
  $('#btn_toggle_filters').on('click', function() {
    $('#filtersBody').slideToggle(200);
    $(this).find('i').toggleClass('fa-minus fa-plus');
  });
  $('#computer_search').on('keypress', function(e) {
    if (e.which === 13) { currentPage = 0; loadData(); }
  });

  $('#btn_export').on('click', function() {
    var filtros = getFilters();
    var params = new URLSearchParams();
    (filtros.plaza || []).forEach(function(v) { params.append('plaza[]', v); });
    (filtros.type || []).forEach(function(v) { params.append('type[]', v); });
    (filtros.file_category || []).forEach(function(v) { params.append('file_category[]', v); });
    (filtros.archivo || []).forEach(function(v) { params.append('archivo[]', v); });
    (filtros.conexion || []).forEach(function(v) { params.append('conexion[]', v); });
    (filtros.estado || []).forEach(function(v) { params.append('estado[]', v); });
    if (filtros.search) params.append('search', filtros.search);
    params.append('_t', Date.now());
    window.open("{{ url('/reportes/dbf-files/export') }}?" + params.toString(), '_blank');
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
