@extends('adminlte::page')
@section('title', 'Vales')

@section('content_header')
<h1>Vales</h1>
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
          <label class="form-label small mb-1">Procedencia</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_procedencia" class="form-check-input">
              <label for="select_all_procedencia" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($procedencias as $proc)
            <div class="form-check">
              <input type="checkbox" name="procedencia[]" value="{{ $proc }}" id="proc_{{ $proc }}" class="form-check-input procedencia-checkbox">
              <label for="proc_{{ $proc }}" class="form-check-label">{{ $proc }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tiendas</label>
          <div class="border rounded p-2" style="max-height: 100px; overflow-y: auto;">
            <div class="form-check">
              <input type="checkbox" id="select_all_almacenes" class="form-check-input">
              <label for="select_all_almacenes" class="form-check-label font-weight-bold"><strong>Todas</strong></label>
            </div>
            @foreach($tiendas as $tienda)
            <div class="form-check">
              <input type="checkbox" name="almacen[]" value="{{ $tienda }}" id="almacen_{{ $tienda }}" class="form-check-input almacen-checkbox">
              <label for="almacen_{{ $tienda }}" class="form-check-label">{{ $tienda }}</label>
            </div>
            @endforeach
          </div>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Tipo Movimiento</label>
          <select id="tipo_movim" class="form-control form-control-sm">
            <option value="">Todos</option>
            @foreach($tiposMovim as $tipo)
            <option value="{{ $tipo }}">{{ $tipo }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Fecha Desde</label>
          <input type="date" id="fecha_desde" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">Fecha Hasta</label>
          <input type="date" id="fecha_hasta" class="form-control form-control-sm">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small mb-1">No. Consecutivo</label>
          <input type="text" id="no_consec" class="form-control form-control-sm" placeholder="Ej: 000123">
        </div>
      </div>

      <div class="row mt-3">
        <div class="col-12 d-flex flex-wrap gap-2 align-items-center justify-content-between">
          <div class="d-flex gap-2 flex-wrap">
            <span id="total_vales" class="badge bg-info align-self-center"></span>
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
        <i class="fas fa-file-invoice"></i> Vales Registrados
      </h5>
      <div>
        <span id="total_registros" class="badge bg-light text-dark"></span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="vales-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Tipo Mov</th>
              <th>No. Consec</th>
              <th>Fecha</th>
              <th>Cve Pro/Cl</th>
              <th>Descripción</th>
              <th>E/S</th>
              <th>Tienda</th>
              <th>Plaza</th>
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
  const dataTable = $('#vales-table').DataTable({
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
      url: "{{ url('/reportes/vales/data') }}",
      type: "GET",
      data: function (d) {
        const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
        const procedenciasSeleccionadas = $('.procedencia-checkbox:checked').map(function() { return $(this).val(); }).get();
        const almacenesSeleccionados = $('.almacen-checkbox:checked').map(function() { return $(this).val(); }).get();

        if (plazasSeleccionadas.length > 0) {
          d.plaza = plazasSeleccionadas;
        }
        if (procedenciasSeleccionadas.length > 0) {
          d.procedencia = procedenciasSeleccionadas;
        }
        if (almacenesSeleccionados.length > 0) {
          d.almacen = almacenesSeleccionados;
        }
        if ($('#tipo_movim').val()) {
          d.tipo_movim = $('#tipo_movim').val();
        }
        if ($('#fecha_desde').val()) {
          d.fecha_desde = $('#fecha_desde').val();
        }
        if ($('#fecha_hasta').val()) {
          d.fecha_hasta = $('#fecha_hasta').val();
        }
        if ($('#no_consec').val()) {
          d.no_consec = $('#no_consec').val();
        }
      },
      dataSrc: function(json) {
        $('#total_vales').text('Total: ' + json.recordsTotal + ' vales');
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
      { data: 'tipo_movim', className: 'text-center' },
      { data: 'no_consec', className: 'text-center' },
      { data: 'fecha', className: 'text-center' },
      { data: 'cve_pro_cl', className: 'text-center' },
      { data: 'desc_mov', className: 'text-left' },
      { data: 'ent_sal', className: 'text-center', render: function(data) {
        if (data === 'E') return '<span class="badge bg-success">E</span>';
        if (data === 'S') return '<span class="badge bg-danger">S</span>';
        return '<span class="badge bg-secondary">'+data+'</span>';
      }},
      { data: 'tienda', className: 'text-center' },
      { data: 'plaza', className: 'text-center' },
    ]
  });

  $('#btn_search').on('click', function() { dataTable.ajax.reload(); });
  $('#btn_refresh').on('click', function() { dataTable.ajax.reload(); });

  $('#btn_reset_filters').on('click', function() {
    $('.plaza-checkbox').prop('checked', false);
    $('.procedencia-checkbox').prop('checked', false);
    $('.almacen-checkbox').prop('checked', false);
    $('#select_all_plazas').prop('checked', false);
    $('#select_all_procedencia').prop('checked', false);
    $('#select_all_almacenes').prop('checked', false);
    $('#tipo_movim').val('');
    $('#fecha_desde').val('');
    $('#fecha_hasta').val('');
    $('#no_consec').val('');
  });

  $('#select_all_plazas').on('change', function() {
    $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('#select_all_procedencia').on('change', function() {
    $('.procedencia-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('#select_all_almacenes').on('change', function() {
    $('.almacen-checkbox').prop('checked', $(this).prop('checked'));
  });

  $('.plaza-checkbox, .procedencia-checkbox, .almacen-checkbox').on('change', function() {
    dataTable.ajax.reload();
  });

  $('#tipo_movim').on('change', function() {
    dataTable.ajax.reload();
  });

  $('#btn_export').on('click', function() {
    const plazasSeleccionadas = $('.plaza-checkbox:checked').map(function() { return $(this).val(); }).get();
    const procedenciasSeleccionadas = $('.procedencia-checkbox:checked').map(function() { return $(this).val(); }).get();
    const almacenesSeleccionados = $('.almacen-checkbox:checked').map(function() { return $(this).val(); }).get();
    const tipoMovim = $('#tipo_movim').val();
    const fechaDesde = $('#fecha_desde').val();
    const fechaHasta = $('#fecha_hasta').val();

    const params = new URLSearchParams();

    plazasSeleccionadas.forEach(function(val) {
      params.append('plaza[]', val);
    });
    procedenciasSeleccionadas.forEach(function(val) {
      params.append('procedencia[]', val);
    });
    almacenesSeleccionados.forEach(function(val) {
      params.append('almacen[]', val);
    });
    if (tipoMovim) {
      params.append('tipo_movim', tipoMovim);
    }
    if (fechaDesde) {
      params.append('fecha_desde', fechaDesde);
    }
    if (fechaHasta) {
      params.append('fecha_hasta', fechaHasta);
    }

    const url = "{{ url('/reportes/vales/export') }}?" + params.toString();
    window.open(url, '_blank');
  });
});
</script>
@endsection
