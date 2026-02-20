@extends('adminlte::page')
@section('title', 'Reporte de Vendedores')

@section('content_header')
<h1>Reporte de Vendedores</h1>
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
          <label class="form-label small mb-1">Tiendas</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_tiendas" class="form-check-input">
              <label for="select_all_tiendas" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($tiendas as $tienda)
            <div class="form-check">
              <input type="checkbox" name="tienda[]" value="{{ $tienda }}" id="tienda_{{ $tienda }}" class="form-check-input tienda-checkbox">
              <label for="tienda_{{ $tienda }}" class="form-check-label">{{ $tienda }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <div class="col-12 col-md-4">
          <label for="vendedor" class="form-label small mb-1">Vendedor</label>
          <input type="text" id="vendedor" class="form-control form-control-sm border-secondary" placeholder="Clave vendedor">
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="info_status" class="badge bg-secondary align-self-center"></span>
          </div>
          <div class="d-flex gap-1 flex-wrap">
            @hasPermission('reportes.vendedores.ver')
            <button id="btn_search" class="btn btn-success btn-sm">
              <i class="fas fa-search"></i> <span class="d-none d-sm-inline">Buscar</span>
            </button>
            @endhasPermission
            @hasPermission('reportes.vendedores.ver')
            <button id="btn_refresh" class="btn btn-primary btn-sm">
              <i class="fas fa-sync-alt"></i> <span class="d-none d-sm-inline">Actualizar</span>
            </button>
            @endhasPermission
            @hasPermission('reportes.vendedores.ver')
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm">
              <i class="fas fa-undo"></i> <span class="d-none d-sm-inline">Limpiar</span>
            </button>
            @endhasPermission
            @hasPermission('reportes.vendedores.editar')
            <button id="btn_csv" class="btn btn-info btn-sm">
              <i class="fas fa-file-csv"></i> <span class="d-none d-sm-inline">CSV</span>
            </button>
            @endhasPermission
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
              <th>#</th>
              <th>Tienda-Vendedor</th>
              <th>Vendedor-Día</th>
              <th>Plaza Ajustada</th>
              <th>Tienda</th>
              <th>Vendedor</th>
              <th>Fecha</th>
              <th class="text-right">Venta Total</th>
              <th class="text-right">Devolución</th>
              <th class="text-right">Venta Neta</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
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
      url: "{{ url('/reportes/vendedores/data') }}",
      data: function (d) {
        const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
        const tiendasSeleccionadas = $('.tienda-checkbox:checked').map(function() { return $(this).val(); }).get();
        
        if (plazasSeleccionadas.length > 0) {
          d.plaza = plazasSeleccionadas;
        }
        if (tiendasSeleccionadas.length > 0) {
          d.tienda = tiendasSeleccionadas;
        }
        
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
      { data: 'no', className: 'text-center' },
      { data: 'tienda_vendedor', className: 'text-center' },
      { data: 'vendedor_dia', className: 'text-center' },
      { data: 'plaza_ajustada', className: 'text-center' },
      { data: 'ctienda', className: 'text-center' },
      { data: 'vend_clave', className: 'text-center' },
      { data: 'fecha', className: 'text-center' },
      { data: 'venta_total', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'devolucion', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
      { data: 'venta_neta', className: 'text-end', render: $.fn.dataTable.render.number(',', '.', 2, '$') }
    ]
  });

  $('#btn_search').on('click', function() { dataTable.ajax.reload(); });
  $('#btn_refresh').on('click', function() { dataTable.ajax.reload(); });

  $('#btn_reset_filters').on('click', function() {
    $('#period_start').val("{{ $startDefault }}");
    $('#period_end').val("{{ $endDefault }}");
    $('.plaza-checkbox').prop('checked', false);
    $('.tienda-checkbox').prop('checked', false);
    $('#select_all_plazas').prop('checked', false);
    $('#select_all_tiendas').prop('checked', false);
    $('#vendedor').val('');
    updateCurrentPeriodDisplay();
  });

  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('#select_all_tiendas').on('change', function() {
    $('.tienda-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('.plaza-checkbox, .tienda-checkbox').on('change', function() {
    dataTable.ajax.reload();
  });

  $('#period_start, #period_end').on('change', function() {
    dataTable.ajax.reload();
    updateCurrentPeriodDisplay();
  });

  $('#vendedor').on('keyup', function(e) {
    if(e.key === 'Enter' || $(this).val() === '') {
      dataTable.ajax.reload();
    }
  });

  $('#btn_csv').on('click', function() {
    const start = $('#period_start').val();
    const end = $('#period_end').val();
    const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
    const tiendasSeleccionadas = $('.tienda-checkbox:checked').map(function() { return $(this).val(); }).get();
    const vendedor = $('#vendedor').val();
    
    let form = $('<form>', {
      'method': 'POST',
      'action': "{{ url('/reportes/vendedores/export-csv') }}",
      'target': '_blank'
    });
    
    form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': "{{ csrf_token() }}" }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_start', 'value': start }));
    form.append($('<input>', { 'type': 'hidden', 'name': 'period_end', 'value': end }));
    plazasSeleccionadas.forEach(function(val) {
      form.append($('<input>', { 'type': 'hidden', 'name': 'plaza[]', 'value': val }));
    });
    tiendasSeleccionadas.forEach(function(val) {
      form.append($('<input>', { 'type': 'hidden', 'name': 'tienda[]', 'value': val }));
    });
    if (vendedor) form.append($('<input>', { 'type': 'hidden', 'name': 'vendedor', 'value': vendedor }));
    
    $('body').append(form);
    form.submit();
    form.remove();
  });

  function updateCurrentPeriodDisplay(){
    const s = $('#period_start').val();
    const e = $('#period_end').val();
    $('#current_period_display').text('Periodo: ' + s + ' a ' + e);
  }

  updateCurrentPeriodDisplay();
});
</script>
@endsection
