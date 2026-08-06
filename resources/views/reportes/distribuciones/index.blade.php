@extends('adminlte::page')
@section('title', 'Reporte de Distribuciones')

@section('content_header')
<h1>
    <i class="fas fa-upload"></i> Reporte de Distribuciones
    <small class="text-muted">Actividad de usuarios en distribuciones</small>
</h1>
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="stat-total-created">0</h3>
                    <p>Distribuciones Creadas</p>
                </div>
                <div class="icon"><i class="fas fa-plus-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="stat-total-active">0</h3>
                    <p>Activas</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="stat-total-deleted">0</h3>
                    <p>Eliminadas</p>
                </div>
                <div class="icon"><i class="fas fa-trash"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="stat-failed">0</h3>
                    <p>Targets Fallidos</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <div class="card bg-light mb-3">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Usuario</label>
                    <select id="filter-user" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Tipo</label>
                    <select id="filter-type" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="immediate">Inmediata</option>
                        <option value="scheduled">Programada</option>
                        <option value="recurring">Recurrente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Estado</label>
                    <select id="filter-status" class="form-control form-control-sm">
                        <option value="">Todos</option>
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En Progreso</option>
                        <option value="completed">Completado</option>
                        <option value="failed">Fallido</option>
                        <option value="stopped">Detenido</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Fecha Desde</label>
                    <input type="date" id="filter-fecha-desde" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Fecha Hasta</label>
                    <input type="date" id="filter-fecha-hasta" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="d-flex gap-1">
                        <button id="btn-search" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                        <button id="btn-reset" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0"><i class="fas fa-list"></i> Detalle de Distribuciones</h5>
            <span id="total-registros" class="badge bg-light text-dark"></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="distribuciones-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Contenido</th>
                            <th>Tipo</th>
                            <th>Dist. Type</th>
                            <th>Estado</th>
                            <th>Creado por</th>
                            <th>Fecha Creación</th>
                            <th>Targets</th>
                            <th>Completados</th>
                            <th>Fallidos</th>
                            <th>Pendientes</th>
                            <th>Progreso</th>
                            <th>Fecha Modificación</th>
                            <th>Fecha Eliminación</th>
                            <th>Estado Elim.</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-users"></i> Resumen por Usuario</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="usuarios-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Usuario</th>
                            <th>Distribuciones Creadas</th>
                            <th>Activas</th>
                            <th>Eliminadas</th>
                            <th>Total Targets</th>
                            <th>Targets Fallidos</th>
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
<style>
.card-header { border-bottom: 2px solid #dee2e6; }
.table th { background-color: #f8f9fa; font-weight: 600; font-size: 0.75rem; white-space: nowrap; }
.table td { font-size: 0.75rem; white-space: nowrap; }
.small-box { border-radius: 0.5rem; }
.small-box .inner h3 { font-size: 2rem; }
.small-box .inner p { font-size: 0.9rem; }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
.badge { font-size: 0.7rem; }
.form-label { font-size: 0.75rem; }
.form-control-sm { font-size: 0.75rem; }
@media (max-width: 768px) {
    .table th, .table td { font-size: 0.65rem; padding: 0.25rem; }
}
</style>
@endsection

@section('js')
<script>
$(function() {
    function loadResumen() {
        const params = {};
        const userId = $('#filter-user').val();
        const fechaDesde = $('#filter-fecha-desde').val();
        const fechaHasta = $('#filter-fecha-hasta').val();

        if (userId) params.created_by = userId;
        if (fechaDesde) params.fecha_desde = fechaDesde;
        if (fechaHasta) params.fecha_hasta = fechaHasta;

        $.get('{{ route("reportes.distribuciones.resumen") }}', params, function(r) {
            $('#stat-total-created').text(r.total_created);
            $('#stat-total-active').text(r.total_active);
            $('#stat-total-deleted').text(r.total_deleted);
            $('#stat-failed').text(r.total_failed);
        }).fail(function() {
            $('#stat-total-created').text('Error');
        });
    }

    function loadPorUsuario() {
        const params = {};
        const userId = $('#filter-user').val();
        const fechaDesde = $('#filter-fecha-desde').val();
        const fechaHasta = $('#filter-fecha-hasta').val();

        if (userId) params.created_by = userId;
        if (fechaDesde) params.fecha_desde = fechaDesde;
        if (fechaHasta) params.fecha_hasta = fechaHasta;

        $.get('{{ route("reportes.distribuciones.por-usuario") }}', params, function(r) {
            const tbody = $('#usuarios-table tbody');
            tbody.empty();

            if (!r.data || r.data.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center">No hay datos</td></tr>');
                return;
            }

            r.data.forEach(function(u) {
                tbody.append(
                    '<tr>' +
                        '<td>' + escapeHtml(u.user_name) + '</td>' +
                        '<td class="text-center">' + u.total_created + '</td>' +
                        '<td class="text-center">' + u.activas + '</td>' +
                        '<td class="text-center">' + u.eliminadas + '</td>' +
                        '<td class="text-center">' + u.total_targets + '</td>' +
                        '<td class="text-center"><span class="badge ' + (u.failed_targets > 0 ? 'bg-danger' : 'bg-success') + '">' + u.failed_targets + '</span></td>' +
                    '</tr>'
                );
            });
        }).fail(function() {
            $('#usuarios-table tbody').html('<tr><td colspan="6" class="text-center text-danger">Error al cargar datos</td></tr>');
        });
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    const table = $('#distribuciones-table').DataTable({
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
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
            emptyTable: "No hay datos disponibles",
            zeroRecords: "No se encontraron resultados",
            loadingRecords: "Cargando...",
            processing: "Procesando..."
        },
        ajax: {
            url: "{{ route('reportes.distribuciones.data') }}",
            type: "GET",
            data: function(d) {
                d.created_by = $('#filter-user').val();
                d.type = $('#filter-type').val();
                d.status = $('#filter-status').val();
                d.fecha_desde = $('#filter-fecha-desde').val();
                d.fecha_hasta = $('#filter-fecha-hasta').val();
            },
            dataSrc: function(json) {
                $('#total-registros').text((json.recordsTotal || 0) + ' registros');
                return json.data;
            },
            error: function() {
                alert('Error cargando datos');
            }
        },
        columns: [
            { data: 'id', className: 'text-center' },
            { data: 'name', className: 'text-left' },
            { data: null, className: 'text-left',
                render: function(data, type, row) {
                    var content = row.files || row.command || '';
                    return content ? '<span class="text-monospace"><small>' + content + '</small></span>' : '<span class="text-muted">-</span>';
                }
            },
            { data: 'type', className: 'text-center',
                render: function(data) {
                    const labels = { immediate: 'Inmediata', scheduled: 'Programada', recurring: 'Recurrente' };
                    return labels[data] || data;
                }
            },
            { data: 'distribution_type', className: 'text-center',
                render: function(data) { return data || '-'; }
            },
            { data: 'status', className: 'text-center',
                render: function(data) {
                    const badges = {
                        pending: '<span class="badge bg-warning">Pendiente</span>',
                        in_progress: '<span class="badge bg-info">En Progreso</span>',
                        completed: '<span class="badge bg-success">Completado</span>',
                        failed: '<span class="badge bg-danger">Fallido</span>',
                        stopped: '<span class="badge bg-secondary">Detenido</span>',
                    };
                    return badges[data] || '<span class="badge bg-secondary">'+data+'</span>';
                }
            },
            { data: 'created_by', className: 'text-center' },
            { data: 'created_at', className: 'text-center' },
            { data: 'total_targets', className: 'text-center' },
            { data: 'completed_targets', className: 'text-center',
                render: function(data) { return '<span class="badge bg-success">'+data+'</span>'; }
            },
            { data: 'failed_targets', className: 'text-center',
                render: function(data) { return data > 0 ? '<span class="badge bg-danger">'+data+'</span>' : '<span class="badge bg-success">0</span>'; }
            },
            { data: 'pending_targets', className: 'text-center',
                render: function(data) { return data > 0 ? '<span class="badge bg-warning">'+data+'</span>' : '0'; }
            },
            { data: 'targets_progress', className: 'text-center',
                render: function(data) {
                    var color = 'bg-success';
                    if (data < 50) color = 'bg-danger';
                    else if (data < 80) color = 'bg-warning';
                    return '<div class="progress" style="height:16px"><div class="progress-bar '+color+'" style="width:'+data+'%">'+data+'%</div></div>';
                }
            },
            { data: 'updated_at', className: 'text-center',
                render: function(data) { return data || '-'; }
            },
            { data: 'deleted_at', className: 'text-center',
                render: function(data) { return data || '-'; }
            },
            { data: null, className: 'text-center',
                render: function(data, type, row) {
                    return row.deleted_at ? '<span class="badge bg-danger">Eliminado</span>' : '<span class="badge bg-success">Activo</span>';
                }
            },
        ]
    });

    $('#btn-search').on('click', function() {
        table.ajax.reload();
        loadResumen();
        loadPorUsuario();
    });

    $('#btn-reset').on('click', function() {
        $('#filter-user').val('');
        $('#filter-type').val('');
        $('#filter-status').val('');
        $('#filter-fecha-desde').val('');
        $('#filter-fecha-hasta').val('');
        table.ajax.reload();
        loadResumen();
        loadPorUsuario();
    });

    loadResumen();
    loadPorUsuario();
});
</script>
@endsection
