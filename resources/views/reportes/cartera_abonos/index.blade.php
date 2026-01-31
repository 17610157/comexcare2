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
      
      <div class="row">
        <div class="col-md-3">
          <label for="period_start" class="form-label">Periodo Inicio</label>
          <input type="date" id="period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
        </div>
        <div class="col-md-3">
          <label for="period_end" class="form-label">Periodo Fin</label>
          <input type="date" id="period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
        </div>
        <div class="col-md-3">
          <label for="plaza" class="form-label">Código Plaza</label>
          <input type="text" id="plaza" class="form-control form-control-sm border-secondary" placeholder="Ej: A001" maxlength="5" pattern="[A-Z0-9]{5}" style="text-transform: uppercase;" title="Código de 5 caracteres, letras mayúsculas y números. ENTER para buscar, ESC para limpiar.">
        </div>
        <div class="col-md-3">
          <label for="tienda" class="form-label">Código Tienda</label>
          <input type="text" id="tienda" class="form-control form-control-sm border-secondary" placeholder="Ej: B001" maxlength="10" pattern="[A-Z0-9]{10}" style="text-transform: uppercase;" title="Código de tienda con letras mayúsculas y números. ENTER para buscar, ESC para limpiar.">
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-md-12 d-flex align-items-end justify-content-end">
          <button id="btn_search" class="btn btn-success btn-sm me-2">
            <i class="fas fa-search"></i> Buscar
          </button>
          <button id="btn_refresh" class="btn btn-primary btn-sm me-2">
            <i class="fas fa-sync-alt"></i> Actualizar
          </button>
          <button id="btn_reset_filters" class="btn btn-secondary btn-sm me-2" title="Limpiar todos los filtros (ESC en campos)">
            <i class="fas fa-undo"></i> Limpiar
          </button>
          <button id="btn_excel" class="btn btn-success btn-sm me-2">
            <i class="fas fa-file-excel"></i> Excel
          </button>
          <button id="btn_csv" class="btn btn-info btn-sm me-2">
            <i class="fas fa-file-csv"></i> CSV
          </button>
          <button id="btn_pdf" class="btn btn-danger btn-sm">
            <i class="fas fa-file-pdf"></i> PDF
          </button>
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
            <th>Fecha</th>
            <th>Fecha Vta</th>
            <th>Concepto</th>
            <th>Tipo</th>
            <th>Factura</th>
            <th>Clave</th>
            <th>RFC</th>
            <th>Nombre</th>
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
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<style>
.card-header {
  border-bottom: 2px solid #dee2e6;
}
.table th {
  background-color: #f8f9fa;
  font-weight: 600;
}
.btn-sm {
  padding: 0.375rem 0.75rem;
}
.badge {
  font-size: 0.875em;
}
</style>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function() {
  // DataTable initialization
  const dataTable = $('#report-table').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 25,
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
});
</script>
@endsection