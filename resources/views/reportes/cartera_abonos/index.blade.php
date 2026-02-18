@extends('adminlte::page')
@section('title', 'Cartera Abonos')

@section('content_header')
<h1>Cartera - Abonos</h1>
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
        <div class="col-6 col-md-3">
          <label for="period_start" class="form-label small mb-1">Periodo Inicio</label>
          <input type="date" id="period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
        </div>
        <div class="col-6 col-md-3">
          <label for="period_end" class="form-label small mb-1">Periodo Fin</label>
          <input type="date" id="period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
        </div>
        <div class="col-6 col-md-3">
          <label for="plaza" class="form-label small mb-1">Código Plaza</label>
          <input type="text" id="plaza" class="form-control form-control-sm border-secondary" placeholder="Ej: A001" maxlength="5" style="text-transform: uppercase;">
        </div>
        <div class="col-6 col-md-3">
          <label for="tienda" class="form-label small mb-1">Código Tienda</label>
          <input type="text" id="tienda" class="form-control form-control-sm border-secondary" placeholder="Ej: B001" maxlength="10" style="text-transform: uppercase;">
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
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
      <h5 class="mb-0">
        <i class="fas fa-table"></i> Resultados
      </h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="report-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
          <thead class="thead-light">
          <tr>
            <th>Plaza</th>
            <th>Tienda</th>
            <th>Fecha</th>
            <th>Fecha Vta</th>
            <th>Concepto</th>
            <th>Tipo</th>
            <th>Factura</th>
            <th>Clave</th>
            <th>RFC</th>
            <th>Nombre</th>
            <th>Vendedor</th>
            <th>Monto FA</th>
            <th>Monto DV</th>
            <th>Monto CD</th>
            <th>Días Crédito</th>
            <th>Días Vencidos</th>
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
              <label class="form-check-label" for="syncLastMonth">
                Mes anterior (por defecto)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="syncType" id="syncLastDays" value="lastDays">
              <label class="form-check-label" for="syncLastDays">
                Últimos días
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="syncType" id="syncDay" value="day">
              <label class="form-check-label" for="syncDay">
                Un día específico
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="syncType" id="syncPeriod" value="period">
              <label class="form-check-label" for="syncPeriod">
                Período específico
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="syncType" id="syncFull" value="full">
              <label class="form-check-label" for="syncFull">
                Completo (desde 2000)
              </label>
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
            <label class="form-check-label" for="appendData">
              Agregar datos sin limpiar la tabla (append)
            </label>
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
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
.card-header {
  border-bottom: 2px solid #dee2e6;
}
.table th {
  background-color: #f8f9fa;
  font-weight: 600;
  font-size: 0.75rem;
  white-space: nowrap;
}
.table td {
  font-size: 0.75rem;
  white-space: nowrap;
  max-width: 120px;
  overflow: hidden;
  text-overflow: ellipsis;
}
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
.badge {
  font-size: 0.7rem;
}
.form-label {
  font-size: 0.75rem;
}
.form-control-sm {
  font-size: 0.75rem;
}
@media (max-width: 768px) {
  .table th, .table td {
    font-size: 0.65rem;
    padding: 0.25rem;
  }
  .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
  }
  .btn-sm i {
    margin-right: 2px;
  }
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
  // DataTable initialization
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
      url: "{{ url('/reportes/cartera-abonos/data') }}",
      data: function (d) {
        // Enviar valores ya convertidos a mayúsculas por el frontend
        d.plaza = $('#plaza').val();
        d.tienda = $('#tienda').val();
        if ($('#period_start').length && $('#period_start').val()) {
          d.period_start = $('#period_start').val();
        }
        if ($('#period_end').length && $('#period_end').val()) {
          d.period_end = $('#period_end').val();
        }
      }
    },
    columns: [
      { data: 'plaza', className: 'text-center' },
      { data: 'tienda', className: 'text-center' },
      { data: 'fecha', className: 'text-center' },
      { data: 'fecha_vta', className: 'text-center' },
      { data: 'concepto', className: 'text-center' },
      { data: 'tipo', className: 'text-center' },
      { data: 'factura', className: 'text-center' },
      { data: 'clave', className: 'text-center' },
      { data: 'rfc' },
      { data: 'nombre' },
      { data: 'vend_clave', className: 'text-center' },
      { data: 'monto_fa', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'monto_dv', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'monto_cd', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'dias_cred', className: 'text-center' },
      { data: 'dias_vencidos', className: 'text-center' }
    ]
  });

  // Event handlers
  $('#btn_search').on('click', function() {
    dataTable.ajax.reload();
  });

  $('#btn_refresh').on('click', function() {
    dataTable.ajax.reload();
  });

  $('#btn_reset_filters').on('click', function() {
    $('#period_start').val("{{ $startDefault }}");
    $('#period_end').val("{{ $endDefault }}");
    $('#plaza').val('').removeClass('border-primary').addClass('border-secondary');
    $('#tienda').val('').removeClass('border-primary').addClass('border-secondary');
    updateCurrentPeriodDisplay();
  });

  // Export Excel
  $('#btn_excel').on('click', function() {
    const start = $('#period_start').val();
    const end = $('#period_end').val();
    const plaza = $('#plaza').val();
    const tienda = $('#tienda').val();
    
    let form = $('<form>', {
      'method': 'POST',
      'action': "{{ url('/reportes/cartera-abonos/export-excel') }}",
      'target': '_blank'
    });
    
    form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': "{{ csrf_token() }}" }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_start', 'value': start }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_end', 'value': end }));
    if (plaza) form.append($('<input>', { 'type': 'hidden', 'name': 'plaza', 'value': plaza }));
    if (tienda) form.append($('<input>', { 'type': 'hidden', 'name': 'tienda', 'value': tienda }));
    
    $('body').append(form);
    form.submit();
    form.remove();
  });

  // Export CSV
  $('#btn_csv').on('click', function() {
    const start = $('#period_start').val();
    const end = $('#period_end').val();
    const plaza = $('#plaza').val();
    const tienda = $('#tienda').val();
    
    let form = $('<form>', {
      'method': 'POST',
      'action': "{{ url('/reportes/cartera-abonos/export-csv') }}",
      'target': '_blank'
    });
    
    form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': "{{ csrf_token() }}" }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_start', 'value': start }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_end', 'value': end }));
    if (plaza) form.append($('<input>', { 'type': 'hidden', 'name': 'plaza', 'value': plaza }));
    if (tienda) form.append($('<input>', { 'type': 'hidden', 'name': 'tienda', 'value': tienda }));
    
    $('body').append(form);
    form.submit();
    form.remove();
  });

  $('#btn_pdf').on('click', function() {
    const start = $('#period_start').val();
    const end = $('#period_end').val();
    const plaza = $('#plaza').val();
    const tienda = $('#tienda').val();
    let url = "{{ url('/reportes/cartera-abonos/pdf') }}";
    url += '?period_start=' + encodeURIComponent(start);
    url += '&period_end=' + encodeURIComponent(end);
    if (plaza) url += '&plaza=' + encodeURIComponent(plaza);
    if (tienda) url += '&tienda=' + encodeURIComponent(tienda);
    window.open(url, '_blank');
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

  // Initialize
  updateCurrentPeriodDisplay();

  // Load plazas (placeholder - implement actual loading logic)
  // This would typically load from an API endpoint
  setTimeout(function() {
    $('#plaza').html('<option value="">Todas</option><option value="01">Plaza 01</option><option value="02">Plaza 02</option>');
  }, 500);

  // Validación en tiempo real cuando se pierde el foco
  $('#plaza, #tienda').on('blur', function() {
    const inputElement = this;
    const filterName = $(this).attr('id');
    const filterValue = $(this).val().trim();
    
    let isValid = true;
    let errorMessage = '';
    
    if (filterValue) {
      if (filterName === 'plaza') {
        // Plaza: 5 caracteres, mayúsculas y números
        const plazaRegex = /^[A-Z0-9]{5}$/;
        isValid = plazaRegex.test(filterValue);
        if (!isValid) {
          errorMessage = 'Formato inválido. Plaza: 5 caracteres, solo mayúsculas y números (Ej: A001)';
        }
      } else if (filterName === 'tienda') {
        // Tienda: hasta 10 caracteres, mayúculas y números
        const tiendaRegex = /^[A-Z0-9]{1,10}$/;
        isValid = tiendaRegex.test(filterValue);
        if (!isValid) {
          errorMessage = 'Formato inválido. Tienda: hasta 10 caracteres, mayúsculas y números (Ej: B001)';
        }
      }
    }
    
    if (!isValid && filterValue) {
      $(this).addClass('border-danger');
      $(this).removeClass('border-primary border-secondary');
      $(this).attr('title', errorMessage);
      setTimeout(() => {
        $(this).attr('title', 'Ingrese código ' + filterName + ' (Ej: A001 para plaza, B001 para tienda)');
        if ($(this).val().trim()) {
          if ($(this)[0].checkValidity()) {
            $(this).removeClass('border-danger');
            $(this).addClass($(this).val().trim() ? 'border-primary' : 'border-secondary');
          }
        }
      }, 3000);
    } else {
      $(this).removeClass('border-danger');
      $(this).addClass(filterValue ? 'border-primary' : 'border-secondary');
      $(this).attr('title', 'Ingrese código ' + filterName + ' (Ej: A001 para plaza, B001 para tienda)');
    }
  });

  // Mantener funcionalidad de actualización cuando se pierde el foco
  $('#plaza, #tienda').on('change', function() {
    // Convertir a mayúsculas y validar
    $(this).val($(this).val().toUpperCase());
    
    if ($(this)[0].checkValidity()) {
      $(this).removeClass('border-danger');
      $(this).addClass('border-primary');
      $(this).removeClass('border-secondary');
    } else {
      $(this).addClass('border-danger');
      $(this).removeClass('border-primary border-secondary');
    }
    
    dataTable.ajax.reload();
    updateCurrentPeriodDisplay();
  });

  // Permitir búsqueda rápida con Enter en cualquier campo
  $('#plaza, #tienda').on('keypress', function(e) {
    if (e.which === 13) { // Enter key
      e.preventDefault();
      clearTimeout(searchTimeout);
      
      const inputElement = this;
      const filterValue = $(this).val().trim();
      
      // Validar formato usando pattern HTML5
      if (filterValue && !inputElement.checkValidity()) {
        // Mostrar error visual pero permitir búsqueda (el backend procesará el formato)
        $(this).addClass('border-warning');
        $(this).removeClass('border-primary border-secondary');
        
        // Mostrar mensaje de error temporal
        const originalTitle = $(this).attr('title') || '';
        const filterName = $(this).attr('id');
        let errorMessage = '';
        
        if (filterName === 'plaza') {
          errorMessage = 'Formato: 5 caracteres, mayúsculas y números (Ej: A001)';
        } else if (filterName === 'tienda') {
          errorMessage = 'Formato: hasta 10 caracteres, mayúsculas y números (Ej: B001)';
        }
        
        $(this).attr('title', errorMessage);
        setTimeout(() => {
          $(this).attr('title', originalTitle);
          if ($(this).val().trim()) {
            $(this).removeClass('border-warning');
            $(this).addClass($(this).val().trim() ? 'border-primary' : 'border-secondary');
          } else {
            $(this).removeClass('border-warning');
            $(this).addClass('border-secondary');
          }
        }, 2000);
        return false; // Prevenir búsqueda inválida
      }
      
      clearTimeout(searchTimeout);
      dataTable.ajax.reload();
      updateCurrentPeriodDisplay();
    } else {
      $(this).removeClass('border-warning border-danger');
      $(this).addClass($(this).val().trim() ? 'border-primary' : 'border-secondary');
    }
  });

        // Limpiar filtros con ESC (manejado por el handler anterior)
        // Este código es redundante y se elimina para evitar conflictos

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
    
    let url = "{{ url('/reportes/cartera-abonos/sync') }}";
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
          // Recargar datatable
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