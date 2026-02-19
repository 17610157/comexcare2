@extends('adminlte::page')
@section('title', 'Cartera Abonos')

@section('content_header')
<h1>Cartera - Abonos (Mes Anterior)</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header">
      <div class="row">
        <div class="col-md-4">
          <label for="plaza" class="form-label">Plaza</label>
          <input type="text" id="plaza" class="form-control form-control-sm" placeholder="Ej: A001" maxlength="5" pattern="[A-Z0-9]{5}" title="5 caracteres: letras mayúsculas y números">
        </div>
        <div class="col-md-4">
          <label for="tienda" class="form-label">Tienda</label>
          <input type="text" id="tienda" class="form-control form-control-sm" placeholder="Ej: B001" maxlength="10" pattern="[A-Z0-9]{1,10}" title="Hasta 10 caracteres: letras mayúsculas y números">
        </div>
        <div class="col-md-4 align-self-end d-flex align-items-end">
          @hasPermission('reportes.cartera_abonos.filtrar')
          <button id="btn_search" class="btn btn-success btn-sm ml-auto">Buscar</button>
          @endhasPermission
          @hasPermission('reportes.cartera_abonos.ver')
          <button id="btn_refresh" class="btn btn-primary btn-sm ml-2">Actualizar</button>
          @endhasPermission
          @hasPermission('reportes.cartera_abonos.filtrar')
          <button id="btn_reset_filters" class="btn btn-secondary btn-sm ml-2">Limpiar filtros</button>
          @endhasPermission
          @hasPermission('reportes.cartera_abonos.exportar')
          <button id="btn_pdf" class="btn btn-info btn-sm ml-2">Exportar PDF</button>
          @endhasPermission
        </div>
      </div>
    </div>
      <div class="card-body p-0">
      <div class="row mb-2 mx-0 px-0" style="padding:0 0 6px 0;" id="period_range_row_top">
        <div class="col-md-6">
          <label for="period_range" class="form-label small mb-1">Rango</label>
          <select id="period_range" class="form-control form-control-sm" aria-label="Rango de periodo">
            <option value="previous_month" selected>Mes anterior</option>
            <option value="this_month">Este mes</option>
            <option value="last_7_days">Últimos 7 días</option>
            <option value="last_30_days">Últimos 30 días</option>
            <option value="year_to_date">Año actual</option>
          </select>
        </div>
      </div>
      <div class="row mb-2 mx-0 px-0" style="padding:0 0 6px 0;" id="current_period_display_row">
        <div class="col-12">
          <span id="current_period_display" class="text-muted small"></span>
        </div>
      </div>
      @php
        $startDefault = \Carbon\Carbon::parse('first day of previous month')->toDateString();
        $endDefault = \Carbon\Carbon::parse('last day of previous month')->toDateString();
      @endphp
      <div class="row mb-2 mx-0 px-0" style="padding:0 0 6px 0;">
        <div class="col-md-6">
          <label for="period_start" class="form-label small mb-1">Periodo Inicio</label>
          <input type="date" id="period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
        </div>
        <div class="col-md-6">
          <label for="period_end" class="form-label small mb-1">Periodo Fin</label>
          <input type="date" id="period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
        </div>
      </div>
      <table id="report-table" class="table table-bordered table-hover mb-0" style="width:100%">
        <thead>
          <tr>
            <th>Plaza</th>
            <th>Tienda</th>
            <th>Fecha</th>
            <th>Fecha_vta</th>
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
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<style>
.code-filter-tooltip {
  position: absolute;
  background: #dc3545;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  z-index: 1050;
  white-space: nowrap;
  pointer-events: none;
}
.code-filter-input {
  position: relative;
}
</style>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
  console.log('CarteraAbonos: initializing with code filters');
  
  // Store for plaza and tienda data
  let plazaData = [];
  let tiendaData = [];
  
  // Load data for validation
  $.get('{{ url("/reportes/cartera-abonos/data") }}', { length: 1, start: 0 }, function(response) {
    // This would ideally be a dedicated endpoint, but we'll extract from first page
    console.log('Data loaded for validation');
  });
  
  // Helper functions
  function showInputError(input, message) {
    const existingTooltip = document.getElementById('tooltip-' + input.id);
    if (existingTooltip) existingTooltip.remove();
    
    const tooltip = document.createElement('div');
    tooltip.id = 'tooltip-' + input.id;
    tooltip.className = 'code-filter-tooltip';
    tooltip.textContent = message;
    tooltip.style.top = (input.offsetTop + input.offsetHeight + 5) + 'px';
    tooltip.style.left = input.offsetLeft + 'px';
    
    input.parentElement.appendChild(tooltip);
    
    setTimeout(() => {
      if (document.getElementById('tooltip-' + input.id)) {
        tooltip.remove();
      }
    }, 3000);
  }
  
  function validateCodeInput(input, pattern, errorMessage) {
    const value = input.value.toUpperCase();
    input.value = value; // Force uppercase
    
    // Remove validation classes
    input.classList.remove('is-valid', 'is-warning', 'is-invalid');
    
    // Remove any existing tooltip
    const existingTooltip = document.getElementById('tooltip-' + input.id);
    if (existingTooltip) existingTooltip.remove();
    
    if (value === '') {
      return true; // Empty is valid
    }
    
    // Test against pattern
    if (!new RegExp(pattern).test(value)) {
      input.classList.add('is-invalid');
      showInputError(input, errorMessage);
      return false;
    }
    
    input.classList.add('is-valid');
    return true;
  }
  
  // Enhanced input handlers
  $('#plaza, #tienda').on('input', function() {
    const $this = $(this);
    const value = $this.val();
    const id = $this.attr('id');
    
    // Force uppercase and limit characters
    $this.val(value.toUpperCase().replace(/[^A-Z0-9]/g, ''));
    
    // Debounced validation
    clearTimeout($this.data('timer'));
    $this.data('timer', setTimeout(() => {
      if (id === 'plaza') {
        validateCodeInput(this, '^[A-Z0-9]{5}$', 'Formato: 5 caracteres, letras mayúsculas y números (ej: A001)');
      } else {
        validateCodeInput(this, '^[A-Z0-9]{1,10}$', 'Formato: hasta 10 caracteres, letras mayúsculas y números (ej: B001)');
      }
    }, 500));
  });
  
  // Enter key to search
  $('#plaza, #tienda').on('keypress', function(e) {
    if (e.which === 13) { // Enter key
      e.preventDefault();
      performSearch();
    }
  });
  
  // Function to perform search with validation
  function performSearch() {
    const plaza = $('#plaza').val();
    const tienda = $('#tienda').val();
    
    // Validate plaza if provided
    if (plaza && !new RegExp('^[A-Z0-9]{5}$').test(plaza)) {
      validateCodeInput($('#plaza')[0], '^[A-Z0-9]{5}$', 'Formato: 5 caracteres, letras mayúsculas y números (ej: A001)');
      return;
    }
    
    // Validate tienda if provided  
    if (tienda && !new RegExp('^[A-Z0-9]{1,10}$').test(tienda)) {
      validateCodeInput($('#tienda')[0], '^[A-Z0-9]{1,10}$', 'Formato: hasta 10 caracteres, letras mayúsculas y números (ej: B001)');
      return;
    }
    
    // Reload data if validation passes
    $('#report-table').DataTable().ajax.reload();
    updateCurrentPeriodDisplay();
  }
  
  // ESC key to clear
  $('#plaza, #tienda').on('keydown', function(e) {
    if (e.which === 27) { // ESC key
      $(this).val('').removeClass('is-valid is-warning is-invalid');
    }
  });
  
  // Validate on blur
  $('#plaza, #tienda').on('blur', function() {
    const id = $(this).attr('id');
    if (id === 'plaza') {
      validateCodeInput(this, '^[A-Z0-9]{5}$', 'Formato: 5 caracteres, letras mayúsculas y números (ej: A001)');
    } else {
      validateCodeInput(this, '^[A-Z0-9]{1,10}$', 'Formato: hasta 10 caracteres, letras mayúsculas y números (ej: B001)');
    }
  });

  try {
    console.log('Initial period_start:', $('#period_start').val(), 'period_end:', $('#period_end').val(), 'period_range:', $('#period_range').val());
  } catch (e) {
    // ignore
  }
  $('#report-table').DataTable({
    processing: true,
    serverSide: true,
        ajax: {
          url: "{{ url('/reportes/cartera-abonos/data') }}",
      data: function (d) {
        d.plaza = $('#plaza').val();
        d.tienda = $('#tienda').val();
        // Optional period filters if present in the UI (renamed to period_start/end)
        if (typeof $('#period_start').val === 'function' && $('#period_start').length && $('#period_start').val()) {
          d.period_start = $('#period_start').val();
        }
        if (typeof $('#period_end').val === 'function' && $('#period_end').length && $('#period_end').val()) {
          d.period_end = $('#period_end').val();
        }
      }
        },
    columns: [
      { data: 'plaza' },
      { data: 'tienda' },
      { data: 'fecha' },
      { data: 'fecha_vta' },
      { data: 'concepto' },
      { data: 'tipo' },
      { data: 'factura' },
      { data: 'clave' },
      { data: 'rfc' },
      { data: 'nombre' },
      { data: 'monto_fa' },
      { data: 'monto_dv' },
      { data: 'monto_cd' },
      { data: 'dias_cred' },
      { data: 'dias_vencidos' }
    ]
  });

  // Search button functionality
  $('#btn_search').on('click', function() {
    performSearch();
  });
  
  // Refresh button functionality - same as search
  $('#btn_refresh').on('click', function() {
    performSearch();
  });
  // Export to PDF using current filters
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
  // Reset filters to defaults and reload
  $('#btn_reset_filters').on('click', function() {
    // Restore date filters to defaults
    $('#period_start').val("{{ $startDefault }}");
    $('#period_end').val("{{ $endDefault }}");
    // Reset range selector to previous_month and apply reset range
    $('#period_range').val('previous_month');
    $('#period_range').trigger('change');
    // Clear other filters
    $('#plaza').val('');
    $('#tienda').val('');
    updateCurrentPeriodDisplay();
  });
  // Reload data when period filters change
  $('#period_start, #period_end').on('change', function() {
    $('#report-table').DataTable().ajax.reload();
    updateCurrentPeriodDisplay();
  });
  // Apply predefined period ranges from the select
  function formatDate(d) {
    const pad = (n) => (n < 10 ? '0' + n : n);
    const year = d.getFullYear();
    const month = pad(d.getMonth() + 1);
    const day = pad(d.getDate());
    return year + '-' + month + '-' + day;
  }
  function applyPeriodRange(range) {
    const now = new Date();
    let start = null, end = null;
    switch (range) {
      case 'this_month': {
        start = new Date(now.getFullYear(), now.getMonth(), 1);
        end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        break;
      }
      case 'previous_month': {
        const m = now.getMonth();
        const y = now.getFullYear();
        start = new Date(y, m - 1, 1);
        end = new Date(y, m - 1, 0);
        break;
      }
      case 'last_7_days': {
        end = new Date(now);
        start = new Date(now);
        start.setDate(now.getDate() - 6);
        break;
      }
      case 'last_30_days': {
        end = new Date(now);
        start = new Date(now);
        start.setDate(now.getDate() - 29);
        break;
      }
      case 'year_to_date': {
        start = new Date(now.getFullYear(), 0, 1);
        end = now;
        break;
      }
      default:
        return;
    }
    if (start) document.getElementById('period_start').value = formatDate(start);
    if (end) document.getElementById('period_end').value = formatDate(end);
    $('#report-table').DataTable().ajax.reload();
    updateCurrentPeriodDisplay();
  }
  // Initialize and bind
  $('#period_range').on('change', function(){ applyPeriodRange(this.value); });
  // Apply initial range on load
  applyPeriodRange($('#period_range').val());
  // Initialize current period display
  function updateCurrentPeriodDisplay(){
    const s = $('#period_start').val();
    const e = $('#period_end').val();
    $('#current_period_display').text('Periodo: ' + s + ' a ' + e);
    console.log('Periodo display actualizado:', s, 'a', e);
  }
  updateCurrentPeriodDisplay();
});
</script>
@endsection
