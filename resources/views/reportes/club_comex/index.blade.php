@extends('adminlte::page')
@section('title', 'Club Comex')

@section('content_header')
<h1>Club Comex</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card bg-light mb-3">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-filter"></i> Sincronización
      </h5>
    </div>
    <div class="card-body">
      @php
        $startDefault = \Carbon\Carbon::parse('first day of previous month')->toDateString();
        $endDefault = \Carbon\Carbon::parse('last day of previous month')->toDateString();
      @endphp
      
      <div class="row g-2">
        <div class="col-6 col-md-3">
          <label for="sync_type" class="form-label small mb-1">Tipo de Sincronización</label>
          <select id="sync_type" class="form-control form-control-sm">
            <option value="lastMonth">Mes Anterior</option>
            <option value="lastDays">Últimos Días</option>
            <option value="day">Día Específico</option>
            <option value="period">Rango de Fechas</option>
            <option value="full">Completo</option>
          </select>
        </div>
        <div class="col-6 col-md-2" id="lastDaysContainer" style="display: none;">
          <label for="last_days" class="form-label small mb-1">Días</label>
          <input type="number" id="last_days" class="form-control form-control-sm" value="30" min="1">
        </div>
        <div class="col-6 col-md-2" id="dayContainer" style="display: none;">
          <label for="sync_day" class="form-label small mb-1">Fecha</label>
          <input type="date" id="sync_day" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-6 col-md-2" id="periodStartContainer" style="display: none;">
          <label for="period_start" class="form-label small mb-1">Inicio</label>
          <input type="date" id="period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
        </div>
        <div class="col-6 col-md-2" id="periodEndContainer" style="display: none;">
          <label for="period_end" class="form-label small mb-1">Fin</label>
          <input type="date" id="period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
          <button id="btn_sync" class="btn btn-warning btn-sm">
            <i class="fas fa-database"></i> <span class="d-none d-sm-inline">Sincronizar Datos</span>
          </button>
          <span id="sync_status" class="badge bg-secondary align-self-center"></span>
        </div>
      </div>
    </div>
  </div>

  <div class="card bg-light mb-3">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="fas fa-search"></i> Búsqueda por CCampo3
      </h5>
    </div>
    <div class="card-body">
      <div class="row g-2">
        <div class="col-12 col-md-4">
          <label for="search_ccampo3" class="form-label small mb-1">CCampo3 / Cliente</label>
          <input type="text" id="search_ccampo3" class="form-control form-control-sm" placeholder="Ingrese CCampo3 o cliente">
        </div>
        <div class="col-6 col-md-2">
          <label for="search_period_start" class="form-label small mb-1">Fecha Inicio</label>
          <input type="date" id="search_period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
        </div>
        <div class="col-6 col-md-2">
          <label for="search_period_end" class="form-label small mb-1">Fecha Fin</label>
          <input type="date" id="search_period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
          <button id="btn_search" class="btn btn-primary btn-sm">
            <i class="fas fa-search"></i> Buscar
          </button>
          <button id="btn_export" class="btn btn-success btn-sm">
            <i class="fas fa-file-csv"></i> Exportar CSV
          </button>
          <span id="search_status" class="badge bg-secondary align-self-center"></span>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Resultados de Búsqueda</h3>
    </div>
    <div class="card-body">
      <table id="searchResultsTable" class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Plaza</th>
            <th>Tienda</th>
            <th>CCampo3</th>
            <th>Clientes</th>
            <th>RFC</th>
            <th>Cve Con</th>
            <th>Redenciones</th>
            <th>Acumulaciones</th>
            <th>Total Redenciones</th>
            <th>Total Acumulaciones</th>
          </tr>
        </thead>
        <tbody id="searchResultsBody">
          <tr>
            <td colspan="10" class="text-center">Sin resultados</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-header">
      <h3 class="card-title">Resultados de Sincronización</h3>
    </div>
    <div class="card-body">
      <table id="resultsTable" class="table table-bordered table-striped table-hover">
        <thead>
          <tr>
            <th>Tabla</th>
            <th>Registros Insertados</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>redenciones_clubcomex</td>
            <td id="count_redenciones">-</td>
            <td id="status_redenciones">-</td>
          </tr>
          <tr>
            <td>acumulaciones_clubcomex</td>
            <td id="count_acumulaciones">-</td>
            <td id="status_acumulaciones">-</td>
          </tr>
          <tr>
            <td>acumulaciones_clubcomex_ia</td>
            <td id="count_acumulaciones_ia">-</td>
            <td id="status_acumulaciones_ia">-</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@section('js')
