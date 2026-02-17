@extends('adminlte::page')

@section('title', 'Metas Mensual')

@section('content_header')
    <h1>Importar metas mensuales desde Excel</h1>

<!-- Modal: Periodo search (opened from Nueva Meta button) -->
<div class="modal fade" id="modalPeriodo" tabindex="-1" role="dialog" aria-labelledby="modalPeriodoLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="GET" action="{{ route('metas.index') }}">
        <div class="modal-header">
          <h5 class="modal-title" id="modalPeriodoLabel">Buscar Periodo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="modalPeriodo_periodo">Periodo</label>
            <input id="modalPeriodo_periodo" class="form-control" name="periodo" placeholder="YYYY-MM" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Meta Modal (robust) -->
<!-- Modal Edit eliminado: reemplazado por la versión única con modalCreateMain -->
<!-- Modal para Días Feriados -->
<div class="modal fade" id="modalFeriados" tabindex="-1" role="dialog" aria-labelledby="modalFeriadosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFeriadosLabel">¿Existen días feriados en el período?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Seleccione los días que serán feriados y ajuste su valor:</p>
        <div class="row">
          <div class="col-6">
            <h6>Lista de días del período</h6>
            <div id="dias-feriados-list" style="max-height: 300px; overflow-y: auto;">
              <!-- Los días se cargarán dinámicamente -->
            </div>
          </div>
          <div class="col-6">
            <h6>Días seleccionados como feriados</h6>
            <div id="dias-seleccionados-list" style="max-height: 300px; overflow-y: auto;">
              <!-- Días seleccionados aparecerán aquí -->
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="generar-con-feriados-btn">Generar Días con Feriados</button>
      </div>
    </div>
  </div>
</div>
<!-- Nueva Meta modal (nuevo id para robustez) -->
<div class="modal fade" id="modalCreateMain" tabindex="-1" role="dialog" aria-labelledby="modalCreateMainLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ route('metas.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalCreateMainLabel">Nueva Meta</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="modalCreate_plaza">Plaza</label>
            <input id="modalCreate_plaza" class="form-control" name="plaza" required>
          </div>
          <div class="form-group">
            <label for="modalCreate_tienda">Tienda</label>
            <input id="modalCreate_tienda" class="form-control" name="tienda" required>
          </div>
          <div class="form-group">
            <label for="modalCreate_periodo">Periodo</label>
            <input id="modalCreate_periodo" class="form-control" name="periodo" placeholder="YYYY-MM" required>
          </div>
          <div class="form-group">
            <label for="modalCreate_meta">Meta</label>
            <input id="modalCreate_meta" class="form-control" name="meta" type="number" step="any" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary" data-toggle="tooltip" title="Guardar"><i class="fas fa-check"></i></button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Meta Modal (robust) -->
