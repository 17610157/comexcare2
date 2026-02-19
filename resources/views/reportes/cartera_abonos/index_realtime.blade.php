@extends('adminlte::page')
@section('title', 'Cartera Abonos - Tiempo Real')

@section('content_header')
<h1>Cartera - Abonos <small class="badge bg-success">TIEMPO REAL</small></h1>
@stop

@section('content')
<div class="container-fluid">
  <!-- Panel de Estado en Tiempo Real -->
  <div class="row mb-3">
    <div class="col-12">
      <div class="card bg-dark text-white">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">
            <i class="fas fa-satellite-dish"></i> Estado del Sistema
          </h5>
          <div>
            <span id="connection-status" class="badge bg-success">
              <i class="fas fa-circle fa-pulse"></i> Conectado
            </span>
            <span id="data-source" class="badge bg-info ms-1">Materializada</span>
            <span id="performance-tier" class="badge bg-warning ms-1">--</span>
          </div>
        </div>
        <div class="card-body p-2">
          <div class="row text-center">
            <div class="col-md-2">
              <small class="text-muted">Última Sincronización</small>
              <div id="last-sync-time" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Registros Totales</small>
              <div id="total-records" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Tiempo Respuesta</small>
              <div id="response-time" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Cambios Pendientes</small>
              <div id="pending-changes" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Estado Datos</small>
              <div id="data-freshness" class="fw-bold">--</div>
            </div>
            <div class="col-md-2">
              <small class="text-muted">Health Check</small>
              <div id="health-status" class="fw-bold">--</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Estadísticas en Vivo -->
  <div class="row mb-3">
    <div class="col-md-2">
      <div class="card bg-primary text-white stats-card">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Total Abonos</h6>
          <p class="card-text mb-0" id="stats-total-abonos">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-success text-white stats-card">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Monto Total</h6>
          <p class="card-text mb-0" id="stats-total-monto">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-info text-white stats-card">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Plazas</h6>
          <p class="card-text mb-0" id="stats-plazas">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-warning text-white stats-card">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Tiendas</h6>
          <p class="card-text mb-0" id="stats-tiendas">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-secondary text-white stats-card">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Clientes</h6>
          <p class="card-text mb-0" id="stats-clientes">-</p>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-dark text-white stats-card">
        <div class="card-body p-2">
          <h6 class="card-title mb-0">Acciones</h6>
          <div class="btn-group btn-group-sm">
            <button id="btn-force-sync" class="btn btn-outline-light" title="Forzar sincronización">
              <i class="fas fa-sync-alt"></i>
            </button>
            <button id="btn-health-check" class="btn btn-outline-light" title="Health check">
              <i class="fas fa-heartbeat"></i>
            </button>
          </div>
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
            <input type="text" id="plaza" class="form-control border-secondary" placeholder="Ej: A001" maxlength="5" pattern="[A-Z0-9]{5}" style="text-transform: uppercase;">
            <span class="input-group-text"><i class="fas fa-building"></i></span>
          </div>
        </div>
        <div class="col-md-3">
          <label for="tienda" class="form-label">Código Tienda</label>
          <div class="input-group input-group-sm">
            <input type="text" id="tienda" class="form-control border-secondary" placeholder="Ej: B001" maxlength="10" pattern="[A-Z0-9]{10}" style="text-transform: uppercase;">
            <span class="input-group-text"><i class="fas fa-store"></i></span>
          </div>
        </div>
      </div>
      
      <div class="row mt-3">
        <div class="col-md-12 d-flex align-items-end justify-content-end">
          @hasPermission('reportes.cartera_abonos.filtrar')
          <button id="btn_search" class="btn btn-success btn-sm me-2">
            <i class="fas fa-search"></i> Buscar
          </button>
          @endhasPermission
          @hasPermission('reportes.cartera_abonos.ver')
          <button id="btn_refresh" class="btn btn-primary btn-sm me-2" title="Recargar datos">
            <i class="fas fa-sync-alt"></i> Actualizar
          </button>
          @endhasPermission
          @hasPermission('reportes.cartera_abonos.filtrar')
          <button id="btn_reset_filters" class="btn btn-secondary btn-sm me-2" title="Limpiar filtros">
            <i class="fas fa-undo"></i> Limpiar
          </button>
          @endhasPermission
          @hasPermission('reportes.cartera_abonos.exportar')
          <div class="btn-group me-2" role="group">
            <button id="btn_excel" class="btn btn-success btn-sm" title="Exportar Excel">
              <i class="fas fa-file-excel"></i>
            </button>
            <button id="btn_csv" class="btn btn-info btn-sm" title="Exportar CSV">
              <i class="fas fa-file-csv"></i>
            </button>
            <button id="btn_pdf" class="btn btn-danger btn-sm" title="Exportar PDF">
              <i class="fas fa-file-pdf"></i>
            </button>
          </div>
          @endhasPermission
          <button id="btn_streaming" class="btn btn-outline-primary btn-sm" title="Activar streaming en tiempo real">
            <i class="fas fa-broadcast-tower"></i> Streaming
          </button>
        </div>
      </div>
      
      <div class="row mt-2">
        <div class="col-12">
          <span id="current_period_display" class="badge bg-info text-white"></span>
          <span class="badge bg-success ms-1" id="auto-refresh-indicator" style="display:none;">
            <i class="fas fa-sync-alt fa-spin"></i> Auto-refresh
          </span>
          <span class="badge bg-warning ms-1" id="streaming-indicator" style="display:none;">
            <i class="fas fa-broadcast-tower fa-pulse"></i> Streaming Activo
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- DataTable en Tiempo Real -->
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="fas fa-table"></i> Resultados en Tiempo Real
      </h5>
      <div>
        <span class="badge bg-light text-dark" id="record-count">0 registros</span>
        <span class="badge bg-warning text-dark ms-1" id="query-time">0ms</span>
        <span class="badge bg-info text-dark ms-1" id="data-source-indicator">Materializada</span>
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

