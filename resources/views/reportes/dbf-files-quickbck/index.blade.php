@extends('adminlte::page')
@section('title', 'Conciliación QuickBCK')

@section('content_header')
<h1>Conciliación QuickBCK</h1>
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
          <label class="form-label small mb-1">Archivo</label>
          <select id="archivo_filter" class="form-control form-control-sm">
            <option value="">Todos los archivos</option>
            @foreach($archivos as $archivo)
            <option value="{{ $archivo }}">{{ $archivo }}</option>
            @endforeach
          </select>
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
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Conciliación</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" class="form-check-input estado-checkbox" value="conciliado" id="estado_conciliado">
              <label class="form-check-label" for="estado_conciliado">Conciliado</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input estado-checkbox" value="parcial_ok" id="estado_parcial_ok">
              <label class="form-check-label" for="estado_parcial_ok">Parcial OK</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input estado-checkbox" value="parcial_error" id="estado_parcial_error">
              <label class="form-check-label" for="estado_parcial_error">Parcial Error</label>
            </div>
            <div class="form-check">
              <input type="checkbox" class="form-check-input estado-checkbox" value="sin_conciliar" id="estado_sin_conciliar">
              <label class="form-check-label" for="estado_sin_conciliar">Sin Conciliar</label>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-2">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <span id="total_records" class="badge bg-info"></span>
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
            <button id="btn_sync_hashes" class="btn btn-warning btn-sm">
              <i class="fas fa-download"></i> Sincronizar Hashes
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <div class="col-sm-6 col-md">
      <div class="card text-bg-light h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statTotal">0</span>
          <small class="text-muted">Total Archivos</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md">
      <div class="card text-bg-success h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statConciliado">0</span>
          <small>Conciliado</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md">
      <div class="card text-bg-warning h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statParcialOk">0</span>
          <small>Parcial OK</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md">
      <div class="card text-bg-danger h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statParcialError">0</span>
          <small>Parcial Error</small>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md">
      <div class="card text-bg-secondary h-100">
        <div class="card-body py-2 px-3 text-center">
          <span class="d-block fs-4 fw-bold" id="statSinConciliar">0</span>
          <small>Sin Conciliar</small>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-list"></i> Archivos QuickBCK por Computadora
      </h5>
    </div>
    <div class="card-body p-0">
      <div id="tableLoading" class="text-center py-4">
        <i class="fas fa-spinner fa-spin fa-2x"></i>
        <p class="mt-2">Cargando datos...</p>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover table-striped mb-0" id="filesTable">
          <thead class="table-dark">
            <tr>
              <th style="cursor:pointer" data-sort="nombre_instalacion">Computadora <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="plaza">Plaza <i class="fas fa-sort"></i></th>
              <th style="cursor:pointer" data-sort="archivo">Archivo <i class="fas fa-sort"></i></th>
              <th class="text-center">Tamano</th>
              <th>MD5 Pvsi</th>
              <th>Fecha Pvsi</th>
              <th>MD5 Quick</th>
              <th>Fecha Quick</th>
              <th>MD5 RBF</th>
              <th>Fecha RBF</th>
              <th style="cursor:pointer" data-sort="status_conciliacion" class="text-center">Conciliación <i class="fas fa-sort"></i></th>
            </tr>
          </thead>
          <tbody id="filesTableBody">
          </tbody>
        </table>
      </div>
      <div id="pagination" class="d-flex justify-content-between align-items-center mt-2 p-2 d-none flex-wrap gap-2">
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
#filesTable { font-size: 0.72rem; }
#filesTable thead th { white-space: nowrap; font-size: 0.7rem; }
#filesTable tbody td { font-size: 0.72rem; vertical-align: middle; }
</style>
@endsection

@section('js')
<script>
function hashDisplay(value, matched, status) {
  if (!value) return '<span class="text-muted">-</span>';
  var color;
  if (status === 'conciliado') {
    color = '#28a745';
  } else if (status === 'parcial_ok' || status === 'parcial_error') {
    if (matched) {
      color = '#28a745';
    } else {
      return '<code style="font-size:0.65rem;">' + value + '</code>';
    }
  } else {
    color = '#dc3545';
  }
  return '<code style="font-size:0.65rem;color:' + color + ';font-weight:bold;">' + value + '</code>';
}

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
  var grupos = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (grupos.length) d.group_id = grupos;
  if ($('#archivo_filter').val()) d.archivo = $('#archivo_filter').val();
  if ($('#computer_search').val()) d.search = $('#computer_search').val();
  var estados = $('.estado-checkbox:checked').map(function() { return $(this).val(); }).get();
  if (estados.length) d.estado = estados;
  return d;
}

