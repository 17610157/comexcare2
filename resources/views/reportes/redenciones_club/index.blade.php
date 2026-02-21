@extends('adminlte::page')

@section('title', 'Redenciones Club Comex')

@section('content_header')
<h1>Redenciones Club Comex</h1>
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
            @hasPermission('reportes.redenciones_club.sincronizar')
            <button id="btn_sync" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#syncModal">
              <i class="fas fa-database"></i> <span class="d-none d-sm-inline">Sincronizar Datos</span>
            </button>
            @endhasPermission
          </div>
          <div class="d-flex gap-1 flex-wrap">
            @hasPermission('reportes.redenciones_club.filtrar')
            <button id="btn_search" class="btn btn-success btn-sm">
              <i class="fas fa-search"></i> <span class="d-none d-sm-inline">Buscar</span>
            </button>
            @endhasPermission
            @hasPermission('reportes.redenciones_club.ver')
            <button id="btn_refresh" class="btn btn-primary btn-sm">
              <i class="fas fa-sync-alt"></i> <span class="d-none d-sm-inline">Actualizar</span>
            </button>
            @endhasPermission
            @hasPermission('reportes.redenciones_club.filtrar')
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm">
              <i class="fas fa-undo"></i> <span class="d-none d-sm-inline">Limpiar</span>
            </button>
            @endhasPermission
            @hasPermission('reportes.redenciones_club.exportar')
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
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">
        <i class="fas fa-table"></i> Resultados
      </h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive" style="overflow-x: auto;">
        <table id="report-table" class="table table-bordered table-hover table-striped mb-0" style="width: 100%; min-width: 800px;">
          <thead class="thead-light">
            <tr>
              <th>Plaza</th>
              <th>Tienda</th>
              <th>Concepto</th>
              <th>Fecha</th>
              <th>Ref Tipo</th>
              <th>Ref Num</th>
              <th>Importe</th>
              <th>Ing/Egr</th>
              <th>Club ID</th>
              <th>Vendedor</th>
              <th>Nota Folio</th>
              <th>Folio R</th>
              <th>Tipo Venta</th>
              <th>Cliente</th>
              <th>Status</th>
              <th>Fecha Nota</th>
              <th>Producto</th>
              <th>Descripcion</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Subtotal</th>
              <th>Importe</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Sincronización -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
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
        <button type="button" id="btn_confirm_sync" class="btn btn-warning">
          <i class="fas fa-sync-alt"></i> Sincronizar
        </button>
      </div>
    </div>
  </div>