<!-- Modal de Health Check -->
<div class="modal fade" id="healthModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Health Check - Sistema Cartera Abonos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="health-check-content">
          <div class="text-center">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Verificando...</span>
            </div>
            <p class="mt-2">Verificando estado del sistema...</p>
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
.health-check-item {
  padding: 0.5rem;
  border-bottom: 1px solid #dee2e6;
}
.health-check-item:last-child {
  border-bottom: none;
}
.health-status-healthy { color: #198754; }
.health-status-degraded { color: #ffc107; }
.health-status-unhealthy { color: #dc3545; }
.streaming-active {
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
<script>
// Configuración de Tiempo Real
const REALTIME_CONFIG = {
    debounceTime: 200,
    autoRefreshInterval: 30000, // 30 segundos
    streamingEnabled: false,
    maxRetries: 3,
    healthCheckInterval: 60000 // 1 minuto
};

// Estado de la aplicación
let realtimeState = {
    dataTable: null,
    eventSource: null,
    isStreaming: false,
    lastUpdate: null,
    performanceStats: {
        totalRequests: 0,
        averageResponseTime: 0,
        cacheHitRate: 0
    }
};

// Inicializar DataTable en Tiempo Real
function initializeRealtimeDataTable() {
    realtimeState.dataTable = $('#report-table').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 50, // Más registros para menos requests
        lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
        language: {
            search: "Buscar:",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            paginate: {
                first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior"
            },
            emptyTable: "No hay datos disponibles",
            zeroRecords: "No se encontraron resultados",
            processing: "Cargando..."
        },
        ajax: {
            url: "{{ url('/reportes/cartera-abonos-realtime/data') }}",
            type: "GET",
            data: function(d) {
                return {
                    draw: d.draw,
                    start: d.start,
                    length: d.length,
                    search: d.search.value,
                    plaza: $('#plaza').val().toUpperCase(),
                    tienda: $('#tienda').val().toUpperCase(),
                    period_start: $('#period_start').val(),
                    period_end: $('#period_end').val()
                };
            },
            dataSrc: function(json) {
                // Actualizar UI con metadata
                updateRealtimeUI(json.meta || {});
                
                // Actualizar contadores
                $('#record-count').text(json.recordsFiltered + ' registros');
                $('#query-time').text((json.meta?.query_time_ms || 0).toFixed(0) + 'ms');
                $('#data-source-indicator').text(json.meta?.data_source || 'Unknown');
                
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('Error en DataTable tiempo real:', {xhr, error, thrown});
                showConnectionError();
            }
        },
        columns: [
            { data: 'plaza', className: 'text-center', width: '80px' },
            { data: 'tienda', className: 'text-center', width: '80px' },
            { data: 'fecha', className: 'text-center', width: '100px',
              render: function(data) {
                  return data ? moment(data).format('DD/MM/YYYY') : '';
              }
            },
            { data: 'fecha_vta', className: 'text-center', width: '100px',
              render: function(data) {
                  return data ? moment(data).format('DD/MM/YYYY') : '';
              }
            },
            { data: 'concepto', className: 'text-center', width: '80px' },
            { data: 'tipo', className: 'text-center', width: '60px' },
            { data: 'factura', className: 'text-center', width: '100px' },
            { data: 'clave', className: 'text-center', width: '100px' },
            { data: 'rfc', className: 'text-center', width: '120px' },
            { data: 'nombre', width: '200px' },
            { data: 'monto_fa', className: 'text-end', width: '100px',
              render: $.fn.dataTable.render.number(',', '.', 2, '$')
            },
            { data: 'monto_dv', className: 'text-end', width: '100px',
              render: $.fn.dataTable.render.number(',', '.', 2, '$')
            },
            { data: 'monto_cd', className: 'text-end', width: '100px',
              render: $.fn.dataTable.render.number(',', '.', 2, '$')
            },
            { data: 'dias_cred', className: 'text-center', width: '80px' },
            { data: 'dias_vencidos', className: 'text-center', width: '80px',
              render: function(data) {
                  const days = parseInt(data);
                  if (days > 0) {
                      return '<span class="badge bg-danger">' + days + '</span>';
                  } else if (days < 0) {
                      return '<span class="badge bg-warning">' + Math.abs(days) + '</span>';
                  }
                  return '<span class="badge bg-success">0</span>';
              }
            }
        ],
        initComplete: function() {
            // Iniciar actualizaciones automáticas
            startAutoRefresh();
            loadRealtimeStats();
            startHealthCheck();
        }
    });
}

// Actualizar UI en tiempo real
function updateRealtimeUI(meta) {
    // Actualizar indicadores de performance
    if (meta.response_time_ms) {
        updatePerformanceStats(meta.response_time_ms);
    }
    
    // Actualizar estado de conexión
    updateConnectionStatus(true);
    
    // Actualizar fuente de datos
    $('#data-source').text(meta.data_source || 'Unknown');
    
    // Actualizar tier de performance
    if (meta.performance_tier) {
        updatePerformanceTier(meta.performance_tier);
    }
    
    // Actualizar última sincronización
    if (meta.last_sync) {
        $('#last-sync-time').text(moment(meta.last_sync).fromNow());
    }
    
    // Actualizar frescura de datos
    if (meta.is_fresh_data !== undefined) {
        $('#data-freshness').text(meta.is_fresh_data ? 'Fresco' : 'Antiguo');
        $('#data-freshness').toggleClass('text-success', meta.is_fresh_data)
                           .toggleClass('text-warning', !meta.is_fresh_data);
    }
}

// Event handlers
function setupRealtimeEventHandlers() {
    // Botones principales
    $('#btn_search').on('click', debounce(function() {
        realtimeState.dataTable.ajax.reload();
        loadRealtimeStats();
    }, REALTIME_CONFIG.debounceTime));
    
    $('#btn_refresh').on('click', function() {
        realtimeState.dataTable.ajax.reload();
        loadRealtimeStats();
    });
    
    $('#btn_reset_filters').on('click', function() {
        resetFilters();
    });
    
    // Forzar sincronización
    $('#btn-force-sync').on('click', function() {
        forceSync();
    });
    
    // Health check
    $('#btn-health-check').on('click', function() {
        performHealthCheck();
    });
    
    // Streaming
    $('#btn_streaming').on('click', function() {
        toggleStreaming();
    });
    
    // Filtros con auto-refresh
    $('#period_start, #period_end').on('change', debounce(function() {
        realtimeState.dataTable.ajax.reload();
        updateCurrentPeriodDisplay();
        loadRealtimeStats();
    }, REALTIME_CONFIG.debounceTime));
    
    // Filtros de plaza/tienda
    $('#plaza, #tienda').on('change', debounce(function() {
        if ($(this)[0].checkValidity()) {
            realtimeState.dataTable.ajax.reload();
            loadRealtimeStats();
        }
    }, REALTIME_CONFIG.debounceTime));
}

// Streaming en tiempo real
function startStreaming() {
    if (realtimeState.eventSource) {
        realtimeState.eventSource.close();
    }
    
    const params = {
        plaza: $('#plaza').val().toUpperCase(),
        tienda: $('#tienda').val().toUpperCase(),
        period_start: $('#period_start').val(),
        period_end: $('#period_end').val()
    };
    
    const queryString = new URLSearchParams(params).toString();
    const url = "{{ url('/reportes/cartera-abonos-realtime/stream') }}?" + queryString;
    
    realtimeState.eventSource = new EventSource(url);
    
    realtimeState.eventSource.onopen = function() {
        console.log('Streaming iniciado');
        realtimeState.isStreaming = true;
        $('#streaming-indicator').show();
        $('#btn_streaming').addClass('btn-primary').removeClass('btn-outline-primary');
    };
    
    realtimeState.eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.type === 'update') {
            // Actualizar DataTable con nuevos datos
            updateDataTableWithNewData(data.data);
        } else if (data.type === 'heartbeat') {
            // Actualizar indicador de streaming
            $('#streaming-indicator').addClass('streaming-active');
            setTimeout(() => {
                $('#streaming-indicator').removeClass('streaming-active');
            }, 1000);
        } else if (data.type === 'error') {
            console.error('Error en streaming:', data.error);
            stopStreaming();
        }
    };
    
    realtimeState.eventSource.onerror = function() {
        console.error('Error en streaming');
        stopStreaming();
    };
}

