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
            <option value="dbf">Solo .DBF</option>
            <option value="qbck">Solo QBCK</option>
            <option value="exe">Solo .EXE</option>
            <option value="bat">Solo .BAT</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Archivo</label>
          <select id="archivo_filter" class="form-control form-control-sm">
            <option value="">Todos los archivos</option>
            @foreach($archivos as $archivo)
            <option value="{{ $archivo }}">{{ $archivo }}</option>
            @endforeach
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
              <th style="cursor:pointer" data-sort="status">Estado <i class="fas fa-sort"></i></th>
              <th>Categoria</th>
              <th>Nombre</th>
              <th>Ruta</th>
              <th>Tamano</th>
              <th>Modificacion</th>
              <th>MD5</th>
              <th>Ruta RBF</th>
              <th>Hash RBF</th>
              <th>Estado Archivo</th>
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

#computersTable { font-size: 0.8rem; }
#computersTable thead th { white-space: nowrap; font-size: 0.75rem; }
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

function getFilters() {
  var d = {};
  var plazas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
  var grupos = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (plazas.length) d.plaza = plazas;
  if (grupos.length) d.group_id = grupos;
  if ($('#file_category_filter').val()) d.file_category = $('#file_category_filter').val();
  if ($('#archivo_filter').val()) d.archivo = $('#archivo_filter').val();
  if ($('#hash_filter').val()) d.hash = $('#hash_filter').val();
  if ($('#computer_search').val()) d.search = $('#computer_search').val();
  if ($('#estado_filter').val()) d.estado = $('#estado_filter').val();
  return d;
}

var currentPage = 0;
var pageSize = 50;
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
  $('#total_computadoras').text('Total: ' + (json.recordsTotal || 0) + ' computadoras');
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
    $tbody.html('<tr><td colspan="12" class="text-center py-4 text-muted">No se encontraron computadoras</td></tr>');
    $('#computersPagination').addClass('d-none');
    return;
  }

  var estadoFiltro = $('#estado_filter').val();
  var rows = 0;
  data.forEach(function(comp) {
    var files = comp.dbf_files || [];
    var statusBadge = comp.status === 'online'
      ? '<span class="badge bg-success">Online</span>'
      : '<span class="badge bg-danger">Offline</span>';
    files.forEach(function(file) {
      // Filtro por estado individual del archivo
      if (estadoFiltro === 'actualizado' && !file.rbf_matched) return;
      if (estadoFiltro === 'desactualizado' && file.rbf_matched) return;

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
        '<td class="text-center">' + rbfStatus + '</td>' +
      '</tr>');
      rows++;
    });
  });

  if (rows === 0) {
    $tbody.html('<tr><td colspan="12" class="text-center py-4 text-muted">No se encontraron archivos</td></tr>');
  }

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
    $('#archivo_filter').val('');
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
    var archivo = $('#archivo_filter').val();
    var hash = $('#hash_filter').val();
    var params = new URLSearchParams();
    plazasSeleccionadas.forEach(function(val) { params.append('plaza[]', val); });
    gruposSeleccionados.forEach(function(val) { params.append('group_id[]', val); });
    if (fileCategory) params.append('file_category', fileCategory);
    if (archivo) params.append('archivo', archivo);
    if (hash) params.append('hash', hash);
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