<div class="modal fade" id="modalEdit" tabindex="-1" role="dialog" aria-labelledby="modalEditLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="{{ route('metas.update') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditLabel">Editar Meta</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="old_plaza" id="edit_old_plaza">
          <input type="hidden" name="old_tienda" id="edit_old_tienda">
          <input type="hidden" name="old_periodo" id="edit_old_periodo">
          <div class="form-group">
            <label for="edit_plaza">Plaza</label>
            <input id="edit_plaza" class="form-control" name="plaza" required>
          </div>
          <div class="form-group">
            <label for="edit_tienda">Tienda</label>
            <input id="edit_tienda" class="form-control" name="tienda" required>
          </div>
          <div class="form-group">
            <label for="edit_periodo">Periodo</label>
            <input id="edit_periodo" class="form-control" name="periodo" required>
          </div>
          <div class="form-group">
            <label for="edit_meta">Meta</label>
            <input id="edit_meta" class="form-control" name="meta" type="number" step="any" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary" data-toggle="tooltip" title="Actualizar"><i class="fas fa-check"></i></button>
        </div>
      </form>
    </div>
  </div>
</div>

    @stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
            var metasDiasGenerateUrl = "{{ route('metas_dias.generate') }}";
            $('#tabla-met-mensual').DataTable({
                pageLength: 25,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false
            });

            // Initialize DataTables for other tabs
            $('#tabla-metas-diarias').DataTable({
                pageLength: 25,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false
            });

            $('#tabla-metas-detalles').DataTable({
                pageLength: 25,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false
            });

            // Edit button populates modalEdit with row data (with robust data attributes)
            $(document).on('click', '.edit-btn', function() {
                var old_plaza = $(this).data('oldPlaza') || $(this).data('old-plaza');
                var old_tienda = $(this).data('oldTienda') || $(this).data('old-tienda');
                var old_periodo = $(this).data('oldPeriodo') || $(this).data('old-periodo');
                var plaza = $(this).data('plaza') || $(this).data('Plaza');
                var tienda = $(this).data('tienda') || $(this).data('Tienda');
                var periodo = $(this).data('periodo') || $(this).data('Periodo');
                var meta = $(this).data('meta') || $(this).data('Meta');

                // Fallback: read from table row if data attributes are missing
                var row = $(this).closest('tr');
                if (!plaza) {
                    plaza = row.find('td').eq(0).text().trim();
                }
                if (!tienda) {
                    tienda = row.find('td').eq(1).text().trim();
                }
                if (!periodo) {
                    periodo = row.find('td').eq(2).text().trim();
                }
                if (!meta) {
                    meta = row.find('td').eq(3).text().trim();
                }

                $('#edit_old_plaza').val(old_plaza);
                $('#edit_old_tienda').val(old_tienda);
                $('#edit_old_periodo').val(old_periodo);
                $('#edit_plaza').val(plaza);
                $('#edit_tienda').val(tienda);
                $('#edit_periodo').val(periodo);
                $('#edit_meta').val(meta);

                // Ensure modal shows even if Bootstrap tooltip/modal loading order is off
                if (typeof $.fn.modal === 'function') {
                    $('#modalEdit').modal('show');
                }
            });

            // Update custom file input label when a file is selected (Bootstrap-style)
            $(document).on('change', '.custom-file-input', function(e){
                var fileName = e.target.files[0] ? e.target.files[0].name : 'Archivo Excel';
                $(this).next('.custom-file-label').html(fileName);
            });
            // Initialize Bootstrap tooltips gracefully (lazy load bootstrap if needed)
            (function initTooltipsGracefully(){
                function tryInit(){
                    if (typeof $.fn.tooltip === 'function') {
                        $('[data-toggle="tooltip"]').tooltip();
                    } else {
                        // Try to load Bootstrap bundle dynamically
                        var s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js';
                        s.integrity = '';
                        s.crossOrigin = 'anonymous';
                        s.onload = function(){
                            if (typeof $.fn.tooltip === 'function') {
                                $('[data-toggle="tooltip"]').tooltip();
                            } else {
                                console.warn('Bootstrap loaded but tooltips still unavailable.');
                            }
                        };
                        s.onerror = function(){
                            console.warn('Failed to load Bootstrap; tooltips disabled.');
                        };
                        document.head.appendChild(s);
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', tryInit);
                } else {
                    tryInit();
                }
            })();

            // Sync period inputs when toolbar period changes
            $('#toolbar-periodo').on('change', function() {
                var periodValue = $(this).val();
                if (periodValue) {
                    // Update modal period input
                    $('#modalPeriodo_periodo').val(periodValue);
                    // Update create modal period input
                    $('#modalCreate_periodo').val(periodValue);
                    // Update edit modal period input
                    $('#edit_periodo').val(periodValue);
                }
            });

            // Sync period input when modal period is submitted
            $(document).on('submit', '#modalPeriodo form', function() {
                var periodValue = $('#modalPeriodo_periodo').val();
                if (periodValue) {
                    $('#toolbar-periodo').val(periodValue);
                }
            });

            // Handler para mostrar modal de feriados antes de generar días
            $('#generate-dias-btn').off('click').on('click', function(e) {
                e.preventDefault();
                var periodo = "{{ $currentPeriodo ?? '' }}" || $('#toolbar-periodo').val();
                
                if (!periodo) {
                    alert('Debe seleccionar un período primero');
                    return;
                }
                
                // Cargar días del período para selección de feriados
                $.get('/api/dias-periodo?periodo=' + periodo, function(data) {
                    var html = '';
                    data.dias.forEach(function(dia) {
                        var isChecked = dia.valor_dia == 0.5 || dia.valor_dia == 0 ? 'checked' : '';
                        var valorFeriado = dia.valor_dia == 0.5 ? '0.5' : (dia.valor_dia == 0 ? '0' : '0.5');
                        html += `
                            <div class="form-check mb-2">
                                <input class="form-check-input feriado-check" type="checkbox" value="${dia.fecha}" data-dia="${dia.dia_semana}" data-valor-normal="${dia.valor_dia}">
                                <label class="form-check-label">
                                    ${dia.fecha} (${dia.nombre_dia}) - Valor normal: ${dia.valor_dia}
                                    <select class="form-control form-control-sm mt-1 valor-feriado" style="display:none;">
                                        <option value="0.5" ${valorFeriado == '0.5' ? 'selected' : ''}>Feriado (0.5)</option>
                                        <option value="0" ${valorFeriado == '0' ? 'selected' : ''}>Feriado completo (0)</option>
                                    </select>
                                </label>
                            </div>
                        `;
                    });
                    $('#dias-feriados-list').html(html);
                    $('#dias-seleccionados-list').html('');
                    $('#modalFeriados').modal('show');
                }).fail(function() {
                    // Si falla la API, continuar con proceso normal
                    generarDiasNormal(periodo);
                });
            });

            // Manejar checkboxes de feriados
            $(document).on('change', '.feriado-check', function() {
                var isChecked = $(this).is(':checked');
                $(this).siblings('label').find('.valor-feriado').toggle(isChecked);
                actualizarListaSeleccionados();
            });

            // Actualizar lista de seleccionados
            function actualizarListaSeleccionados() {
                var html = '';
                $('.feriado-check:checked').each(function() {
                    var fecha = $(this).val();
                    var valor = $(this).siblings('label').find('.valor-feriado').val();
                    html += `<div>${fecha} - Valor: ${valor}</div>`;
                });
                $('#dias-seleccionados-list').html(html || '<p class="text-muted">No hay días seleccionados</p>');
            }

            // Generar días con feriados
            $('#generar-con-feriados-btn').on('click', function() {
                var periodo = "{{ $currentPeriodo ?? '' }}" || $('#toolbar-periodo').val();
                var feriados = [];
                
                $('.feriado-check:checked').each(function() {
                    feriados.push({
                        fecha: $(this).val(),
                        valor: parseFloat($(this).siblings('label').find('.valor-feriado').val())
                    });
                });
                
                $('#modalFeriados').modal('hide');
                generarDiasConFeriados(periodo, feriados);
            });

            // Función para generar días con feriados
            function generarDiasConFeriados(periodo, feriados) {
                const token = "{{ csrf_token() }}";
                var generateDiasUrl = "{{ route('metas_dias.generate') }}";
                
                $.post(generateDiasUrl, { 
                    periodo: periodo, 
                    feriados: feriados,
                    _token: token 
                }, function(res){
                    if (res && res.message) {
                        if (typeof Swal !== 'undefined' && Swal.fire) {
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, title: res.message });
                        } else {
                            alert(res.message);
                        }
                    }
                    if (res && res.summary) {
                        const s = res.summary;
                        const cont = document.getElementById('summary-content');
                        if (cont) {
                            cont.textContent = `Días trabajables: ${s.days_workable ?? 0} | Total Meta: ${s.total_meta ?? 0} | Total Días: ${s.total_days ?? 0} | Meta diaria promedio: ${((s.avg_meta_per_day ?? 0)).toFixed(2)}`;
                        }
                    }
                }).fail(function(xhr){
                    var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error generate';
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({ title: 'Error', text: err });
                    } else {
                        alert(err);
                    }
                });
            }

            // Función para generar días normal (si falla la API)
            function generarDiasNormal(periodo) {
                const token = "{{ csrf_token() }}";
                var generateDiasUrl = "{{ route('metas_dias.generate') }}";
                
                $.post(generateDiasUrl, { 
                    periodo: periodo, 
                    _token: token 
                }, function(res){
                    if (res && res.message) {
                        if (typeof Swal !== 'undefined' && Swal.fire) {
                            Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, title: res.message });
                        } else {
                            alert(res.message);
                        }
                    }
                    if (res && res.summary) {
                        const s = res.summary;
                        const cont = document.getElementById('summary-content');
                        if (cont) {
                            cont.textContent = `Días trabajables: ${s.days_workable ?? 0} | Total Meta: ${s.total_meta ?? 0} | Total Días: ${s.total_days ?? 0} | Meta diaria promedio: ${((s.avg_meta_per_day ?? 0)).toFixed(2)}`;
                        }
                    }
                }).fail(function(xhr){
                    var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error generate';
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({ title: 'Error', text: err });
                    } else {
                        alert(err);
                    }
                });
            }
        });

        // Handler for generate dias button (Enfoque A: generar dias y luego insertar en metas)
        $('#generate-dias-btn').on('click', function(e) {
            e.preventDefault();
            var periodo = "{{ $currentPeriodo ?? '' }}";
            if (!periodo) {
                alert('Periodo actual no definido');
                return;
            }
            const token = "{{ csrf_token() }}";
            var generateDiasUrl = "{{ route('metas_dias.generate') }}";
            $.post(generateDiasUrl, { periodo: periodo, _token: token }, function(res){
                // Notificar proceso
                if (res && res.message) {
                    if (typeof Swal !== 'undefined' && Swal.fire) {
                        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, title: res.message });
                    } else {
                        alert(res.message);
                    }
                }
            // Render resumen de forma segura usando DOM puro
            if (res && res.summary) {
                const s = res.summary;
                const cont = document.getElementById('summary-content');
                if (cont) {
                    cont.textContent = `Días trabajables: ${s.days_workable ?? 0} | Total Meta: ${s.total_meta ?? 0} | Total Días: ${s.total_days ?? 0} | Meta diaria promedio: ${((s.avg_meta_per_day ?? 0)).toFixed(2)}`;
                }
            }
            }).fail(function(xhr){
                var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error generate';
                if (typeof Swal !== 'undefined' && Swal.fire) {
                    Swal.fire({ title: 'Error', text: err });
                } else {
                    alert(err);
                }
        });
    });

    // Performance Test function
    function runPerformanceTest() {
        const periodo = $('[name="periodo"]').val() || '{{ $currentPeriodo }}';
        
        Swal.fire({
            title: 'Ejecutando Pruebas de Velocidad',
            text: 'Período: ' + periodo,
            timerProgressBar: true,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '{{ route("metas.performance.test") }}',
            method: 'GET',
            data: { periodo: periodo },
            success: function(response) {
                Swal.close();
                showPerformanceResults(response);
            },
            error: function() {
                Swal.close();
                Swal.fire('Error', 'Error al ejecutar pruebas', 'error');
            }
        });
    }

    function showPerformanceResults(results) {
        console.log('showPerformanceResults called with:', results);
        
        // Debug: Verificar estructura de results
        if (!results || typeof results !== 'object') {
            console.warn('showPerformanceResults: results is null or not an object', results);
            return;
        }
        
        // Debug: Verificar propiedades específicas
        if (!results.pruebas || !results.resumen) {
            console.warn('showPerformanceResults: missing properties in results object');
            return;
        }
        
        console.log('Results structure OK, proceeding...');
        
        let html = '<div class="card"><div class="card-header"><h5>Resultados de Pruebas de Velocidad</h5></div><div class="card-body">';
        
        // Validar que results exista y tenga la estructura esperada
        if (!results || !results.pruebas) {
            html += '<div class="alert alert-warning">No hay resultados para mostrar</div>';
        } else {
            // Mostrar resultados individuales
            const pruebas = results.pruebas || {};
            Object.keys(pruebas).forEach(test => {
                const result = pruebas[test];
                const statusClass = result.status === 'OK' ? 'success' : 'danger';
                html += `<div class="alert alert-${statusClass}">
                    <strong>${test.toUpperCase()}:</strong> ${result.tiempo_ms}ms (${result.cantidad} registros) - ${result.status}
                </div>`;
            });
            
            // Mostrar resumen
            const resumen = results.resumen || {};
            const resumenClass = resumen.status_general === 'OK' ? 'success' : 'warning';
            html += `<div class="alert alert-${resumenClass}">
                <strong>RESUMEN:</strong><br>
                Tiempo Total: ${resumen.tiempo_total_ms}ms<br>
                Promedio: ${resumen.promedio_tiempo_ms}ms<br>
                Estado: ${resumen.status_general}
            </div>`;
            
            // Mostrar recomendaciones
            if (results.recomendaciones && results.recomendaciones.length > 0) {
                html += '<div class="alert alert-info"><strong>RECOMENDACIONES:</strong><ul>';
                results.recomendaciones.forEach(rec => {
                    html += `<li>${rec}</li>`;
                });
                html += '</ul></div>';
            }
        }
        
        html += '</div></div>';
        
        console.log('HTML generado:', html);
        
        Swal.fire({
            title: 'Resultados de Pruebas de Velocidad',
            html: html,
            width: 600
        });
    }

    // Delete confirmation with toastr feedback
    $(document).on('submit', '.delete-form', function(e) {
        e.preventDefault();
        if (!confirm('Eliminar esta meta?')) return;
        var form = this;
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: $(form).serialize(),
            success: function(){
                var row = $(form).closest('tr');
                row.fadeOut(200, function(){ 
                    row.remove(); 
                    toastr.success('Meta eliminada correctamente');
                });
            },
            error: function(xhr){
                console.error('Error al eliminar la meta.', xhr);
                toastr.error('Error al eliminar la meta');
            }
        });
    });

    // Generar Metas Button functionality
    $('#btnGenerarMetas').on('click', function() {
        const periodo = $('#toolbar-periodo').val() || '{{ $currentPeriodo }}';
        
        if (!periodo) {
            toastr.warning('Debe seleccionar un período para generar las metas.');
            return;
        }
        
        toastr.info('Procesando generación de metas...');
        
        $.ajax({
            url: '{{ route("metas.generar") }}',
            method: 'POST',
            data: {
                '_token': '{{ csrf_token() }}',
                'periodo': periodo
            },
            success: function(response) {
                console.log('Response from server:', response);
                
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                console.error('Error generating metas:', xhr);
                let errorMsg = 'Error de comunicación al generar metas';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg);
            }
        });
    });

    // Ensure only one generate button remains (remove duplicates if any)
    document.addEventListener('DOMContentLoaded', function(){
        var btns = document.querySelectorAll('#generate-dias-btn');
        if (btns.length > 1) {
            btns[1].remove();
        }
    });
    </script>
