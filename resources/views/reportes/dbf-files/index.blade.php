@extends('adminlte::page')
@section('title', 'Archivos DBF - Computadoras')

@section('content_header')
<h1>Archivos DBF - Computadoras</h1>
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
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Archivo DBF</label>
          <select id="archivo_filter" class="form-select form-select-sm">
            <option value="">Todos los archivos</option>
            @foreach($archivos as $archivo)
            <option value="{{ $archivo }}">{{ $archivo }}</option>
            @endforeach
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

  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
      <h5 class="mb-0">
        <i class="fas fa-desktop"></i> Computadoras con Archivos DBF
      </h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="report-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
          <thead class="thead-light">
            <tr>
              <th>Computadora</th>
              <th>Plaza</th>
              <th>Grupo</th>
              <th>Estado</th>
              <th>Última Conexión</th>
              <th>Archivos DBF</th>
              <th>Detalle</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="detailModalLabel">Detalle de Archivos DBF</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 id="modalComputerName"></h6>
        <div class="table-responsive">
          <table class="table table-sm table-bordered" id="dbfFilesTable">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th>Ruta</th>
                <th>Tamaño</th>
                <th>Última Modificación</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
.card-header { border-bottom: 2px solid #dee2e6; }
.table th { background-color: #f8f9fa; font-weight: 600; font-size: 0.75rem; white-space: nowrap; }
.table td { font-size: 0.75rem; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.badge { font-size: 0.7rem; }
.form-label { font-size: 0.75rem; }
.form-control-sm { font-size: 0.75rem; }
@media (max-width: 768px) {
  .table th, .table td { font-size: 0.65rem; padding: 0.25rem; }
  .btn-sm { padding: 0.2rem 0.4rem; font-size: 0.7rem; }
}
</style>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function() {
  const dataTable = $('#report-table').DataTable({
    processing: true,
    serverSide: false,
    responsive: true,
    pageLength: -1,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
    language: {
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_ por página",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      },
      emptyTable: "No hay datos disponibles",
      zeroRecords: "No se encontraron resultados",
      loadingRecords: "Cargando...",
      processing: "Procesando..."
    },
    ajax: {
      url: "{{ url('/reportes/dbf-files/data') }}",
      type: "GET",
      data: function (d) {
        const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
        const gruposSeleccionados = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
        
        if (plazasSeleccionadas.length > 0) {
          d.plaza = plazasSeleccionadas;
        }
        if (gruposSeleccionados.length > 0) {
          d.group_id = gruposSeleccionados;
        }
        if ($('#archivo_filter').val()) {
          d.archivo = $('#archivo_filter').val();
        }
      },
      dataSrc: function(json) {
        $('#total_computadoras').text('Total: ' + json.recordsTotal + ' computadoras');
        return json.data;
      },
      error: function(xhr, error, thrown) {
        console.log('Error:', xhr.responseText);
        alert('Error cargando datos: ' + xhr.status);
      }
    },
    columns: [
      { data: 'computer_name', className: 'text-center' },
      { data: 'plaza', className: 'text-center' },
      { data: 'group_name', className: 'text-center' },
      { data: 'status', className: 'text-center', render: function(data) {
        if (data === 'online') return '<span class="badge bg-success">Online</span>';
        if (data === 'offline') return '<span class="badge bg-danger">Offline</span>';
        return '<span class="badge bg-secondary">'+data+'</span>';
      }},
      { data: 'last_seen', className: 'text-center' },
      { data: 'dbf_files_count', className: 'text-center', render: function(data) {
        return '<span class="badge bg-info">'+data+'</span>';
      }},
      { data: null, className: 'text-center', render: function(data, type, row) {
        return '<button class="btn btn-sm btn-primary btn-detail" data-computer=\''+JSON.stringify(row)+'\'>' +
               '<i class="fas fa-eye"></i></button>';
      }}
    ]
  });

  $('#btn_search').on('click', function() { dataTable.ajax.reload(); });
  $('#btn_refresh').on('click', function() { dataTable.ajax.reload(); });

  $('#btn_reset_filters').on('click', function() {
    $('.plaza-checkbox').prop('checked', false);
    $('.group-checkbox').prop('checked', false);
    $('#select_all_plazas').prop('checked', false);
    $('#select_all_groups').prop('checked', false);
    $('#archivo_filter').val('');
  });

  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('#select_all_groups').on('change', function() {
    $('.group-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('.plaza-checkbox, .group-checkbox').on('change', function() {
    dataTable.ajax.reload();
  });

  $('#archivo_filter').on('change', function() {
    dataTable.ajax.reload();
  });

  $('#btn_export').on('click', function() {
    const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
    const gruposSeleccionados = $('.group-checkbox:checked').map(function() { return $(this).val(); }).get();
    const archivo = $('#archivo_filter').val();
    
    console.log('Export clicked - archivo:', archivo, 'plazas:', plazasSeleccionadas, 'grupos:', gruposSeleccionados);
    
    const params = new URLSearchParams();
    
    plazasSeleccionadas.forEach(function(val) {
      params.append('plaza[]', val);
    });
    gruposSeleccionados.forEach(function(val) {
      params.append('group_id[]', val);
    });
    if (archivo) {
      params.append('archivo', archivo);
    }
    
    const url = "{{ url('/reportes/dbf-files/export') }}?" + params.toString();
    console.log('Export URL:', url);
    window.open(url, '_blank');
  });

  $('#report-table').on('click', '.btn-detail', function() {
    const computer = $(this).data('computer');
    $('#modalComputerName').text(computer.computer_name + ' - ' + computer.plaza + ' / ' + computer.group_name);
    
    const tbody = $('#dbfFilesTable tbody');
    tbody.empty();
    
    if (computer.dbf_files && computer.dbf_files.length > 0) {
      computer.dbf_files.forEach(function(file) {
        const size = file.size ? (file.size / 1024).toFixed(2) + ' KB' : 'N/A';
        let modified = file.modified || 'N/A';
        if (modified !== 'N/A' && modified.includes('T')) {
          const parts = modified.split('T');
          modified = parts[0] + ' ' + parts[1].substring(0, 8);
        }
        tbody.append('<tr>' +
          '<td>'+(file.name || 'N/A')+'</td>' +
          '<td style="word-break: break-all; font-size: 0.65rem;">'+(file.path || 'N/A')+'</td>' +
          '<td>'+size+'</td>' +
          '<td>'+modified+'</td>' +
        '</tr>');
      });
    } else {
      tbody.append('<tr><td colspan="4" class="text-center">No hay archivos DBF</td></tr>');
    }
    
    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
    modal.show();
  });
});
</script>
@endsection
