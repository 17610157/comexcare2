@extends('adminlte::page')
@section('title', 'Cortes')

@section('content_header')
<h1>Cortes</h1>
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
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Plazas</label>
          <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
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
          <label class="form-label small mb-1">Tiendas</label>
          <div class="border rounded p-2" style="max-height: 120px; overflow-y: auto;">
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
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Fecha Desde</label>
          <input type="date" id="fecha_desde" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-1">Fecha Hasta</label>
          <input type="date" id="fecha_hasta" class="form-control form-control-sm">
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="total_cortes" class="badge bg-info align-self-center"></span>
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
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
      <h5 class="mb-0">
        <i class="fas fa-money-bill-wave"></i> Cortes Registrados
      </h5>
      <div>
        <span id="total_registros" class="badge bg-light text-dark"></span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="cortes-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Fecha Corte</th>
              <th>Tienda</th>
              <th>Plaza</th>
              <th class="text-right">Monto Contado</th>
              <th class="text-right">Monto Crédito</th>
              <th class="text-right">Total</th>
              <th>Fecha Registro</th>
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(function() {
  const money = function(value) {
    const num = parseFloat(value || 0);
    return '$ ' + num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  const dataTable = $('#cortes-table').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
      url: "{{ url('/reportes/cortes/data') }}",
      type: "GET",
      data: function (d) {
        const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
        const tiendasSeleccionadas = $('.tienda-checkbox:checked').map(function() { return $(this).val(); }).get();

        if (plazasSeleccionadas.length > 0) {
          d.plaza = plazasSeleccionadas;
        }
        if (tiendasSeleccionadas.length > 0) {
          d.tienda = tiendasSeleccionadas;
        }
        if ($('#fecha_desde').val()) {
          d.fecha_desde = $('#fecha_desde').val();
        }
        if ($('#fecha_hasta').val()) {
          d.fecha_hasta = $('#fecha_hasta').val();
        }
      },
      dataSrc: function(json) {
        $('#total_cortes').text('Total: ' + json.recordsTotal + ' cortes');
        $('#total_registros').text(json.recordsTotal + ' registros');
        return json.data;
      },
      error: function(xhr, error, thrown) {
        console.log('Error:', xhr.responseText);
        alert('Error cargando datos: ' + xhr.status);
      }
    },
    columns: [
      { data: 'id', className: 'text-center' },
      { data: 'fecha_corte', className: 'text-center' },
      { data: 'clave_tienda', className: 'text-center' },
      { data: 'plaza', className: 'text-center' },
      { data: 'monto_contado', className: 'text-right', render: function(data) { return money(data); } },
      { data: 'monto_credito', className: 'text-right', render: function(data) { return money(data); } },
      { data: 'total', className: 'text-right font-weight-bold', render: function(data) { return money(data); } },
      { data: 'fecha_registro', className: 'text-center' }
    ],
    order: [[1, 'desc']]
  });

  $('#btn_search').on('click', function() { dataTable.ajax.reload(); });
  $('#btn_refresh').on('click', function() { dataTable.ajax.reload(); });

  $('#btn_reset_filters').on('click', function() {
    $('.plaza-checkbox').prop('checked', false);
    $('.tienda-checkbox').prop('checked', false);
    $('#select_all_plazas').prop('checked', false);
    $('#select_all_tiendas').prop('checked', false);
    $('#fecha_desde').val('');
    $('#fecha_hasta').val('');
    dataTable.ajax.reload();
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
});
</script>
@endsection