function stopStreaming() {
    if (realtimeState.eventSource) {
        realtimeState.eventSource.close();
        realtimeState.eventSource = null;
    }
    
    realtimeState.isStreaming = false;
    $('#streaming-indicator').hide();
    $('#btn_streaming').removeClass('btn-primary').addClass('btn-outline-primary');
}

function toggleStreaming() {
    if (realtimeState.isStreaming) {
        stopStreaming();
    } else {
        startStreaming();
    }
}

// Cargar estadísticas en tiempo real
function loadRealtimeStats() {
    $.get('{{ url("/reportes/cartera-abonos-realtime/stats") }}')
        .done(function(data) {
            updateStatsUI(data.stats);
            updateSyncUI(data.sync);
            updateHealthUI(data.health);
        })
        .fail(function() {
            console.warn('No se pudieron cargar las estadísticas');
        });
}

// Forzar sincronización
function forceSync() {
    $('#btn-force-sync').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.post('{{ url("/reportes/cartera-abonos-realtime/force-sync") }}')
        .done(function(data) {
            if (data.result.status === 'success') {
                showSuccess('Sincronización forzada exitosamente');
                // Recargar después de un momento
                setTimeout(() => {
                    realtimeState.dataTable.ajax.reload();
                    loadRealtimeStats();
                }, 2000);
            } else {
                showError('Error en sincronización: ' + data.result.message);
            }
        })
        .fail(function() {
            showError('Error al forzar sincronización');
        })
        .always(function() {
            $('#btn-force-sync').prop('disabled', false).html('<i class="fas fa-sync-alt"></i>');
        });
}

