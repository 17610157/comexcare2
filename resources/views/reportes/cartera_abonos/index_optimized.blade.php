@extends('adminlte::page')
@section('title', 'Cartera Abonos - Optimizado')

@section('content_header')
<h1>Cartera - Abonos <small class="text-muted">(Tiempo Real)</small></h1>
@stop

@section('content')
<div class="container-fluid">
  <!-- Estadísticas en Tiempo Real -->
  <div class="row mb-3">
    <div class="col-md-2">
      <div class="card bg-primary text-white">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Total Abonos</h6>
          <p class="card-text mb-0" id="stats-total-abonos">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-success text-white">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Monto Total</h6>
          <p class="card-text mb-0" id="stats-total-monto">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-info text-white">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Plazas</h6>
          <p class="card-text mb-0" id="stats-plazas">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-warning text-white">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Tiendas</h6>
          <p class="card-text mb-0" id="stats-tiendas">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-secondary text-white">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Clientes</h6>
          <p class="card-text mb-0" id="stats-clientes">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-dark text-white">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Cache</h6>
          <p class="card-text mb-0">
            <span id="cache-indicator" class="badge bg-secondary">DB</span>
            <small id="cache-keys">0</small>
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros Optimizados -->
  <div class="card bg-light mb-3">
    <div class="card-header">
      <h5 class="mb-0 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-filter"></i> Filtros</span>
        <small id="performance-stats" class="text-muted"></small>
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
          <div class="input-group input-group-sm">
            <input type="text" id="plaza" class="form-control border-secondary" placeholder="Ej: A001" maxlength="5" pattern="[A-Z0-9]{5}" style="text-transform: uppercase;" title="Código de 5 caracteres, letras mayúsculas y números. ENTER para buscar, ESC para limpiar.">
            <span class="input-group-text"><i class="fas fa-building"></i></span>
          </div>
        </div>
        <div class="col-md-3">
          <label for="tienda" class="form-label">Código Tienda</label>
          <div class="input-group input-group-sm">
            <input type="text" id="tienda" class="form-control border-secondary" placeholder="Ej: B001" maxlength="10" pattern="[A-Z0-9]{10}" style="text-transform: uppercase;" title="Código de tienda con letras mayúsculas y números. ENTER para buscar, ESC para limpiar.">
            <span class="input-group-text"><i class="fas fa-store"></i></span>
          </div>
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-md-12 d-flex align-items-end justify-content-end">
          <button id="btn_search" class="btn btn-success btn-sm me-2">
            <i class="fas fa-search"></i> Buscar
          </button>
          <button id="btn_refresh" class="btn btn-primary btn-sm me-2" title="Recargar e invalidar caché">
            <i class="fas fa-sync-alt"></i> Actualizar
          </button>
          <button id="btn_reset_filters" class="btn btn-secondary btn-sm me-2" title="Limpiar todos los filtros (ESC en campos)">
            <i class="fas fa-undo"></i> Limpiar
          </button>
          <div class="btn-group me-2" role="group">
            <button id="btn_excel" class="btn btn-success btn-sm" title="Exportar a Excel">
              <i class="fas fa-file-excel"></i>
            </button>
            <button id="btn_csv" class="btn btn-info btn-sm" title="Exportar a CSV">
              <i class="fas fa-file-csv"></i>
            </button>
            <button id="btn_pdf" class="btn btn-danger btn-sm" title="Exportar a PDF">
              <i class="fas fa-file-pdf"></i>
            </button>
          </div>
          <button id="btn_stats" class="btn btn-outline-primary btn-sm" title="Ver estadísticas detalladas">
            <i class="fas fa-chart-bar"></i> Estadísticas
          </button>
        </div>
      </div>
      
      <div class="row mt-2">
        <div class="col-12">
          <span id="current_period_display" class="badge bg-info text-white"></span>
          <span class="badge bg-success ms-1" id="auto-refresh-indicator" style="display:none;">
            <i class="fas fa-sync-alt fa-spin"></i> Auto-refresh
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- DataTable Optimizado -->
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-table"></i> Resultados
      </h5>
      <div>
        <span class="badge bg-light text-dark" id="record-count">0 registros</span>
        <span class="badge bg-warning text-dark ms-1" id="query-time">0ms</span>
      </div>
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

