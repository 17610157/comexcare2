@extends('adminlte::page')
@section('title', 'Notas Completas')

@section('content_header')
<h1>Notas Completas</h1>
@stop

@section('content')
<div class="container-fluid">
  <!-- Filtros Superiores -->
  <div class="card bg-light mb-3">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-filter"></i> Filtros
      </h5>
    </div>
    <div class="card-body">
      @php
        $startDefault = \Carbon\Carbon::parse('first day of previous month')->toDateString();
        $endDefault = \Carbon\Carbon::parse('last day of previous month')->toDateString();
      @endphp
      
      <div class="row g-2">
        <div class="col-6 col-md-2">
          <label for="period_start" class="form-label small mb-1">Periodo Inicio</label>
          <input type="date" id="period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
        </div>
        <div class="col-6 col-md-2">
          <label for="period_end" class="form-label small mb-1">Periodo Fin</label>
          <input type="date" id="period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
        </div>
        <div class="col-4 col-md-2">
          <label for="plaza" class="form-label small mb-1">Plaza</label>
          <input type="text" id="plaza" class="form-control form-control-sm border-secondary" placeholder="Plaza">
        </div>
        <div class="col-4 col-md-2">
          <label for="tienda" class="form-label small mb-1">Tienda</label>
          <input type="text" id="tienda" class="form-control form-control-sm border-secondary" placeholder="Tienda">
        </div>
        <div class="col-4 col-md-2">
          <label for="vendedor" class="form-label small mb-1">Vendedor</label>
          <input type="text" id="vendedor" class="form-control form-control-sm border-secondary" placeholder="Vendedor">
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <button id="btn_sync" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#syncModal">
              <i class="fas fa-database"></i> <span class="d-none d-sm-inline">Sincronizar Datos</span>
            </button>
            <span id="sync_status" class="badge bg-secondary align-self-center"></span>
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
            <button id="btn_csv" class="btn btn-info btn-sm">
              <i class="fas fa-file-csv"></i> <span class="d-none d-sm-inline">CSV</span>
            </button>
          </div>
        </div>
      </div>
      
      <div class="row mt-2">
        <div class="col-12">
          <span id="current_period_display" class="badge bg-info text-white"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- DataTable -->
  <div class="card">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-table"></i> Resultados
      </h5>
    </div>
    <div class="card-body p-0">
      <table id="report-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
        <thead class="thead-light">
          <tr>
            <th>Plaza</th>
            <th>Tienda</th>
            <th>Num Referencia</th>
            <th>Vendedor</th>
            <th>Factura</th>
            <th>Nota Club</th>
            <th>Club TR</th>
            <th>Club ID</th>
            <th>Fecha Vta</th>
            <th>Producto</th>
            <th>Descripcion</th>
            <th>Piezas</th>
            <th>Descuento</th>
            <th>Precio Venta</th>
            <th>Costo</th>
            <th>Total c/IVA</th>
            <th>Total s/IVA</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal de Sincronización -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="syncModalLabel">
          <i class="fas fa-database"></i> Sincronizar Datos
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Tipo de sincronización:</label>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="syncType" id="syncLastMonth" value="lastMonth" checked>
            <label class="form-check-label" for="syncLastMonth">Mes anterior (por defecto)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="syncType" id="syncLastDays" value="lastDays">
            <label class="form-check-label" for="syncLastDays">Últimos días</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="syncType" id="syncDay" value="day">
            <label class="form-check-label" for="syncDay">Un día específico</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="syncType" id="syncPeriod" value="period">
            <label class="form-check-label" for="syncPeriod">Período específico</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="syncType" id="syncFull" value="full">
            <label class="form-check-label" for="syncFull">Completo (desde 2000)</label>
          </div>
        </div>

        <div id="syncLastDaysOptions" class="mb-3" style="display:none;">
          <label for="lastDaysInput" class="form-label">Número de días:</label>
          <input type="number" class="form-control" id="lastDaysInput" value="30" min="1" max="365">
        </div>

        <div id="syncDayOptions" class="mb-3" style="display:none;">
          <label for="dayInput" class="form-label">Fecha:</label>
          <input type="date" class="form-control" id="dayInput" value="{{ date('Y-m-d') }}">
        </div>

        <div id="syncPeriodOptions" class="mb-3" style="display:none;">
          <div class="row">
            <div class="col-6">
              <label for="periodStartInput" class="form-label">Fecha inicio:</label>
              <input type="date" class="form-control" id="periodStartInput" value="{{ date('Y-m-01') }}">
            </div>
            <div class="col-6">
              <label for="periodEndInput" class="form-label">Fecha fin:</label>
              <input type="date" class="form-control" id="periodEndInput" value="{{ date('Y-m-d') }}">
            </div>
          </div>
        </div>

        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="appendData">
          <label class="form-check-label" for="appendData">Agregar datos sin limpiar la tabla (append)</label>
        </div>

        <div id="syncProgress" class="alert alert-info" style="display:none;">
          <i class="fas fa-spinner fa-spin"></i> Sincronizando...
        </div>

        <div id="syncResult" class="alert" style="display:none;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-warning" id="btnExecuteSync">
          <i class="fas fa-sync-alt"></i> Sincronizar
        </button>
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
    serverSide: true,
    responsive: true,
    pageLength: 5,
    language: {
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_ registros",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      paginate: {
        first: "Primero",
        last: "Último",
        next: "Siguiente",
        previous: "Anterior"
      },
      emptyTable: "No hay datos disponibles",
      zeroRecords: "No se encontraron resultados"
    },
    ajax: {
      url: "{{ url('/reportes/notas-completas/data') }}",
      data: function (d) {
        d.plaza = $('#plaza').val();
        d.tienda = $('#tienda').val();
        d.vendedor = $('#vendedor').val();
        if ($('#period_start').length && $('#period_start').val()) {
          d.period_start = $('#period_start').val();
        }
        if ($('#period_end').length && $('#period_end').val()) {
          d.period_end = $('#period_end').val();
        }
      }
    },
    columns: [
      { data: 'plaza_ajustada', className: 'text-center' },
      { data: 'ctienda', className: 'text-center' },
      { data: 'num_referencia', className: 'text-center' },
      { data: 'vend_clave', className: 'text-center' },
      { data: 'factura', className: 'text-center' },
      { data: 'nota_club' },
      { data: 'club_tr' },
      { data: 'club_id' },
      { data: 'fecha_vta', className: 'text-center' },
      { data: 'producto', className: 'text-center' },
      { data: 'descripcion' },
      { data: 'piezas', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 0, '') },
      { data: 'descuento', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '') },
      { data: 'precio_venta', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'costo', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'total_con_iva', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'total_sin_iva', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') }
    ]
  });

  $('#btn_search').on('click', function() { dataTable.ajax.reload(); });
  $('#btn_refresh').on('click', function() { dataTable.ajax.reload(); });

  $('#btn_reset_filters').on('click', function() {
    $('#period_start').val("{{ $startDefault }}");
    $('#period_end').val("{{ $endDefault }}");
    $('#plaza').val('');
    $('#tienda').val('');
    $('#vendedor').val('');
    updateCurrentPeriodDisplay();
  });

  $('#btn_excel').on('click', function() {
    const start = $('#period_start').val();
    const end = $('#period_end').val();
    const plaza = $('#plaza').val();
    const tienda = $('#tienda').val();
    const vendedor = $('#vendedor').val();
    
    let form = $('<form>', {
      'method': 'POST',
      'action': "{{ url('/reportes/notas-completas/export-excel') }}",
      'target': '_blank'
    });
    
    form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': "{{ csrf_token() }}" }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_start', 'value': start }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_end', 'value': end }));
    if (plaza) form.append($('<input>', { 'type': 'hidden', 'name': 'plaza', 'value': plaza }));
    if (tienda) form.append($('<input>', { 'type': 'hidden', 'name': 'tienda', 'value': tienda }));
    if (vendedor) form.append($('<input>', { 'type': 'hidden', 'name': 'vendedor', 'value': vendedor }));
    
    $('body').append(form);
    form.submit();
    form.remove();
  });

  $('#btn_csv').on('click', function() {
    const start = $('#period_start').val();
    const end = $('#period_end').val();
    const plaza = $('#plaza').val();
    const tienda = $('#tienda').val();
    const vendedor = $('#vendedor').val();
    
    let form = $('<form>', {
      'method': 'POST',
      'action': "{{ url('/reportes/notas-completas/export-csv') }}",
      'target': '_blank'
    });
    
    form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': "{{ csrf_token() }}" }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_start', 'value': start }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_end', 'value': end }));
    if (plaza) form.append($('<input>', { 'type': 'hidden', 'name': 'plaza', 'value': plaza }));
    if (tienda) form.append($('<input>', { 'type': 'hidden', 'name': 'tienda', 'value': tienda }));
    if (vendedor) form.append($('<input>', { 'type': 'hidden', 'name': 'vendedor', 'value': vendedor }));
    
    $('body').append(form);
    form.submit();
    form.remove();
  });

  $('#period_start, #period_end').on('change', function() {
    dataTable.ajax.reload();
    updateCurrentPeriodDisplay();
  });

  function updateCurrentPeriodDisplay(){
    const s = $('#period_start').val();
    const e = $('#period_end').val();
    $('#current_period_display').text('Periodo: ' + s + ' a ' + e);
  }

  updateCurrentPeriodDisplay();

  // Sincronización Modal
  $('input[name="syncType"]').on('change', function() {
    const syncType = $('input[name="syncType"]:checked').val();
    $('#syncLastDaysOptions').hide();
    $('#syncDayOptions').hide();
    $('#syncPeriodOptions').hide();
    
    if (syncType === 'lastDays') {
      $('#syncLastDaysOptions').show();
    } else if (syncType === 'day') {
      $('#syncDayOptions').show();
    } else if (syncType === 'period') {
      $('#syncPeriodOptions').show();
    }
  });

  $('#btnExecuteSync').on('click', function() {
    const syncType = $('input[name="syncType"]:checked').val();
    const append = $('#appendData').is(':checked');
    
    let url = "{{ url('/reportes/notas-completas/sync') }}";
    let data = {
      _token: "{{ csrf_token() }}",
      type: syncType,
      append: append
    };
    
    if (syncType === 'lastDays') {
      data.lastDays = $('#lastDaysInput').val();
    } else if (syncType === 'day') {
      data.day = $('#dayInput').val();
    } else if (syncType === 'period') {
      data.periodStart = $('#periodStartInput').val();
      data.periodEnd = $('#periodEndInput').val();
    }
    
    $('#syncProgress').show();
    $('#syncResult').hide();
    $('#btnExecuteSync').prop('disabled', true);
    
    $.ajax({
      url: url,
      type: 'POST',
      data: data,
      success: function(response) {
        $('#syncProgress').hide();
        $('#syncResult').show();
        if (response.success) {
          $('#syncResult').removeClass('alert-danger').addClass('alert-success');
          $('#syncResult').html('<i class="fas fa-check-circle"></i> ' + response.message);
          dataTable.ajax.reload();
        } else {
          $('#syncResult').removeClass('alert-success').addClass('alert-danger');
          $('#syncResult').html('<i class="fas fa-exclamation-circle"></i> ' + response.message);
        }
      },
      error: function(xhr) {
        $('#syncProgress').hide();
        $('#syncResult').show();
        $('#syncResult').removeClass('alert-success').addClass('alert-danger');
        $('#syncResult').html('<i class="fas fa-exclamation-circle"></i> Error: ' + xhr.responseJSON.message);
      },
      complete: function() {
        $('#btnExecuteSync').prop('disabled', false);
      }
    });
  });
});
</script>
@endsection
