// JavaScript Ultra-Fast para 500 usuarios concurrentes
// Arquitectura: Pre-carga + Filtrado 100% Cliente-Side + Zero Database Queries

$(document).ready(function() {
    // Configuración Ultra-Fast
    const ULTRA_FAST_CONFIG = {
        debounceTime: 100, // Más rápido para 500 usuarios
        maxRecords: 50000, // Límite para performance cliente-side
        preloadTimeout: 10000, // 10 segundos para pre-carga
        incrementalUpdateInterval: 300000, // 5 minutos
        maxConcurrentUsers: 500
    };
    
    // Estado Global de Datos
    let dataState = {
        preloadedData: null,
        filteredData: [],
        originalData: [],
        isPreloaded: false,
        isLoading: false,
        lastFilter: {},
        stats: {
            totalRecords: 0,
            filteredRecords: 0,
            loadTime: 0,
            cacheHit: false
        }
    };
    
    // Motor de Búsqueda Cliente-Side Ultra-Rápido
    class ClientSideSearchEngine {
        constructor(data) {
            this.data = data;
            this.indexes = this.createIndexes(data);
        }
        
        // Crear índices para búsqueda instantánea
        createIndexes(data) {
            const startTime = performance.now();
            
            const indexes = {
                plaza: {},
                tienda: {},
                fecha: {},
                nombre: {},
                rfc: {},
                factura: {},
                clave: {},
                texto: {} // Índice de texto completo
            };
            
            data.forEach((record, index) => {
                // Índices exactos
                this.addToIndex(indexes.plaza, record.plaza, index);
                this.addToIndex(indexes.tienda, record.tienda, index);
                this.addToIndex(indexes.fecha, record.fecha, index);
                this.addToIndex(indexes.nombre, record.nombre, index);
                this.addToIndex(indexes.rfc, record.rfc, index);
                this.addToIndex(indexes.factura, record.factura, index);
                this.addToIndex(indexes.clave, record.clave, index);
                
                // Índice de texto completo para búsqueda general
                const searchText = [
                    record.plaza,
                    record.tienda,
                    record.nombre,
                    record.rfc,
                    record.factura,
                    record.clave
                ].join(' ').toLowerCase();
                
                this.addToIndex(indexes.texto, searchText, index);
            });
            
            const indexTime = performance.now() - startTime;
            console.log(`Índices creados en ${indexTime.toFixed(2)}ms para ${data.length} registros`);
            
            return indexes;
        }
        
        addToIndex(index, key, position) {
            if (!key) return;
            
            const normalizedKey = key.toString().toLowerCase();
            if (!index[normalizedKey]) {
                index[normalizedKey] = [];
            }
            index[normalizedKey].push(position);
        }
        
        // Búsqueda instantánea usando índices
        search(filters) {
            const startTime = performance.now();
            
            let resultIndices = null;
            
            // Búsqueda por plaza (índice exacto)
            if (filters.plaza) {
                resultIndices = this.indexes.plaza[filters.plaza.toLowerCase()] || [];
            }
            
            // Búsqueda por tienda (índice exacto)
            if (filters.tienda) {
                const tiendaIndices = this.indexes.tienda[filters.tienda.toLowerCase()] || [];
                resultIndices = resultIndices ? 
                    this.intersectArrays(resultIndices, tiendaIndices) : 
                    tiendaIndices;
            }
            
            // Búsqueda por rango de fechas
            if (filters.start || filters.end) {
                const dateIndices = this.searchByDateRange(filters.start, filters.end);
                resultIndices = resultIndices ? 
                    this.intersectArrays(resultIndices, dateIndices) : 
                    dateIndices;
            }
            
            // Búsqueda de texto (índice de texto completo)
            if (filters.search) {
                const textIndices = this.searchByText(filters.search);
                resultIndices = resultIndices ? 
                    this.intersectArrays(resultIndices, textIndices) : 
                    textIndices;
            }
            
            // Si no hay filtros, usar todos los datos
            if (!resultIndices) {
                resultIndices = Array.from({length: this.data.length}, (_, i) => i);
            }
            
            // Construir resultado final
            const result = resultIndices.map(index => this.data[index]);
            
            const searchTime = performance.now() - startTime;
            console.log(`Búsqueda completada en ${searchTime.toFixed(2)}ms - ${result.length} resultados`);
            
            return result;
        }
        
        // Búsqueda por rango de fechas
        searchByDateRange(startDate, endDate) {
            const start = startDate ? new Date(startDate) : new Date('1900-01-01');
            const end = endDate ? new Date(endDate) : new Date('2100-12-31');
            
            const indices = [];
            
            // Como los datos están ordenados por fecha, podemos usar búsqueda binaria
            for (let i = 0; i < this.data.length; i++) {
                const recordDate = new Date(this.data[i].fecha);
                if (recordDate >= start && recordDate <= end) {
                    indices.push(i);
                } else if (recordDate > end) {
                    break; // Los datos están ordenados
                }
            }
            
            return indices;
        }
        
        // Búsqueda de texto completo
        searchByText(searchText) {
            const search = searchText.toLowerCase();
            const indices = [];
            
            // Buscar en índice de texto
            for (const key in this.indexes.texto) {
                if (key.includes(search)) {
                    indices.push(...this.indexes.texto[key]);
                }
            }
            
            // Eliminar duplicados
            return [...new Set(indices)];
        }
        
        // Intersección de arrays (para filtros múltiples)
        intersectArrays(arr1, arr2) {
            const set2 = new Set(arr2);
            return arr1.filter(item => set2.has(item));
        }
    }
    
    // Inicializar sistema ultra-fast
    function initializeUltraFastSystem() {
        console.log('🚀 Iniciando sistema Ultra-Fast para 500 usuarios...');
        
        // Mostrar estado de carga
        showLoadingState();
        
        // Pre-cargar datos (única llamada a servidor)
        preloadData()
            .then(data => {
                dataState.preloadedData = data;
                dataState.originalData = data.data || [];
                dataState.isPreloaded = true;
                dataState.stats.totalRecords = data.data?.length || 0;
                dataState.stats.cacheHit = data.meta?.cache_hit || false;
                
                // Inicializar motor de búsqueda
                window.searchEngine = new ClientSideSearchEngine(dataState.originalData);
                
                // Ocultar estado de carga
                hideLoadingState();
                
                // Mostrar estadísticas
                updateStatsDisplay();
                
                // Habilitar interfaz
                enableInterface();
                
                // Iniciar actualizaciones incrementales
                startIncrementalUpdates();
                
                console.log('✅ Sistema Ultra-Fast listo para uso');
            })
            .catch(error => {
                console.error('❌ Error en pre-carga:', error);
                showErrorState(error);
            });
    }
    
    // Pre-cargar datos desde servidor
    function preloadData() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('Timeout en pre-carga de datos'));
            }, ULTRA_FAST_CONFIG.preloadTimeout);
            
            $.ajax({
                url: '{{ url("/reportes/cartera-abonos-ultra-fast/preload") }}',
                method: 'GET',
                timeout: ULTRA_FAST_CONFIG.preloadTimeout,
                success: function(response) {
                    clearTimeout(timeout);
                    if (response.status === 'success') {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.message || 'Error en pre-carga'));
                    }
                },
                error: function(xhr, status, error) {
                    clearTimeout(timeout);
                    reject(new Error(`Error AJAX: ${error}`));
                }
            });
        });
    }
    
    // Aplicar filtros cliente-side (instantáneo)
    function applyFiltersInstant() {
        if (!dataState.isPreloaded || !window.searchEngine) {
            return;
        }
        
        const startTime = performance.now();
        
        // Obtener filtros
        const filters = {
            plaza: $('#plaza').val().trim(),
            tienda: $('#tienda').val().trim(),
            start: $('#period_start').val(),
            end: $('#period_end').val(),
            search: $('#search-input').val().trim()
        };
        
        // Eliminar filtros vacíos
        Object.keys(filters).forEach(key => {
            if (!filters[key]) {
                delete filters[key];
            }
        });
        
        // Ejecutar búsqueda cliente-side
        dataState.filteredData = window.searchEngine.search(filters);
        dataState.lastFilter = filters;
        dataState.stats.filteredRecords = dataState.filteredData.length;
        
        // Actualizar DataTable con resultados
        updateDataTableWithFilteredData();
        
        const filterTime = performance.now() - startTime;
        console.log(`Filtros aplicados en ${filterTime.toFixed(2)}ms - ${dataState.stats.filteredRecords} resultados`);
        
        // Actualizar estadísticas
        updateStatsDisplay();
    }
    
    // Actualizar DataTable con datos filtrados
    function updateDataTableWithFilteredData() {
        if (!window.dataTable) {
            return;
        }
        
        // Limpiar DataTable actual
        window.dataTable.clear();
        
        // Agregar nuevos datos
        dataState.filteredData.forEach(record => {
            window.dataTable.row.add([
                record.plaza || '',
                record.tienda || '',
                record.fecha ? moment(record.fecha).format('DD/MM/YYYY') : '',
                record.fecha_vta ? moment(record.fecha_vta).format('DD/MM/YYYY') : '',
                record.concepto || '',
                record.tipo || '',
                record.factura || '',
                record.clave || '',
                record.rfc || '',
                record.nombre || '',
                formatCurrency(record.monto_fa || 0),
                formatCurrency(record.monto_dv || 0),
                formatCurrency(record.monto_cd || 0),
                record.dias_cred || 0,
                formatDaysVencidos(record.dias_vencidos || 0)
            ]);
        });
        
        // Dibujar tabla
        window.dataTable.draw();
        
        // Actualizar contador
        $('#record-count').text(`${dataState.stats.filteredRecords} registros`);
    }
    
    // Formatear moneda
    function formatCurrency(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(amount);
    }
    
    // Formatear días vencidos
    function formatDaysVencidos(days) {
        const numDays = parseInt(days);
        if (numDays > 0) {
            return `<span class="badge bg-danger">${numDays}</span>`;
        } else if (numDays < 0) {
            return `<span class="badge bg-warning">${Math.abs(numDays)}</span>`;
        }
        return `<span class="badge bg-success">0</span>`;
    }
    
    // Event handlers optimizados
    function setupEventHandlers() {
        let searchTimeout;
        
        // Botón de búsqueda
        $('#btn_search').on('click', function() {
            applyFiltersInstant();
        });
        
        // Filtros con debounce ultra-rápido
        $('#plaza, #tienda, #period_start, #period_end').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFiltersInstant, ULTRA_FAST_CONFIG.debounceTime);
        });
        
        // Búsqueda general con debounce
        $('#search-input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyFiltersInstant, ULTRA_FAST_CONFIG.debounceTime);
        });
        
        // Reset de filtros
        $('#btn_reset_filters').on('click', function() {
            resetFilters();
        });
        
        // Forzar pre-carga
        $('#btn_force_preload').on('click', function() {
            forcePreload();
        });
        
        // Exportaciones (usando datos pre-cargados)
        $('#btn_export_excel').on('click', function() {
            exportData('excel');
        });
        
        $('#btn_export_csv').on('click', function() {
            exportData('csv');
        });
        
        $('#btn_export_pdf').on('click', function() {
            exportData('pdf');
        });
    }
    
    // Reset de filtros
    function resetFilters() {
        $('#plaza').val('');
        $('#tienda').val('');
        $('#period_start').val('');
        $('#period_end').val('');
        $('#search-input').val('');
        
        // Aplicar filtros (mostrará todos los datos)
        applyFiltersInstant();
    }
    
    // Forzar pre-carga
    function forcePreload() {
        showLoadingState();
        
        $.post('{{ url("/reportes/cartera-abonos-ultra-fast/force-preload") }}')
            .done(function(response) {
                if (response.status === 'success') {
                    showSuccess('Pre-carga forzada exitosamente');
                    // Recargar sistema
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showError('Error al forzar pre-carga');
                }
            })
            .fail(function() {
                showError('Error al forzar pre-carga');
            })
            .always(function() {
                hideLoadingState();
            });
    }
    
    // Exportar datos (cliente-side)
    function exportData(format) {
        if (!dataState.isPreloaded) {
            showError('No hay datos pre-cargados para exportar');
            return;
        }
        
        // Usar datos filtrados actuales
        const dataToExport = dataState.filteredData.length > 0 ? 
            dataState.filteredData : 
            dataState.originalData;
        
        // Crear formulario de exportación
        const form = $('<form>', {
            'method': 'POST',
            'action': `{{ url("/reportes/cartera-abonos-ultra-fast/export-data") }}?format=${format}`,
            'target': '_blank'
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'filters',
            'value': JSON.stringify(dataState.lastFilter)
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    // Actualizaciones incrementales en background
    function startIncrementalUpdates() {
        setInterval(function() {
            $.get('{{ url("/reportes/cartera-abonos-ultra-fast/incremental-update") }}')
                .done(function(response) {
                    if (response.status === 'success' && response.result.records_updated > 0) {
                        console.log(`Actualización incremental: ${response.result.records_updated} registros`);
                        // Opcional: mostrar notificación de actualización
                        showUpdateNotification(response.result.records_updated);
                    }
                })
                .fail(function() {
                    console.warn('Error en actualización incremental');
                });
        }, ULTRA_FAST_CONFIG.incrementalUpdateInterval);
    }
    
    // Funciones de UI
    function showLoadingState() {
        dataState.isLoading = true;
        $('#loading-overlay').show();
        $('#loading-message').text('Pre-cargando datos del periodo anterior...');
    }
    
    function hideLoadingState() {
        dataState.isLoading = false;
        $('#loading-overlay').hide();
    }
    
    function showErrorState(error) {
        hideLoadingState();
        $('#error-overlay').show();
        $('#error-message').text('Error: ' + error.message);
    }
    
    function enableInterface() {
        $('#search-controls :input').prop('disabled', false);
        $('#export-controls :button').prop('disabled', false);
    }
    
    function updateStatsDisplay() {
        $('#stats-total-records').text(dataState.stats.totalRecords.toLocaleString());
        $('#stats-filtered-records').text(dataState.stats.filteredRecords.toLocaleString());
        $('#stats-load-time').text(dataState.stats.loadTime.toFixed(0) + 'ms');
        $('#stats-cache-status').text(dataState.stats.cacheHit ? 'Cache Hit' : 'Cache Miss');
        $('#stats-data-source').text('Redis Preload');
    }
    
    function showSuccess(message) {
        // Implementar notificación toast
        console.log('✅', message);
    }
    
    function showError(message) {
        // Implementar notificación toast
        console.error('❌', message);
    }
    
    function showUpdateNotification(recordsUpdated) {
        // Implementar notificación sutil de actualización
        console.log(`🔄 Datos actualizados: ${recordsUpdated} registros`);
    }
    
    // Inicializar DataTable (vacío inicialmente)
    function initializeDataTable() {
        window.dataTable = $('#report-table').DataTable({
            data: [],
            columns: [
                { data: 'plaza', className: 'text-center' },
                { data: 'tienda', className: 'text-center' },
                { data: 'fecha', className: 'text-center' },
                { data: 'fecha_vta', className: 'text-center' },
                { data: 'concepto', className: 'text-center' },
                { data: 'tipo', className: 'text-center' },
                { data: 'factura', className: 'text-center' },
                { data: 'clave', className: 'text-center' },
                { data: 'rfc', className: 'text-center' },
                { data: 'nombre' },
                { data: 'monto_fa', className: 'text-end' },
                { data: 'monto_dv', className: 'text-end' },
                { data: 'monto_cd', className: 'text-end' },
                { data: 'dias_cred', className: 'text-center' },
                { data: 'dias_vencidos', className: 'text-center' }
            ],
            language: {
                emptyTable: "Esperando datos pre-cargados...",
                zeroRecords: "No se encontraron resultados con los filtros aplicados",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros"
            },
            pageLength: 50,
            lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
            searching: false, // Deshabilitar búsqueda de DataTables (usamos la nuestra)
            ordering: true,
            order: [[0, 'asc'], [1, 'asc'], [2, 'desc']]
        });
    }
    
    // Inicialización completa
    initializeDataTable();
    setupEventHandlers();
    initializeUltraFastSystem();
    
    // Exponer funciones globalmente para debugging
    window.carteraAbonosUltraFast = {
        dataState: dataState,
        applyFilters: applyFiltersInstant,
        forcePreload: forcePreload,
        exportData: exportData,
        searchEngine: window.searchEngine
    };
    
    console.log('🎯 Sistema Ultra-Fast inicializado - Ready para 500 usuarios concurrentes');
});