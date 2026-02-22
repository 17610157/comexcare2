// JavaScript optimizado para Cartera Abonos - Tiempo Real
$(document).ready(function() {
    // Configuración global
    const CONFIG = {
        debounceTime: 300,
        streamingEnabled: true,
        performanceMonitoring: true,
        autoRefreshInterval: 300000, // 5 minutos
        maxRetries: 3
    };
    
    // Estado de la aplicación
    let state = {
        dataTable: null,
        currentParams: {},
        isLoading: false,
        lastRequestTime: 0,
        retryCount: 0,
        performanceStats: {
            totalRequests: 0,
            averageResponseTime: 0,
            cacheHitRate: 0
        }
    };
    
    // Inicializar DataTable optimizado
    function initializeOptimizedDataTable() {
        state.dataTable = $('#report-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25, // Más registros por página para menos requests
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
                zeroRecords: "No se encontraron resultados",
                processing: "Cargando..."
            },
            ajax: {
                url: "{{ url('/reportes/cartera-abonos-optimized/data') }}",
                type: "GET",
                data: function(d) {
                    const startTime = performance.now();
                    
                    // Parámetros optimizados
                    const params = {
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        search: d.search.value,
                        plaza: $('#plaza').val().toUpperCase(),
                        tienda: $('#tienda').val().toUpperCase(),
                        period_start: $('#period_start').val(),
                        period_end: $('#period_end').val()
                    };
                    
                    state.currentParams = params;
                    return params;
                },
                dataSrc: function(json) {
                    const endTime = performance.now();
                    const responseTime = endTime - state.lastRequestTime;
                    
                    // Actualizar estadísticas de performance
                    if (CONFIG.performanceMonitoring && json.performance) {
                        updatePerformanceStats(json.performance);
                    }
                    
                    // Mostrar indicador de caché
                    if (json.performance && json.performance.cache_hit) {
                        showCacheIndicator(true);
                    } else {
                        showCacheIndicator(false);
                    }
                    
                    // Resetear contador de retries
                    state.retryCount = 0;
                    
                    return json.data;
                },
                error: function(xhr, error, thrown) {
                    console.error('Error en DataTable:', {xhr, error, thrown});
                    
                    // Reintentar automáticamente
                    if (state.retryCount < CONFIG.maxRetries) {
                        state.retryCount++;
                        setTimeout(() => {
                            state.dataTable.ajax.reload();
                        }, 1000 * state.retryCount);
                    } else {
                        showError('Error al cargar los datos. Por favor, recargue la página.');
                    }
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
                // Cargar estadísticas iniciales
                loadStats();
                
                // Configurar auto-refresh
                if (CONFIG.autoRefreshInterval > 0) {
                    setInterval(autoRefresh, CONFIG.autoRefreshInterval);
                }
            }
        });
    }
    
    // Event handlers optimizados con debounce
    function setupEventHandlers() {
        let searchTimeout;
        
        // Botones principales
        $('#btn_search').on('click', debounce(function() {
            state.dataTable.ajax.reload();
            loadStats();
        }, CONFIG.debounceTime));
        
        $('#btn_refresh').on('click', function() {
            // Invalidar caché y recargar
            invalidateCache();
            state.dataTable.ajax.reload();
            loadStats();
        });
        
        $('#btn_reset_filters').on('click', function() {
            resetFilters();
        });
        
        // Filtros de fecha con auto-refresh
        $('#period_start, #period_end').on('change', debounce(function() {
            state.dataTable.ajax.reload();
            updateCurrentPeriodDisplay();
            loadStats();
        }, CONFIG.debounceTime));
        
        // Filtros de plaza/tienda con validación mejorada
        $('#plaza, #tienda').on('input', function() {
            const input = $(this);
            const value = input.val().toUpperCase();
            
            // Validación en tiempo real
            if (input.attr('id') === 'plaza' && value.length <= 5) {
                validatePlazaFormat(input, value);
            } else if (input.attr('id') === 'tienda' && value.length <= 10) {
                validateTiendaFormat(input, value);
            }
        });
        
        $('#plaza, #tienda').on('change', debounce(function() {
            if ($(this)[0].checkValidity()) {
                state.dataTable.ajax.reload();
                loadStats();
            }
        }, CONFIG.debounceTime));
        
        // Búsqueda rápida con Enter
        $('#plaza, #tienda').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                clearTimeout(searchTimeout);
                
                if ($(this)[0].checkValidity()) {
                    state.dataTable.ajax.reload();
                    loadStats();
                } else {
                    showValidationError($(this));
                }
            }
        });
        
        // Exportaciones optimizadas
        $('#btn_excel').on('click', function() {
            showExportModal('excel');
        });
        
        $('#btn_csv').on('click', function() {
            showExportModal('csv');
        });
        
        $('#btn_pdf').on('click', function() {
            const url = buildExportUrl('pdf');
            window.open(url, '_blank');
        });
    }
    
    // Funciones de optimización
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
    
    function updatePerformanceStats(performance) {
        state.performanceStats.totalRequests++;
        
        const responseTime = performance.response_time_ms;
        state.performanceStats.averageResponseTime = 
            (state.performanceStats.averageResponseTime + responseTime) / 2;
        
        if (performance.cache_hit) {
            state.performanceStats.cacheHitRate = 
                (state.performanceStats.cacheHitRate + 1) / 2;
        }
        
        // Actualizar UI con stats
        updatePerformanceUI();
    }
    
    function updatePerformanceUI() {
        const stats = state.performanceStats;
        const cacheRate = (stats.cacheHitRate * 100).toFixed(1);
        const avgTime = stats.averageResponseTime.toFixed(0);
        
        $('#performance-stats').html(
            `<small class="text-muted">
                Tiempo respuesta: ${avgTime}ms | 
                Cache: ${cacheRate}% | 
                Requests: ${stats.totalRequests}
            </small>`
        );
    }
    
    function showCacheIndicator(isHit) {
        const indicator = $('#cache-indicator');
        if (isHit) {
            indicator.removeClass('bg-secondary').addClass('bg-success')
                     .text('CACHE').attr('title', 'Datos desde caché');
        } else {
            indicator.removeClass('bg-success').addClass('bg-secondary')
                     .text('DB').attr('title', 'Datos desde base de datos');
        }
    }
    
    // Cargar estadísticas en tiempo real
    function loadStats() {
        $.get('{{ url("/reportes/cartera-abonos-optimized/stats") }}', state.currentParams)
            .done(function(data) {
                updateStatsUI(data.stats);
                updateCacheUI(data.cache);
            })
            .fail(function() {
                console.warn('No se pudieron cargar las estadísticas');
            });
    }
    
    function updateStatsUI(stats) {
        $('#stats-total-abonos').text(stats.total_abonos?.toLocaleString() || '0');
        $('#stats-total-monto').text('$' + (stats.total_general?.toLocaleString(undefined, {minimumFractionDigits: 2}) || '0'));
        $('#stats-plazas').text(stats.unique_plazas || '0');
        $('#stats-tiendas').text(stats.unique_tiendas || '0');
        $('#stats-clientes').text(stats.unique_clientes || '0');
    }
    
    function updateCacheUI(cache) {
        $('#cache-keys').text(cache.total_keys || '0');
        $('#cache-memory').text(formatBytes(cache.memory_usage || 0));
    }
    
    // Auto-refresh
    function autoRefresh() {
        if (!state.isLoading) {
            state.dataTable.ajax.reload();
            loadStats();
        }
    }
    
    // Invalidar caché
    function invalidateCache() {
        $.post('{{ url("/reportes/cartera-abonos-optimized/invalidate-cache") }}')
            .done(function() {
                showSuccess('Caché invalidado correctamente');
            })
            .fail(function() {
                console.warn('No se pudo invalidar la caché');
            });
    }
    
    // Funciones de utilidad
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function validatePlazaFormat(input, value) {
        const plazaRegex = /^[A-Z0-9]{0,5}$/;
        const isValid = plazaRegex.test(value);
        
        input.toggleClass('border-danger', !isValid && value.length > 0);
        input.toggleClass('border-primary', isValid && value.length > 0);
        input.toggleClass('border-secondary', value.length === 0);
        
        return isValid;
    }
    
    function validateTiendaFormat(input, value) {
        const tiendaRegex = /^[A-Z0-9]{0,10}$/;
        const isValid = tiendaRegex.test(value);
        
        input.toggleClass('border-danger', !isValid && value.length > 0);
        input.toggleClass('border-primary', isValid && value.length > 0);
        input.toggleClass('border-secondary', value.length === 0);
        
        return isValid;
    }
    
    function showValidationError(input) {
        input.addClass('border-danger animate-shake');
        setTimeout(() => {
            input.removeClass('animate-shake');
        }, 1000);
    }
    
    function showError(message) {
        // Implementar notificación de error
        console.error(message);
    }
    
    function showSuccess(message) {
        // Implementar notificación de éxito
        console.log(message);
    }
    
    // Inicialización
    initializeOptimizedDataTable();
    setupEventHandlers();
    updateCurrentPeriodDisplay();
    
    // Exponer funciones globalmente para debugging
    window.carteraAbonos = {
        state: state,
        loadStats: loadStats,
        invalidateCache: invalidateCache,
        refresh: () => state.dataTable.ajax.reload()
    };
});