var currentPage = 0;
var pageSize = 50;
var totalRecords = 0;
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

  $('#tableLoading').removeClass('d-none');
  $('#filesTableBody').empty();

  $.ajax({
    url: "{{ url('/reportes/dbf-files-quickbck/data') }}",
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
  var s = json.conciliacion_stats || {};
  $('#statTotal').text(s.total || 0);
  $('#statConciliado').text(s.conciliado || 0);
  $('#statParcialOk').text(s.parcial_ok || 0);
  $('#statParcialError').text(s.parcial_error || 0);
  $('#statSinConciliar').text(s.sin_conciliar || 0);
}

function renderTable(json) {
  totalRecords = json.recordsTotal || 0;
  var data = json.data || [];
  var $tbody = $('#filesTableBody');
  $('#tableLoading').addClass('d-none');
  $tbody.empty();

  if (data.length === 0) {
    $tbody.html('<tr><td colspan="11" class="text-center py-4 text-muted">No se encontraron archivos QuickBCK</td></tr>');
    $('#pagination').addClass('d-none');
    return;
  }

  data.forEach(function(row) {
    var statusBadge;
    var desactualizadoBadge = '';
    if (row.status_conciliacion === 'conciliado') {
      statusBadge = '<span class="badge bg-success">Conciliado</span>';
    } else if (row.status_conciliacion === 'parcial_ok') {
      statusBadge = '<span class="badge bg-warning text-dark">Parcial OK</span>';
    } else if (row.status_conciliacion === 'parcial_error') {
      statusBadge = '<span class="badge bg-danger">Parcial Error</span>';
      if (row.desactualizado) {
        desactualizadoBadge = ' <span class="badge bg-secondary">Desactualizado</span>';
      }
    } else {
      statusBadge = '<span class="badge bg-secondary">Sin Conciliar</span>';
    }

    var size = row.tamano !== null ? row.tamano + ' KB' : 'N/A';
    var modified = formatAgentModifiedDate(row.modificacion || '');

    $tbody.append(
      '<tr>' +
        '<td><strong>' + (row.nombre_instalacion || 'N/A') + '</strong></td>' +
        '<td>' + (row.plaza || 'N/A') + '</td>' +
        '<td><strong>' + (row.archivo || 'N/A') + '</strong></td>'  +
        '<td class="text-center">' + size + '</td>' +
        '<td>' + hashDisplay(row.pvsi_md5, row.pvsi_matched, row.status_conciliacion) + '</td>' +
        '<td style="white-space:nowrap;">' + (row.pvsi_fecha || '<span class="text-muted">-</span>') + '</td>' +
        '<td>' + hashDisplay(row.md5, row.pvsi_matched || row.rbf_matched, row.status_conciliacion) + '</td>' +
        '<td style="white-space:nowrap;">' + modified + '</td>' +
        '<td>' + hashDisplay(row.rbf_md5, row.rbf_matched, row.status_conciliacion) + '</td>' +
        '<td style="white-space:nowrap;">' + (row.rbf_fecha || '<span class="text-muted">-</span>') + '</td>' +
        '<td class="text-center">' + statusBadge + desactualizadoBadge + '</td>' +
      '</tr>'
    );
  });

  updatePagination();
}

function updatePagination() {
  var totalPages = Math.ceil(totalRecords / pageSize);
  if (totalPages <= 1) {
    $('#pagination').addClass('d-none');
    return;
  }
  $('#pagination').removeClass('d-none');
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
    $('#select_all_plazas').prop('checked', false);
    $('.group-checkbox').prop('checked', false);
    $('#select_all_groups').prop('checked', false);
    $('#archivo_filter').val('');
    $('#computer_search').val('');
    $('.estado-checkbox').prop('checked', false);
    currentPage = 0;
    loadData();
  });

  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
  });
  $('#select_all_groups').on('change', function() {
    $('.group-checkbox').prop('checked', $(this).prop('checked'));
  });
  $('.plaza-checkbox').on('change', function() { currentPage = 0; loadData(); });
  $('.group-checkbox').on('change', function() { currentPage = 0; loadData(); });
  $('#archivo_filter').on('change', function() { currentPage = 0; loadData(); });
  $('.estado-checkbox').on('change', function() { currentPage = 0; loadData(); });
  $('#computer_search').on('keypress', function(e) {
    if (e.which === 13) { currentPage = 0; loadData(); }
  });

  $('#btn_export').on('click', function() {
    var plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
    var gruposSeleccionados = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
    var archivo = $('#archivo_filter').val();
    var params = new URLSearchParams();
    plazasSeleccionadas.forEach(function(val) { params.append('plaza[]', val); });
    gruposSeleccionados.forEach(function(val) { params.append('group_id[]', val); });
    if (archivo) params.append('archivo', archivo);
    params.append('_t', Date.now());
    window.open("{{ url('/reportes/dbf-files-quickbck/export') }}?" + params.toString(), '_blank');
  });

  $('#btn_sync_hashes').on('click', function() {
    var $btn = $(this);
    if (!confirm('Sincronizar hashes desde la API de Conciliacion? Esto actualizara todos los registros.')) return;
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sincronizando...');
    $.ajax({
      url: "{{ route('reportes.dbf-files-quickbck.sync') }}",
      type: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      success: function(response) {
        alert(response.message);
        currentPage = 0;
        loadData();
      },
      error: function(xhr) {
        var msg = xhr.responseJSON?.message || 'Error al sincronizar';
        alert(msg);
      },
      complete: function() {
        $btn.prop('disabled', false).html('<i class="fas fa-download"></i> Sincronizar Hashes');
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
