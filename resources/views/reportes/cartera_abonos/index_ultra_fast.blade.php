@extends('adminlte::page')
@section('title', 'Cartera Abonos - Ultra Fast (500 Usuarios)')

@section('content_header')
<h1>Cartera - Abonos <small class="badge bg-success">ULTRA-FAST</small></h1>
@stop

@section('content')
<div class="container-fluid">
  <!-- Panel de Estado Ultra-Fast -->
  <div class="row mb-3">
    <div class="col-12">
      <div class="card bg-dark text-white">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="fas fa-rocket"></i> Sistema Ultra-Fast (500 Usuarios)
          </h5>
          <div>
            <span id="connection-status" class="badge bg-success">
              <i class="fas fa-circle fa-pulse"></i> Pre-cargado
            </span>
            <span id="data-source" class="badge bg-info ms-1">Redis</span>
            <span id="performance-tier" class="badge bg-success ms-1">Ultra-Fast</span>
          </div>
        </div>
        <div class="card-body p-2">
          <div class="row text-center">
            <div class="col-md-2">
              <small class="text-muted">Total Registros</small>
              <div id="stats-total-records" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Filtrados</small>
              <div id="stats-filtered-records" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Tiempo Carga</small>
              <div id="stats-load-time" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Cache Status</small>
              <div id="stats-cache-status" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Data Source</small>
              <div id="stats-data-source" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Acciones</small>
              <div class="btn-group btn-group-sm">
                <button id="btn_force_preload" class="btn btn-outline-light" title="Forzar pre-carga">
                  <i class="fas fa-sync-alt"></i>
                </button>
                <button id="btn_health_check" class="btn btn-outline-light" title="Health check">
                  <i class="fas fa-heartbeat"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Overlay de Carga -->
  <div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.8); z-index: 9999; display: none;">
    <div class="text-center text-white">
      <div class="spinner-border mb-3" style="width: 3rem; height: 3rem;"></div>
      <h4 id="loading-message">Pre-cargando datos...</h4>
      <p class="mb-0">Esto solo se ejecuta una vez por periodo</p>
    </div>
  </div>

  <!-- Overlay de Error -->
  <div id="error-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.8); z-index: 9999; display: none;">
    <div class="text-center text-white">
      <div class="mb-3">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem;"></i>
      </div>
      <h4>Error al cargar datos</h4>
      <p id="error-message" class="mb-3">Mensaje de error</p>
      <button class="btn btn-primary" onclick="location.reload()">Reintentar</button>
    </div>
  </div>

  <!-- Controles de Búsqueda (deshabilitados inicialmente) -->
  <div class="card bg-light mb-3">
    <div class="card-header">
      <h5 class="mb-0 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-search"></i> Búsqueda Cliente-Side (Instantánea)</span>
        <small class="text-muted">Zero Database Queries</small>
      </h5>
    </div>
    <div class="card-body">
      <div id="search-controls">
        <div class="row">
          <div class="col-md-3">
            <label for="period_start" class="form-label">Periodo Inicio</label>
            <input type="date" id="period_start" class="form-control form-control-sm" disabled>
          </div>
          <div class="col-md-3">
            <label for="period_end" class="form-label">Periodo Fin</label>
            <input type="date" id="period_end" class="form-control form-control-sm" disabled>
          </div>
          <div class="col-md-3">
            <label for="plaza" class="form-label">Código Plaza</label>
            <div class="input-group input-group-sm">
              <input type="text" id="plaza" class="form-control" placeholder="Ej: A001" maxlength="5" disabled>
              <span class="input-group-text"><i class="fas fa-building"></i></span>
            </div>
          </div>
          <div class="col-md-3">
            <label for="tienda" class="form-label">Código Tienda</label>
            <div class="input-group input-group-sm">
              <input type="text" id="tienda" class="form-control" placeholder="Ej: B001" maxlength="10" disabled>
              <span class="input-group-text"><i class="fas fa-store"></i></span>
            </div>
          </div>
        </div>
        
        <div class="row mt-3">
          <div class="col-md-6">
            <label for="search-input" class="form-label">Búsqueda General</label>
            <div class="input-group input-group-sm">
              <input type="text" id="search-input" class="form-control" placeholder="Buscar por nombre, RFC, factura, etc." disabled>
              <span class="input-group-text"><i class="fas fa-search"></i></span>
            </div>
          </div>
          <div class="col-md-6 d-flex align-items-end justify-content-end">
            <button id="btn_search" class="btn btn-success btn-sm me-2" disabled>
              <i class="fas fa-search"></i> Buscar
            </button>
            <button id="btn_reset_filters" class="btn btn-secondary btn-sm me-2" disabled>
              <i class="fas fa-undo"></i> Limpiar
            </button>
            <div id="export-controls" class="btn-group btn-group-sm" role="group">
              <button id="btn_export_excel" class="btn btn-success btn-sm" title="Exportar Excel" disabled>
                <i class="fas fa-file-excel"></i>
              </button>
              <button id="btn_export_csv" class="btn btn-info btn-sm" title="Exportar CSV" disabled>
                <i class="fas fa-file-csv"></i>
              </button>
              <button id="btn_export_pdf" class="btn btn-danger btn-sm" title="Exportar PDF" disabled>
                <i class="fas fa-file-pdf"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- DataTable Ultra-Fast -->
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-table"></i> Resultados (Filtrado 100% Cliente-Side)
      </h5>
      <div>
        <span class="badge bg-light text-dark" id="record-count">0 registros</span>
        <span class="badge bg-success text-dark ms-1">Pre-cargado</span>
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

  <!-- Información de Arquitectura -->
  <div class="row mt-3">
    <div class="col-12">
      <div class="card bg-info text-white">
        <div class="card-header">
          <h6 class="mb-0">
            <i class="fas fa-info-circle"></i> Arquitectura Ultra-Fast
          </h6>
        </div>
        <div class="card-body p-2">
          <div class="row text-center">
            <div class="col-md-3">
              <small class="d-block">Pre-carga</small>
              <strong>Única llamada</strong>
            </div>
            <div class="col-md-3">
              <small class="d-block">Filtrado</small>
              <strong>100% Cliente-Side</strong>
            </div>
            <div class="col-md-3">
              <small class="d-block">Cache</small>
              <strong>Redis (2h TTL)</strong>
            </div>
            <div class="col-md-3">
              <small class="d-block">Concurrencia</small>
              <strong>500 Usuarios</strong>
            </div>
          </div>
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
.stats-card {
  transition: all 0.3s ease;
}
.stats-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
#loading-overlay, #error-overlay {
  backdrop-filter: blur(5px);
}
.search-highlight {
  background-color: #fff3cd;
  padding: 1px 2px;
  border-radius: 2px;
}
.performance-indicator {
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.7; }
  100% { opacity: 1; }
}
</style>
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="{{ asset('js/reportes/cartera_abonos_ultra_fast.js') }}"></script>
<script>
// Configuración adicional para 500 usuarios
window.ULTRA_FAST_CONFIG = {
    maxConcurrentUsers: 500,
    debounceTime: 100,
    preloadTimeout: 10000,
    incrementalUpdateInterval: 300000
};

// Health check periódico
setInterval(function() {
    $.get('{{ url("/reportes/cartera-abonos-ultra-fast/health") }}')
        .done(function(response) {
            if (response.status !== 'healthy') {
                console.warn('Health check degraded:', response);
            }
        })
        .fail(function() {
            console.error('Health check failed');
        });
}, 60000); // Cada minuto

// Monitor de performance
window.performanceMonitor = {
    startTime: performance.now(),
    
    mark: function(name) {
        performance.mark(name);
    },
    
    measure: function(name, startMark, endMark) {
        performance.measure(name, startMark, endMark);
        const measures = performance.getEntriesByName(name);
        if (measures.length > 0) {
            console.log(`${name}: ${measures[0].duration.toFixed(2)}ms`);
        }
    }
};

// Iniciar monitor
window.performanceMonitor.mark('system-start');
</script>
@endsection