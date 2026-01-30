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
    <script>
        $(document).ready(function() {
            var metasDiasGenerateUrl = "{{ route('metas_dias.generate') }}";
            $('#tabla-met-mensual').DataTable({
                pageLength: 25,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                order: [[0, 'asc']],
                responsive: true,
                autoWidth: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
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
        });
        // Handler for generate dias button (Enfoque A: generar dias y luego insertar en metas)
        $('#generate-dias-btn').on('click', function(e) {
            e.preventDefault();
            var periodo = '{{ $currentPeriodo ?? '' }}';
            if (!periodo) {
                alert('Periodo actual no definido');
                return;
            }
            const token = '{{ csrf_token() }}';
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
                // Render tablas si hay datos
                var html = '';
                if (res.dias && res.dias.length) {
                    html += '<div class="card mt-3"><div class="card-header">Metas Dias -Periodo ' + periodo + '</div><div class="card-body p-0"><table class="table table-hover table-bordered table-striped" id="tabla-dias-generated"><thead class="thead-dark"><tr><th>Fecha</th><th>Periodo</th><th>Dia Sem</th><th>Dias Mes</th><th>Valor Dia</th><th>Año</th><th>Mes Friedman</th><th>Semana Friedman</th></tr></thead><tbody>';
                    res.dias.forEach(function(r){
                        html += '<tr><td>'+ r.fecha +'</td><td>'+ r.periodo +'</td><td>'+ r.dia_semana +'</td><td>'+ r.dias_mes +'</td><td>'+ r.valor_dia +'</td><td>'+ r.anio +'</td><td>'+ r.mes_friedman +'</td><td>'+ r.semana_friedman +'</td></tr>';
                    });
                    html += '</tbody></table></div></div>';
                }
                if (res.metas && res.metas.length) {
                    html += '<div class="card mt-3"><div class="card-header">Metas -Periodo ' + periodo + '</div><div class="card-body p-0"><table class="table table-hover table-bordered table-striped" id="tabla-metas-generated"><thead class="thead-dark"><tr><th>Plaza</th><th>Tienda</th><th>Fecha</th><th>Meta</th><th>Dias Mes</th><th>Valor Dia</th><th>Computed</th></tr></thead><tbody>';
                    res.metas.forEach(function(m){
                        html += '<tr><td>'+ m.plaza +'</td><td>'+ m.tienda +'</td><td>'+ m.fecha +'</td><td>'+ m.meta +'</td><td>'+ m.dias_mes +'</td><td>'+ m.valor_dia +'</td><td>'+ (m.computed||'') +'</td></tr>';
                    });
                    html += '</tbody></table></div></div>';
                }
                if (html) {
                    document.getElementById('generated-tables').innerHTML = html;
                    // Optional: initialize DataTables on generated tables
                    var dt1 = document.getElementById('tabla-dias-generated');
                    if (dt1) $(dt1).DataTable({ paging: false, searching: false, info: false });
                    var dt2 = document.getElementById('tabla-metas-generated');
                    if (dt2) $(dt2).DataTable({ paging: false, searching: false, info: false });
                }
                // Recarga opcional para reflejar cambios si no se llenó con datos
            }).fail(function(xhr){
                var err = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Error generate';
            if (typeof Swal !== 'undefined' && Swal.fire) {
                    Swal.fire({ title: 'Error', text: err });
                } else {
                    alert(err);
                }
            });
        });
        
            // SweetAlert2 for delete confirmation (lazy-load if needed)
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
                    row.fadeOut(200, function(){ row.remove(); });
                    // Simple feedback without alert/dialogs
                    // If you want a visible notification, you can add a toast here later
                },
                error: function(xhr){
                    console.error('Error al eliminar la meta.', xhr);
                }
            });
        });
    </script>
    <script>
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
    <style>
        .text-right { text-align: right !important; }
        .bg-success { background-color: #d4edda !important; }
        .bg-danger { background-color: #f8d7da !important; }
        .bg-primary { background-color: #cce5ff !important; }
        #tabla-met-mensual_wrapper { padding: 0 10px; }
        table#tabla-met-mensual { width: 100%; }
    </style>
@stop

<!-- Generated data sections (metas_dias y metas) rendered here after generation -->
<!-- generated tables removed -->

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
                <div class="col-12 d-flex align-items-center justify-content-between">
            <form action="{{ route('metas.import') }}" method="POST" enctype="multipart/form-data" id="import-form-inline" class="d-inline-flex align-items-center" style="gap:8px;">
                    @csrf
                    <div class="custom-file" style="max-width:420px;">
                        <input type="file" class="custom-file-input" id="excel" name="excel" aria-label="Archivo Excel" required>
                        <label class="custom-file-label" for="excel">Archivo Excel</label>
                    </div>
            <button class="btn btn-primary" type="submit" aria-label="Importar" data-toggle="tooltip" title="Importar"><i class="fas fa-upload"></i></button>
                    </form>       
                        <div class="btn-group" role="group" aria-label="Metas actions">
      <button class="btn btn-success" data-toggle="modal" data-target="#modalCreateMain" aria-label="Nueva Meta" title="Nueva Meta" data-toggle="tooltip"><i class="fas fa-plus"></i></button>
                        <button class="btn btn-secondary ml-2" data-toggle="modal" data-target="#modalPeriodo" aria-label="Buscar periodo" title="Buscar periodo" data-toggle="tooltip"><i class="fas fa-search"></i></button>
                        <button class="btn btn-secondary ml-2" onclick="location.reload()" aria-label="Recargar" title="Recargar" data-toggle="tooltip"><i class="fas fa-sync"></i></button>
                        <button id="publicar-metas-top" class="btn btn-success btn-sm" aria-label="Publicar Metas" data-toggle="tooltip" title="Publicar Metas">
                        <button id="generate-dias-btn" class="btn btn-warning btn-sm" aria-label="Generar Metas Dias" data-toggle="tooltip" title="Generar Metas Dias"><i class="fas fa-upload"></i></button>
      <i class="fas fa-paper-plane"></i> Publicar
    </button>
                    </div>
                </div>
                </div>
        </div>
    </div>

    @if (isset($rows) && $rows->count())
    <div class="card">
        <div class="card-header" style="display:none"></div>
        <div class="card-body p-0">
            <table class="table table-hover table-bordered table-striped mb-0" id="tabla-met-mensual">
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
                    @foreach ($rows as $row)
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
    <p class="text-muted">No hay metas para el periodo seleccionado.</p>
    @endif



    <p class="text-muted">Ejemplo de columnas en el Excel: plaza,tienda,periodo,meta. Cabecera: plaza,tienda,periodo,meta</p>
@stop