// Health check
function performHealthCheck() {
    $('#health-check-content').html(`
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Verificando...</span>
            </div>
            <p class="mt-2">Verificando estado del sistema...</p>
        </div>
    `);
    
    $('#healthModal').modal('show');
    
    $.get('{{ url("/reportes/cartera-abonos-realtime/health") }}')
        .done(function(data) {
            displayHealthCheckResults(data);
        })
        .fail(function() {
            $('#health-check-content').html(`
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> Error de Conexión</h5>
                    <p>No se pudo conectar al servicio de health check.</p>
                </div>
            `);
        });
}

function displayHealthCheckResults(health) {
    const statusClass = health.status === 'healthy' ? 'success' : 
                       health.status === 'degraded' ? 'warning' : 'danger';
    
    let html = `
        <div class="alert alert-${statusClass}">
            <h5><i class="fas fa-heartbeat"></i> Estado General: ${health.status.toUpperCase()}</h5>
            <p class="mb-0">Timestamp: ${health.timestamp}</p>
        </div>
        <div class="row">
    `;
    
    // Mostrar checks individuales
    Object.keys(health.checks).forEach(key => {
        if (key === 'overall_status') return;
        
        const check = health.checks[key];
        const icon = check ? '✅' : '❌';
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        html += `
            <div class="col-md-6">
                <div class="health-check-item">
                    <strong>${label}:</strong> ${icon}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    $('#health-check-content').html(html);
}

// Funciones de utilidad
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function updatePerformanceStats(responseTime) {
    realtimeState.performanceStats.totalRequests++;
    realtimeState.performanceStats.averageResponseTime = 
        (realtimeState.performanceStats.averageResponseTime + responseTime) / 2;
    
    $('#performance-stats').html(
        `Tiempo: ${realtimeState.performanceStats.averageResponseTime.toFixed(0)}ms | Requests: ${realtimeState.performanceStats.totalRequests}`
    );
}

function updatePerformanceTier(tier) {
    const tierConfig = {
        excellent: { class: 'success', text: 'Excelente' },
        good: { class: 'info', text: 'Bueno' },
        acceptable: { class: 'warning', text: 'Aceptable' },
        slow: { class: 'danger', text: 'Lento' }
    };
    
    const config = tierConfig[tier] || tierConfig.slow;
    $('#performance-tier')
        .removeClass('bg-success bg-info bg-warning bg-danger')
        .addClass('bg-' + config.class)
        .text(config.text);
}

function updateConnectionStatus(connected) {
    const statusEl = $('#connection-status');
    if (connected) {
        statusEl.removeClass('bg-danger').addClass('bg-success')
               .html('<i class="fas fa-circle fa-pulse"></i> Conectado');
    } else {
        statusEl.removeClass('bg-success').addClass('bg-danger')
               .html('<i class="fas fa-circle"></i> Desconectado');
    }
}

function showConnectionError() {
    updateConnectionStatus(false);
    showError('Error de conexión. Reintentando...');
}

function updateStatsUI(stats) {
    $('#stats-total-abonos').text((stats.total_abonos || 0).toLocaleString());
    $('#stats-total-monto').text('$' + (stats.total_general || 0).toLocaleString(undefined, {minimumFractionDigits: 2}));
    $('#stats-plazas').text(stats.unique_plazas || 0);
    $('#stats-tiendas').text(stats.unique_tiendas || 0);
    $('#stats-clientes').text(stats.unique_clientes || 0);
}

function updateSyncUI(sync) {
    $('#last-sync-time').text(sync.last_sync ? moment(sync.last_sync).fromNow() : 'Nunca');
    $('#total-records').text((sync.total_records || 0).toLocaleString());
    $('#pending-changes').text(sync.pending_changes || 0);
    $('#data-freshness').text(sync.is_fresh ? 'Fresco' : 'Antiguo');
}

function updateHealthUI(health) {
    const statusText = health.overall_status || 'Unknown';
    const statusClass = health.overall_status === 'healthy' ? 'text-success' : 
                       health.overall_status === 'degraded' ? 'text-warning' : 'text-danger';
    
    $('#health-status').text(statusText).removeClass('text-success text-warning text-danger').addClass(statusClass);
}

function startAutoRefresh() {
    setInterval(() => {
        if (!realtimeState.isStreaming) {
            loadRealtimeStats();
        }
    }, REALTIME_CONFIG.autoRefreshInterval);
    
    // Mostrar indicador de auto-refresh
    setInterval(() => {
        $('#auto-refresh-indicator').fadeIn(300).delay(2000).fadeOut(300);
    }, REALTIME_CONFIG.autoRefreshInterval);
}

function startHealthCheck() {
    setInterval(() => {
        $.get('{{ url("/reportes/cartera-abonos-realtime/health") }}')
            .done(function(data) {
                updateHealthUI(data);
            });
    }, REALTIME_CONFIG.healthCheckInterval);
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
    realtimeState.dataTable.ajax.reload();
    loadRealtimeStats();
}

function showError(message) {
    console.error(message);
    // Implementar notificación toast
}

function showSuccess(message) {
    console.log(message);
    // Implementar notificación toast
}

// Inicialización
$(document).ready(function() {
    initializeRealtimeDataTable();
    setupRealtimeEventHandlers();
    updateCurrentPeriodDisplay();
    
    // Exponer funciones globalmente
    window.carteraAbonosRealtime = {
        state: realtimeState,
        forceSync: forceSync,
        healthCheck: performHealthCheck,
        toggleStreaming: toggleStreaming,
        refresh: () => realtimeState.dataTable.ajax.reload()
    };
});
</script>
@endsection