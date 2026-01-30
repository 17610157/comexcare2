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
          <select id="plaza" class="form-control form-control-sm"></select>
        </div>
        <div class="col-md-4">
          <label for="tienda" class="form-label">Tienda</label>
          <select id="tienda" class="form-control form-control-sm"></select>
        </div>
        <div class="col-md-4 align-self-end d-flex align-items-end">
          <button id="btn_refresh" class="btn btn-primary btn-sm ml-auto">Actualizar</button>
          <button id="btn_reset_filters" class="btn btn-secondary btn-sm ml-2">Limpiar filtros</button>
          <button id="btn_pdf" class="btn btn-info btn-sm ml-2">Exportar PDF</button>
        </div>
      </div>
    </div>
    <div class="card-body p-0">
      @php
        $startDefault = \Carbon\Carbon::parse('first day of previous month')->toDateString();
        $endDefault = \Carbon\Carbon::parse('last day of previous month')->toDateString();
      @endphp
      <div class="row mb-2 mx-0 px-0" style="padding:0 0 6px 0;"><div class="col-md-6">
        <label for="period_start" class="form-label small mb-1">Periodo Inicio</label>
        <input type="date" id="period_start" class="form-control form-control-sm" value="{{ $startDefault }}">
      </div>
      <div class="col-md-6">
        <label for="period_end" class="form-label small mb-1">Periodo Fin</label>
        <input type="date" id="period_end" class="form-control form-control-sm" value="{{ $endDefault }}">
      </div></div>
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
        <div class="col-12"><span id="current_period_display" class="text-muted small"></span></div>
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
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
  $('#report-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ url('/reportes/cartera-abonos/data') }}",
      data: function (d) {
        d.plaza = $('#plaza').val();
        d.tienda = $('#tienda').val();
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

  $('#btn_refresh').on('click', function() {
    $('#report-table').DataTable().ajax.reload();
  });
  // Export PDF with current filters
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
    $('#period_start').val("{{ $startDefault }}");
    $('#period_end').val("{{ $endDefault }}");
    $('#period_range').val('previous_month');
    $('#period_range').trigger('change');
    $('#plaza').val('');
    $('#tienda').val('');
  });
  // Period filters changes
  $('#period_start, #period_end').on('change', function() {
    $('#report-table').DataTable().ajax.reload();
    updateCurrentPeriodDisplay();
  });
  // Predefined ranges
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
  $('#period_range').on('change', function(){ applyPeriodRange(this.value); });
  applyPeriodRange($('#period_range').val());
  function updateCurrentPeriodDisplay(){
    const s = $('#period_start').val();
    const e = $('#period_end').val();
    $('#current_period_display').text('Periodo: ' + s + ' a ' + e);
  }
  updateCurrentPeriodDisplay();
});
</script>
@endsection
