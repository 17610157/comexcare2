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
            @can('cortes.enviar')
            <button id="btn_notificar" class="btn btn-warning btn-sm">
              <i class="fas fa-paper-plane"></i> <span class="d-none d-sm-inline">Notificar faltantes</span>
            </button>
            @endcan
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

  @can('cortes.enviar')
  <div class="modal fade" id="modal_notificar" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Notificar tiendas sin corte por plaza</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="row align-items-end mb-3">
            <div class="col-md-3">
              <label class="form-label small mb-1">Fecha a evaluar</label>
              <input type="date" id="notif_fecha" class="form-control form-control-sm" value="{{ now()->subDay()->toDateString() }}">
            </div>
            <div class="col-md-9 text-right">
              <button type="button" class="btn btn-default btn-sm" id="btn_notif_reload">
                <i class="fas fa-sync-alt"></i> Actualizar
              </button>
              <button type="button" class="btn btn-success btn-sm" id="btn_notif_enviar_todas">
                <i class="fas fa-paper-plane"></i> Enviar todas
              </button>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Plaza</th>
                  <th>Destinatarios</th>
                  <th class="text-center">Sin corte</th>
                  <th class="text-center">Regla</th>
                  <th class="text-center" style="min-width:160px;">Programación</th>
                  <th class="text-center">Enviar</th>
                </tr>
              </thead>
              <tbody id="notif_tbody">
                <tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>
              </tbody>
            </table>
          </div>
          <small class="text-muted d-block mt-2">
            Se incluyen siempre los destinatarios fijos: {{ implode(', ', config('cortes.destinatarios_fijos', [])) }}.
          </small>
        </div>
      </div>
    </div>
  </div>
  @endcan
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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

  function loadNotifPlazas() {
    const fecha = $('#notif_fecha').val();
    $('#notif_tbody').html('<tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>');
    $.get('{{ route('reportes.cortes.notificaciones.plazas') }}', { fecha: fecha }, function(res) {
      const tbody = $('#notif_tbody').empty();
      if (!res.data || res.data.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center text-muted">Sin plazas.</td></tr>');
        return;
      }
      res.data.forEach(function(p) {
        const correos = (p.emails && p.emails.length) ? p.emails.join(', ') : '<span class="text-muted">Solo fijos</span>';
        const horaValor = (p.hora_envio !== null && p.hora_envio !== undefined) ? String(p.hora_envio).padStart(2, '0') + ':00' : '07:00';
        const programacion =
          '<div class="d-flex align-items-center justify-content-center">' +
            '<div class="custom-control custom-switch mr-2">' +
              '<input type="checkbox" class="custom-control-input notif-auto" id="auto_' + p.plaza + '" data-plaza="' + p.plaza + '"' + (p.envio_activo ? ' checked' : '') + '>' +
              '<label class="custom-control-label" for="auto_' + p.plaza + '"></label>' +
            '</div>' +
            '<input type="time" class="form-control form-control-sm notif-hora" style="width:110px" data-plaza="' + p.plaza + '" value="' + horaValor + '">' +
          '</div>';
        const btn = '<button class="btn btn-success btn-sm btn-notif-send" data-plaza="' + p.plaza + '"><i class="fas fa-paper-plane"></i> Enviar</button>';
        tbody.append(
          '<tr><td><strong>' + p.plaza + '</strong></td>' +
          '<td class="text-truncate" style="max-width:280px;">' + correos + '</td>' +
          '<td class="text-center">' + p.sin_corte + '</td>' +
          '<td class="text-center">' + p.regla + '</td>' +
          '<td class="text-center">' + programacion + '</td>' +
          '<td class="text-center">' + btn + '</td></tr>'
        );
      });
    }).fail(function() {
      $('#notif_tbody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar plazas.</td></tr>');
    });
  }

  $('#btn_notificar').on('click', function() {
    $('#modal_notificar').modal('show');
    loadNotifPlazas();
  });

  $('#btn_notif_reload').on('click', loadNotifPlazas);
  $('#notif_fecha').on('change', loadNotifPlazas);

  $('#notif_tbody').on('change', '.notif-auto, .notif-hora', function() {
    const $row = $(this).closest('tr');
    const plaza = $(this).data('plaza');
    const activo = $row.find('.notif-auto').is(':checked');
    const hora = $row.find('.notif-hora').val() || '07:00';
    const horaInt = parseInt(hora.split(':')[0], 10) || 0;

    $.post('{{ route('reportes.cortes.notificaciones.automatico') }}', {
      plaza: plaza,
      envio_activo: activo ? 1 : 0,
      hora_envio: horaInt,
      _token: '{{ csrf_token() }}'
    })
      .done(function(res) {
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        Toast.fire({ icon: 'success', title: res.message });
      })
      .fail(function(xhr) {
        Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'No se pudo guardar la programación' });
      });
  });

  $('#notif_tbody').on('click', '.btn-notif-send', function() {
    const plaza = $(this).data('plaza');
    const fecha = $('#notif_fecha').val();
    const $btn = $(this).prop('disabled', true);
    $.post('{{ route('reportes.cortes.notificaciones.enviar') }}', { plaza: plaza, fecha: fecha, _token: '{{ csrf_token() }}' })
      .done(function(res) {
        Swal.fire({ icon: res.success ? 'success' : 'info', title: res.success ? 'Enviado' : 'Sin envío', text: res.message });
        loadNotifPlazas();
      })
      .fail(function(xhr) {
        Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar' });
      })
      .always(function() { $btn.prop('disabled', false); });
  });

  $('#btn_notif_enviar_todas').on('click', function() {
    const fecha = $('#notif_fecha').val();
    const $btn = $(this).prop('disabled', true);
    $.post('{{ route('reportes.cortes.notificaciones.enviar-todas') }}', { fecha: fecha, _token: '{{ csrf_token() }}' })
      .done(function(res) {
        Swal.fire({ icon: 'success', title: 'Completado', text: res.message }).then(loadNotifPlazas);
      })
      .fail(function(xhr) {
        Swal.fire({ icon: 'error', title: 'Error', text: (xhr.responseJSON && xhr.responseJSON.message) || 'Error al enviar' });
      })
      .always(function() { $btn.prop('disabled', false); });
  });
});
</script>
@endsection