<!-- Modal de Exportación -->
<div class="modal fade" id="exportModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Exportar Datos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Seleccione el formato de exportación:</p>
        <div class="d-grid gap-2">
          <button class="btn btn-success" onclick="exportData('excel')">
            <i class="fas fa-file-excel"></i> Microsoft Excel
          </button>
          <button class="btn btn-info" onclick="exportData('csv')">
            <i class="fas fa-file-csv"></i> CSV (Valores Separados por Comas)
          </button>
          <button class="btn btn-danger" onclick="exportData('pdf')">
            <i class="fas fa-file-pdf"></i> PDF (Documento)
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
<style>
.card-header {
  border-bottom: 2px solid #dee2e6;
}
.table th {
  background-color: #f8f9fa;
  font-weight: 600;
  white-space: nowrap;
}
.btn-sm {
  padding: 0.375rem 0.75rem;
}
.badge {
  font-size: 0.875em;
}
.animate-shake {
  animation: shake 0.5s;
}
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}
.border-danger {
  border-color: #dc3545 !important;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.border-primary {
  border-color: #0d6efd !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}
.border-secondary {
  border-color: #6c757d !important;
}
#performance-stats {
  font-size: 0.75rem;
}
.stats-card {
  transition: all 0.3s ease;
}
.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="{{ asset('js/reportes/cartera_abonos_optimized.js') }}"></script>
<script>
// Funciones globales para exportación
function showExportModal(format) {
  $('#exportModal').modal('show');
}

function exportData(format) {
  const start = $('#period_start').val();
  const end = $('#period_end').val();
  const plaza = $('#plaza').val();
  const tienda = $('#tienda').val();
  
  let url = "{{ url('/reportes/cartera-abonos-optimized/export') }}/" + format;
  url += '?period_start=' + encodeURIComponent(start);
  url += '&period_end=' + encodeURIComponent(end);
  if (plaza) url += '&plaza=' + encodeURIComponent(plaza);
  if (tienda) url += '&tienda=' + encodeURIComponent(tienda);
  
  window.open(url, '_blank');
  $('#exportModal').modal('hide');
}

function buildExportUrl(format) {
  const start = $('#period_start').val();
  const end = $('#period_end').val();
  const plaza = $('#plaza').val();
  const tienda = $('#tienda').val();
  
  let url = "{{ url('/reportes/cartera-abonos-optimized/pdf') }}";
  url += '?period_start=' + encodeURIComponent(start);
  url += '&period_end=' + encodeURIComponent(end);
  if (plaza) url += '&plaza=' + encodeURIComponent(plaza);
  if (tienda) url += '&tienda=' + encodeURIComponent(tienda);
  
  return url;
}

function updateCurrentPeriodDisplay(){
  const s = $('#period_start').val();
  const e = $('#period_end').val();
  $('#current_period_display').text('Periodo: ' + s + ' a ' + e);
}

function resetFilters() {
  $('#period_start').val("{{ $startDefault }}");
  $('#period_end').val("{{ $endDefault }}");
  $('#plaza').val('').removeClass('border-primary').addClass('border-secondary');
  $('#tienda').val('').removeClass('border-primary').addClass('border-secondary');
  updateCurrentPeriodDisplay();
  if (window.carteraAbonos && window.carteraAbonos.state.dataTable) {
    window.carteraAbonos.state.dataTable.ajax.reload();
    window.carteraAbonos.loadStats();
  }
}

// Mostrar indicador de auto-refresh
setInterval(function() {
  $('#auto-refresh-indicator').fadeIn(300).delay(2000).fadeOut(300);
}, 300000); // Cada 5 minutos
</script>
@endsection