<script>
$(document).ready(function() {
  $('#sync_type').change(function() {
    const type = $(this).val();
    $('#lastDaysContainer, #dayContainer, #periodStartContainer, #periodEndContainer').hide();
    
    switch(type) {
      case 'lastDays':
        $('#lastDaysContainer').show();
        break;
      case 'day':
        $('#dayContainer').show();
        break;
      case 'period':
        $('#periodStartContainer, #periodEndContainer').show();
        break;
    }
  });

  $('#btn_sync').click(function() {
    const type = $('#sync_type').val();
    const lastDays = $('#last_days').val();
    const day = $('#sync_day').val();
    const periodStart = $('#period_start').val();
    const periodEnd = $('#period_end').val();
    
    let url = "{{ url('/reportes/club-comex/sync') }}";
    let data = { type: type };
    
    if (type === 'lastDays') {
      data.lastDays = lastDays;
    } else if (type === 'day') {
      data.day = day;
    } else if (type === 'period') {
      data.periodStart = periodStart;
      data.periodEnd = periodEnd;
    }

    $('#btn_sync').prop('disabled', true);
    $('#sync_status').text('Sincronizando...').removeClass('bg-success bg-danger').addClass('bg-warning');

    $.ajax({
      url: url,
      method: 'POST',
      data: data,
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function(response) {
        $('#btn_sync').prop('disabled', false);
        
        if (response.success) {
          $('#sync_status').text('Completado').removeClass('bg-warning bg-danger').addClass('bg-success');
          
          if (response.results) {
            if (response.results.redenciones) {
              $('#count_redenciones').text(response.results.redenciones.count);
              $('#status_redenciones').html('<span class="badge bg-success">OK</span>');
            }
            if (response.results.acumulaciones) {
              $('#count_acumulaciones').text(response.results.acumulaciones.count);
              $('#status_acumulaciones').html('<span class="badge bg-success">OK</span>');
            }
            if (response.results.acumulaciones_ia) {
              $('#count_acumulaciones_ia').text(response.results.acumulaciones_ia.count);
              $('#status_acumulaciones_ia').html('<span class="badge bg-success">OK</span>');
            }
          }
        } else {
          $('#sync_status').text('Error').removeClass('bg-warning bg-success').addClass('bg-danger');
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr) {
        $('#btn_sync').prop('disabled', false);
        $('#sync_status').text('Error').removeClass('bg-warning bg-success').addClass('bg-danger');
        alert('Error: ' + (xhr.responseJSON?.message || 'Error desconocido'));
      }
    });
  });

  $('#btn_search').click(function() {
    const ccampo3 = $('#search_ccampo3').val();
    const periodStart = $('#search_period_start').val();
    const periodEnd = $('#search_period_end').val();

    console.log('Buscando:', ccampo3, periodStart, periodEnd);

    if (!ccampo3) {
      alert('Por favor ingrese un valor para CCampo3');
      return;
    }

    $('#btn_search').prop('disabled', true);
    $('#search_status').text('Buscando...').removeClass('bg-success bg-danger').addClass('bg-warning');

    $.ajax({
      url: "{{ url('/reportes/club-comex/search') }}",
      method: 'POST',
      data: {
        ccampo3: ccampo3,
        period_start: periodStart,
        period_end: periodEnd
      },
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      success: function(response) {
        console.log('Respuesta:', response);
        $('#btn_search').prop('disabled', false);
        
        if (response.success) {
          $('#search_status').text('Completado').removeClass('bg-warning bg-danger').addClass('bg-success');
          
          const tbody = $('#searchResultsBody');
          tbody.empty();
          
          if (response.data && response.data.length > 0) {
            response.data.forEach(function(row) {
              tbody.append(`
                <tr>
                  <td>${row.plaza || ''}</td>
                  <td>${row.tienda || ''}</td>
                  <td>${row.ccampo3 || ''}</td>
                  <td>${row.cantidad_clientes || 0}</td>
                  <td>${row.cantidad_rfc || 0}</td>
                  <td>${row.cantidad_cve_con || 0}</td>
                  <td>${row.cantidad_redenciones || 0}</td>
                  <td>${row.cantidad_acumulaciones || 0}</td>
                  <td>${parseFloat(row.total_redenciones || 0).toFixed(2)}</td>
                  <td>${parseFloat(row.total_acumulaciones || 0).toFixed(2)}</td>
                </tr>
              `);
            });
          } else {
            tbody.append('<tr><td colspan="10" class="text-center">Sin resultados</td></tr>');
          }
        } else {
          $('#search_status').text('Error').removeClass('bg-warning bg-success').addClass('bg-danger');
          alert('Error: ' + response.message);
        }
      },
      error: function(xhr, status, error) {
        console.log('Error:', status, error);
        console.log('Response:', xhr.responseText);
        $('#btn_search').prop('disabled', false);
        $('#search_status').text('Error').removeClass('bg-warning bg-success').addClass('bg-danger');
        alert('Error: ' + (xhr.responseJSON?.message || error || 'Error desconocido'));
      }
    });
  });

  $('#btn_export').click(function() {
    const ccampo3 = $('#search_ccampo3').val();
    const periodStart = $('#search_period_start').val();
    const periodEnd = $('#search_period_end').val();

    if (!ccampo3) {
      alert('Por favor ingrese un valor para CCampo3');
      return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = "{{ url('/reportes/club-comex/export-csv') }}";
    form.target = '_blank';

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);

    const ccampo3Input = document.createElement('input');
    ccampo3Input.type = 'hidden';
    ccampo3Input.name = 'ccampo3';
    ccampo3Input.value = ccampo3;
    form.appendChild(ccampo3Input);

    const periodStartInput = document.createElement('input');
    periodStartInput.type = 'hidden';
    periodStartInput.name = 'period_start';
    periodStartInput.value = periodStart;
    form.appendChild(periodStartInput);

    const periodEndInput = document.createElement('input');
    periodEndInput.type = 'hidden';
    periodEndInput.name = 'period_end';
    periodEndInput.value = periodEnd;
    form.appendChild(periodEndInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
  });
});
</script>
@endsection