</div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<style>
@media (max-width: 768px) {
  .modal-fullscreen-md-down {
    width: 100vw;
    max-width: none;
    height: 100%;
    margin: 0;
  }
  .modal-fullscreen-md-down .modal-content {
    height: 100%;
    border: 0;
    border-radius: 0;
  }
  .modal-fullscreen-md-down .modal-body {
    overflow-y: auto;
  }
  .table-responsive {
    font-size: 0.85rem;
  }
  .table-responsive .btn, 
  .table-responsive .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
}
@media (max-width: 576px) {
  .filters-container .col-6, 
  .filters-container .col-12 {
    padding-right: 5px;
    padding-left: 5px;
  }
  .filters-container .form-label {
    font-size: 0.75rem;
  }
  .filters-container .form-control-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  .btn-group-sm > .btn, .btn-sm {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
  }
}
</style>
@stop

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function() {
    // Inicializar DataTable
    var table = $('#report-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("reportes.redenciones_club.data") }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: function(d) {
                d.period_start = $('#period_start').val();
                d.period_end = $('#period_end').val();
                d.plaza = $('#select_all_plazas').is(':checked') ? '' : getSelectedValues('.plaza-checkbox');
                d.tienda = $('#select_all_tiendas').is(':checked') ? '' : getSelectedValues('.tienda-checkbox');
                d.vendedor = $('#vendedor').val();
            }
        },
        columns: [
            { data: 'cplaza', name: 'cplaza' },
            { data: 'ctienda', name: 'ctienda' },
            { data: 'cve_con', name: 'cve_con' },
            { data: 'fecha', name: 'fecha' },
            { data: 'ref_tipo', name: 'ref_tipo' },
            { data: 'ref_num', name: 'ref_num' },
            { data: 'importe', name: 'importe', render: function(data) { return formatCurrency(data); } },
            { data: 'ing_egr', name: 'ing_egr' },
            { data: 'club_id', name: 'club_id' },
            { data: 'vend_clave', name: 'vend_clave' },
            { data: 'nota_folio', name: 'nota_folio' },
            { data: 'cfolio_r', name: 'cfolio_r' },
            { data: 'tipo_venta', name: 'tipo_venta' },
            { data: 'clie_clave', name: 'clie_clave' },
            { data: 'ban_status', name: 'ban_status' },
            { data: 'nota_fecha', name: 'nota_fecha' },
            { data: 'prod_clave', name: 'prod_clave' },
            { data: 'cdesc_adi', name: 'cdesc_adi' },
            { data: 'nota_canti', name: 'nota_canti', render: function(data) { return data ? parseFloat(data).toFixed(2) : '0.00'; } },
            { data: 'nota_preci', name: 'nota_preci', render: function(data) { return formatCurrency(data); } },
            { data: 'subtotal', name: 'subtotal', render: function(data) { return formatCurrency(data); } },
            { data: 'nota_impor', name: 'nota_impor', render: function(data) { return formatCurrency(data); } }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            info: "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            infoEmpty: "Mostrando registros del 0 al 0 de un total de 0 registros",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    function getSelectedValues(selector) {
        var values = [];
        $(selector + ':checked').each(function() {
            values.push($(this).val());
        });
        return values;
    }

    function formatCurrency(value) {
        if (value === null || value === undefined) return '$0.00';
        return '$' + parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Checkbox select all
    $('#select_all_plazas').on('change', function() {
        $('.plaza-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    $('#select_all_tiendas').on('change', function() {
        $('.tienda-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Botón buscar
    $('#btn_search').on('click', function() {
        updatePeriodDisplay();
        table.draw();
    });

    // Botón actualizar
    $('#btn_refresh').on('click', function() {
        table.draw();
    });

    // Botón limpiar
    $('#btn_reset_filters').on('click', function() {
        $('#period_start').val('{{ $startDefault }}');
        $('#period_end').val('{{ $endDefault }}');
        $('#vendedor').val('');
        $('#select_all_plazas').prop('checked', true);
        $('#select_all_tiendas').prop('checked', true);
        $('.plaza-checkbox').prop('checked', false);
        $('.tienda-checkbox').prop('checked', false);
        updatePeriodDisplay();
        table.draw();
    });

    // Botón CSV
    $('#btn_csv').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Exportar a CSV',
            text: '¿Desea descargar el archivo CSV?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, descargar',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (result.isConfirmed) {
                var url = '{{ route("reportes.redenciones_club.export.csv") }}';
                var form = $('<form method="POST" action="' + url + '">');
                form.append('@csrf');
                form.append($('<input type="hidden" name="period_start">').val($('#period_start').val()));
                form.append($('<input type="hidden" name="period_end">').val($('#period_end').val()));
                form.append($('<input type="hidden" name="plaza">').val($('#select_all_plazas').is(':checked') ? '' : getSelectedValues('.plaza-checkbox').join(',')));
                form.append($('<input type="hidden" name="tienda">').val($('#select_all_tiendas').is(':checked') ? '' : getSelectedValues('.tienda-checkbox').join(',')));
                form.append($('<input type="hidden" name="vendedor">').val($('#vendedor').val()));
                $('body').append(form);
                form.submit();
                form.remove();
            }
        });
    });

    // Modal sync - mostrar/ocultar opciones según tipo seleccionado
    $('input[name="syncType"]').on('change', function() {
        var type = $(this).val();
        $('#syncLastDaysOptions').hide();
        $('#syncDayOptions').hide();
        $('#syncPeriodOptions').hide();
        
        if (type === 'lastDays') {
            $('#syncLastDaysOptions').show();
        } else if (type === 'day') {
            $('#syncDayOptions').show();
        } else if (type === 'period') {
            $('#syncPeriodOptions').show();
        }
    });

    // Botón confirmar sync
    $('#btn_confirm_sync').on('click', function() {
        var type = $('input[name="syncType"]:checked').val();
        var append = $('#appendData').is(':checked');
        var data = { type: type, append: append, _token: '{{ csrf_token() }}' };
        
        if (type === 'period') {
            data.periodStart = $('#periodStartInput').val();
            data.periodEnd = $('#periodEndInput').val();
        } else if (type === 'lastDays') {
            data.lastDays = $('#lastDaysInput').val();
        } else if (type === 'day') {
            data.day = $('#dayInput').val();
        }
        
        $('#syncProgress').show();
        $('#syncResult').hide();
        $('#btn_confirm_sync').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("reportes.redenciones_club.sync") }}',
            type: 'POST',
            data: data,
            success: function(response) {
                $('#syncProgress').hide();
                $('#syncResult').show();
                if (response.success) {
                    $('#syncResult').removeClass('alert-danger').addClass('alert-success');
                    $('#syncResult').html('<i class="fas fa-check-circle"></i> ' + response.message);
                    table.ajax.reload();
                } else {
                    $('#syncResult').removeClass('alert-success').addClass('alert-danger');
                    $('#syncResult').html('<i class="fas fa-exclamation-circle"></i> ' + response.message);
                }
            },
            error: function(xhr) {
                $('#syncProgress').hide();
                $('#syncResult').show();
                $('#syncResult').removeClass('alert-success').addClass('alert-danger');
                $('#syncResult').html('<i class="fas fa-exclamation-circle"></i> Error: ' + (xhr.responseJSON?.message || 'Error al sincronizar'));
            },
            complete: function() {
                $('#btn_confirm_sync').prop('disabled', false);
            }
        });
    });

    function updatePeriodDisplay() {
        var start = $('#period_start').val();
        var end = $('#period_end').val();
        $('#current_period_display').text('Período: ' + start + ' al ' + end);
    }

    updatePeriodDisplay();
});
</script>
@stop