@endsection

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .text-right { text-align: right !important; }
        .bg-success { background-color: #d4edda !important; }
        .bg-danger { background-color: #f8d7da !important; }
        .bg-primary { background-color: #cce5ff !important; }
        #tabla-met-mensual_wrapper { padding: 0 10px; }
        table#tabla-met-mensual { width: 100%; }
        #tabla-metas-diarias_wrapper { padding: 0 10px; }
        table#tabla-metas-diarias { width: 100%; }
        #tabla-metas-detalles_wrapper { padding: 0 10px; }
        table#tabla-metas-detalles { width: 100%; }
    </style>
@stop


@section('content')
<!-- Logout control removed per request -->
    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="card card-default">
        <div class="card-body">
            <div class="row mb-3" id="metas-toolbar">
                <div class="col-12 d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div class="d-flex align-items-center flex-wrap" style="gap:10px;">
                <!-- Period Selector -->
                <div class="form-group mb-0">
                    <label for="toolbar-periodo" class="mr-2">Período:</label>
                    <input type="month" id="toolbar-periodo" name="periodo" class="form-control d-inline-block" style="width:auto;" value="{{ $currentPeriodo ?? date('Y-m') }}">
                </div>
                
                <!-- Import Form -->
                <form action="{{ route('metas.import') }}" method="POST" enctype="multipart/form-data" id="import-form-inline" class="d-inline-flex align-items-center" style="gap:8px;">
                        @csrf
                        <div class="custom-file" style="max-width:420px;">
                            <input type="file" class="custom-file-input" id="excel" name="excel" aria-label="Archivo Excel" required>
                            <label class="custom-file-label" for="excel">Archivo Excel</label>
                        </div>
                <button class="btn btn-primary" type="submit" aria-label="Importar" data-toggle="tooltip" title="Importar"><i class="fas fa-upload"></i></button>
                        </form>       
            </div>
            
        <div class="btn-group" role="group" aria-label="Metas actions">
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalCreateMain" aria-label="Nueva Meta" title="Nueva Meta"><i class="fas fa-plus"></i></button>
            <button class="btn btn-secondary btn-sm ml-2" data-toggle="modal" data-target="#modalPeriodo" aria-label="Buscar periodo" title="Buscar periodo"><i class="fas fa-search"></i></button>
            <button class="btn btn-secondary btn-sm ml-2" onclick="location.reload()" aria-label="Recargar" title="Recargar"><i class="fas fa-sync"></i></button>
            <button id="generate-dias-btn" class="btn btn-warning btn-sm ml-2" aria-label="Generar Metas Dias" data-toggle="tooltip" title="Generar Metas Dias"><i class="fas fa-upload"></i></button>
            <button type="button" class="btn btn-secondary btn-sm ml-2" onclick="runPerformanceTest()" title="Pruebas de Velocidad">
                <i class="fas fa-tachometer-alt"></i> Pruebas Velocidad
            </button>
            <button id="btnGenerarMetas" class="btn btn-info btn-sm ml-2" aria-label="Generar Metas" title="Generar Metas"><i class="fas fa-calculator"></i> Generar Metas</button>
            <!-- Publicar Metas button moved to a form for reliability -->
        </div>
                    </div>
                </div>
                </div>
        </div>
    </div>

    <!-- Bootstrap Tabs Navigation -->
<ul class="nav nav-tabs" id="metasTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="metas-mensual-tab" data-toggle="tab" href="#metas-mensual" role="tab" aria-controls="metas-mensual" aria-selected="true">Metas Mensual</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="metas-diarias-tab" data-toggle="tab" href="#metas-diarias" role="tab" aria-controls="metas-diarias" aria-selected="false">Metas Diarias</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="metas-detalles-tab" data-toggle="tab" href="#metas-detalles" role="tab" aria-controls="metas-detalles" aria-selected="false">Metas Detalles</a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content" id="metasTabsContent">
    <!-- Tab 1: Metas Mensual -->
    <div class="tab-pane fade show active" id="metas-mensual" role="tabpanel" aria-labelledby="metas-mensual-tab">
        @if (isset($metas_mensual) && $metas_mensual->count())
        <div class="card mt-3">
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0" id="tabla-met-mensual">
                    <thead class="thead-dark">
                        <tr>
                            <th>Plaza</th>
                            <th>Tienda</th>
                            <th>Periodo</th>
                            <th>Meta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metas_mensual as $row)
                        <tr>
                            <td>{{ $row->plaza }}</td>
                            <td>{{ $row->tienda }}</td>
                            <td>{{ $row->periodo }}</td>
                            <td>{{ $row->meta }}</td>
                            <td>
                <button class="btn btn-sm btn-info edit-btn" 
                data-toggle="modal" data-target="#modalEdit" data-toggle="tooltip" title="Editar"
                data-old-plaza="{{ $row->plaza }}"
                data-old-tienda="{{ $row->tienda }}"
                data-old-periodo="{{ $row->periodo }}"
                data-plaza="{{ $row->plaza }}"
                data-tienda="{{ $row->tienda }}"
                data-periodo="{{ $row->periodo }}"
                data-meta="{{ $row->meta }}"
            aria-label="Editar"><i class="fas fa-edit"></i></button>
                <form method="POST" action="{{ route('metas.destroy') }}" class="delete-form" style="display:inline-block;" data-toggle="tooltip" title="Eliminar">
                                    @csrf
                                    <input type="hidden" name="plaza" value="{{ $row->plaza }}">
                                    <input type="hidden" name="tienda" value="{{ $row->tienda }}">
                                    <input type="hidden" name="periodo" value="{{ $row->periodo }}">
                                    <button type="submit" class="btn btn-sm btn-danger" aria-label="Eliminar" data-toggle="tooltip" title="Eliminar"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <p class="text-muted mt-3">No hay metas para el periodo seleccionado.</p>
        @endif
    </div>

    <!-- Tab 2: Metas Diarias -->
    <div class="tab-pane fade" id="metas-diarias" role="tabpanel" aria-labelledby="metas-diarias-tab">
        @if (isset($metas_dias) && $metas_dias->count())
        <div class="card mt-3">
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0" id="tabla-metas-diarias">
                    <thead class="thead-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Período</th>
                            <th>Día Semana</th>
                            <th>Días Mes</th>
                            <th>Valor Día</th>
                            <th>Año</th>
                            <th>Mes Friedman</th>
                            <th>Semana Friedman</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metas_dias as $row)
                        <tr>
                            <td>{{ $row->fecha }}</td>
                            <td>{{ $row->periodo }}</td>
                            <td>{{ $row->dia_semana }}</td>
                            <td>{{ $row->dias_mes }}</td>
                            <td>{{ $row->valor_dia }}</td>
                            <td>{{ $row->anio }}</td>
                            <td>{{ $row->mes_friedman }}</td>
                            <td>{{ $row->semana_friedman }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <p class="text-muted mt-3">No existen datos para el período seleccionado en Metas Diarias</p>
        @endif
    </div>

    <!-- Tab 3: Metas Detalles -->
    <div class="tab-pane fade" id="metas-detalles" role="tabpanel" aria-labelledby="metas-detalles-tab">
        @if (isset($metas_detalles) && $metas_detalles->count())
        <div class="card mt-3">
            <div class="card-body p-0">
                <table class="table table-bordered table-sm mb-0" id="tabla-metas-detalles">
                    <thead class="thead-dark">
                        <tr>
                            <th>Plaza</th>
                            <th>Tienda</th>
                            <th>Fecha</th>
                            <th>Meta Total</th>
                            <th>Días Total</th>
                            <th>Valor Día</th>
                            <th>Meta Día</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metas_detalles as $row)
                        <tr>
                            <td>{{ $row->plaza }}</td>
                            <td>{{ $row->tienda }}</td>
                            <td>{{ $row->fecha }}</td>
                            <td>{{ $row->meta_total }}</td>
                            <td>{{ $row->dias_total }}</td>
                            <td>{{ $row->valor_dia }}</td>
                            <td>{{ $row->meta_dia }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <p class="text-muted mt-3">No existen datos para el período seleccionado en Metas Detalles</p>
        @endif
    </div>
</div>



    <p class="text-muted">Ejemplo de columnas en el Excel: plaza,tienda,periodo,meta. Cabecera: plaza,tienda,periodo,meta</p>
@stop
