@extends('adminlte::page')

@section('title', $category->name)

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.agent-defaults.index') }}">Archivos Predeterminados</a></li>
            <li class="breadcrumb-item active">{{ $category->name }}</li>
        </ol>
    </nav>
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3"><i class="fas fa-cogs text-primary"></i> {{ $category->name }}</h1>
        <div>
            @can('agent-defaults.editar')
                <a href="{{ route('admin.agent-defaults.edit', $category) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
            @endcan
            <a href="{{ route('admin.agent-defaults.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
    <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="routes-tab" data-toggle="tab" href="#routes" role="tab">
                <i class="fas fa-route"></i> Rutas
                <span class="badge bg-primary ml-1">{{ $category->routes->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="sync-tab" data-toggle="tab" href="#sync" role="tab">
                <i class="fas fa-sync-alt"></i> Sincronización
            </a>
        </li>
    </ul>

    <div class="tab-content mt-3">
        {{-- ROUTES TAB --}}
        <div class="tab-pane active" id="routes" role="tabpanel">
            <div class="info-callout mb-3">
                <i class="fas fa-link text-purple"></i>
                <strong>Asignación por Ruta:</strong>
                Cada ruta se asigna independientemente a grupos o PCs.
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-route text-primary"></i> Rutas de Descarga</h5>
                @can('agent-defaults.editar')
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addRouteModal">
                        <i class="fas fa-plus"></i> Agregar Ruta
                    </button>
                @endcan
            </div>

            <div id="routesContainer">
                @forelse($category->routes as $route)
                    <div class="route-card" id="route-card-{{ $route->id }}">
                        <div class="route-header">
                            <strong><i class="fas fa-check-circle text-success"></i> Ruta #{{ $loop->iteration }}</strong>
                            @if($route->label)
                                <span class="badge bg-info text-dark">— "{{ $route->label }}"</span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-start mt-2">
                            <div>
                                <code class="route-pattern">{{ $route->route_pattern }}</code>
                            </div>
                            <div class="btn-group">
                                @can('agent-defaults.editar')
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="editRoute({{ $route->id }})" title="Editar ruta">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="showAssignments({{ $route->id }})" title="Asignaciones ({{ $route->assignments->count() }})">
                                        <i class="fas fa-users"></i>
                                        <span class="badge bg-success ml-1">{{ $route->assignments->count() }}</span>
                                    </button>
                                @endcan
                                @can('agent-defaults.ver')
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="showRouteFiles({{ $route->id }})" title="Archivos ({{ $route->files->count() }})">
                                        <i class="fas fa-folder-open"></i>
                                        <span class="badge bg-primary ml-1">{{ $route->files->count() }}</span>
                                    </button>
                                @endcan
                                @can('agent-defaults.eliminar')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRoute({{ $route->id }})" title="Eliminar ruta">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endcan
                            </div>
                        </div>

                        @if($route->assignments->isNotEmpty())
                            <div class="assign-section mt-2">
                                <strong style="font-size:13px;"><i class="fas fa-link text-purple"></i> Asignaciones</strong>
                                @foreach($route->assignments as $assignment)
                                    <div class="assign-item" id="assign-display-{{ $assignment->id }}">
                                        <div>
                                            @if($assignment->assignable_type === 'App\Models\Computer')
                                                <i class="fas fa-desktop text-info"></i>
                                            @else
                                                <i class="fas fa-users text-primary"></i>
                                            @endif
                                            <strong>
                                                {{ $assignment->assignable->nombre_instalacion ?? $assignment->assignable->name ?? 'N/A' }}
                                            </strong>
                                            @if($assignment->assignable && $assignment->assignable->plaza)
                                                <span class="plaza-badge ml-2">{{ $assignment->assignable->plaza }}</span>
                                            @endif
                                        </div>
                                        @can('agent-defaults.editar')
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeAssignment({{ $assignment->id }})">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        @endcan
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="alert alert-info" id="noRoutesMsg">
                        <i class="fas fa-info-circle"></i> No hay rutas registradas. Agrega una usando el botón de arriba.
                    </div>
                @endforelse
            </div>

            <div class="route-card d-none" id="routeTemplate" style="display:none;">
                <div class="route-header">
                    <strong><i class="fas fa-check-circle text-success"></i> <span class="route-label-prefix">Ruta #<span class="route-number"></span></span></strong>
                    <span class="badge bg-info text-dark route-badge-label" style="display:none;">— "<span class="route-label-text"></span>"</span>
                </div>
                <div class="d-flex justify-content-between align-items-start mt-2">
                    <div><code class="route-pattern route-pattern-text"></code></div>
                    <div class="btn-group">
                        @can('agent-defaults.editar')
                            <button type="button" class="btn btn-sm btn-outline-info btn-edit-route" title="Editar ruta"><i class="fas fa-edit"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-success btn-assign-route" title="Asignaciones (0)"><i class="fas fa-users"></i> <span class="badge bg-success ml-1">0</span></button>
                        @endcan
                        @can('agent-defaults.ver')
                            <button type="button" class="btn btn-sm btn-outline-primary btn-files-route" title="Archivos (0)"><i class="fas fa-folder-open"></i> <span class="badge bg-primary ml-1">0</span></button>
                        @endcan
                        @can('agent-defaults.eliminar')
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-route" title="Eliminar ruta"><i class="fas fa-trash"></i></button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- SYNC TAB --}}
        <div class="tab-pane" id="sync" role="tabpanel">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title"><i class="fas fa-sync-alt"></i> Estado de Sincronización</h3>
                    <div class="d-flex gap-3 align-items-center">
                        @can('agent-defaults.editar')
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" id="autoValidationToggle"
                                    {{ $category->auto_validation ? 'checked' : '' }}
                                    onchange="toggleAutoValidation(this.checked)">
                                <label class="form-check-label small" for="autoValidationToggle">
                                    <i class="fas fa-check-circle"></i> Validación Automática
                                </label>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input type="checkbox" class="form-check-input" id="autoSyncToggle"
                                    {{ $category->auto_sync ? 'checked' : '' }}
                                    onchange="toggleAutoSync(this.checked)">
                                <label class="form-check-label small" for="autoSyncToggle">
                                    <i class="fas fa-sync-alt"></i> Sincronización Automática
                                </label>
                            </div>
                        @endcan
                        <span class="text-muted small">Los agentes reportan su estado vía API</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>PC</th>
                                    <th>Ruta Servidor</th>
                                    <th>Ruta Local</th>
                                    <th>Archivo</th>
                                    <th>Estado</th>
                                    <th>Checksum Servidor</th>
                                    <th>Checksum Local</th>
                                    <th>Última sinc.</th>
                                </tr>
                            </thead>
                            <tbody id="syncTableBody">
                                @forelse($syncRows as $row)
                                    @php
                                        $badge = match ($row->sync_status) {
                                            'synced' => 'bg-success',
                                            'different' => 'bg-warning text-dark',
                                            'error' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                        $label = match ($row->sync_status) {
                                            'synced' => 'Actualizado',
                                            'different' => 'Diferente',
                                            'error' => 'Error',
                                            default => 'Pendiente',
                                        };
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $row->nombre_instalacion }}</strong></td>
                                        <td><code class="small">{{ $row->ruta_servidor }}</code></td>
                                        <td><code class="small">{{ $row->ruta_local ?: '—' }}</code></td>
                                        <td>{{ $row->file_name }}</td>
                                        <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                                        <td><code class="small">{{ Str::limit($row->server_checksum, 12) }}</code></td>
                                        <td><code class="small">{{ $row->local_checksum ? Str::limit($row->local_checksum, 12) : '—' }}</code></td>
                                        <td>{{ $row->synced_at ? $row->synced_at->format('d/m/Y H:i') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr id="noSyncRow">
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-info-circle"></i> Sin datos de sincronización.
                                            Asigna rutas con archivos para ver el estado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Route Modal --}}
    <div class="modal fade" id="addRouteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Agregar Ruta</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="addRouteForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="route_pattern" class="fw-bold">Ruta del Servidor <span class="text-danger">*</span></label>
                            <input type="text" name="route_pattern" id="route_pattern" class="form-control" required placeholder="Ej: \\srv\archivos\categoria">
                            <small class="form-text text-muted">Ruta completa en el servidor donde se encuentran los archivos.</small>
                        </div>
                        <div class="form-group">
                            <label for="route_label" class="fw-bold">Etiqueta</label>
                            <input type="text" name="label" id="route_label" class="form-control" placeholder="Ej: Ruta principal">
                        </div>
                        <div class="form-group">
                            <label for="download_path_index" class="fw-bold">Ruta de descarga</label>
                            <select name="download_path_index" id="download_path_index" class="form-control">
                                <option value="">Predeterminada</option>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}">download_path_{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Route Modal --}}
    <div class="modal fade" id="editRouteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Ruta</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="editRouteForm">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="route_id" id="edit_route_id">
                        <div class="form-group">
                            <label for="edit_route_pattern" class="fw-bold">Ruta del Servidor <span class="text-danger">*</span></label>
                            <input type="text" name="route_pattern" id="edit_route_pattern" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_route_label" class="fw-bold">Etiqueta</label>
                            <input type="text" name="label" id="edit_route_label" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="edit_download_path_index" class="fw-bold">Ruta de descarga</label>
                            <select name="download_path_index" id="edit_download_path_index" class="form-control">
                                <option value="">Predeterminada</option>
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}">download_path_{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> Actualizar</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Assignments Modal --}}
    <div class="modal fade" id="assignmentsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-users"></i> Asignaciones de Ruta</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="assignment_route_id">
                    <div class="row mb-3 g-2">
                        <div class="col-md-5">
                            <select id="assignment_type" class="form-control">
                                <option value="group">Grupo</option>
                                <option value="computer">Computadora</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select id="assignment_target" class="form-control">
                                <option value="">Seleccionar...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            @can('agent-defaults.editar')
                                <button type="button" class="btn btn-primary btn-block" onclick="addAssignment()">
                                    <i class="fas fa-plus"></i> Asignar
                                </button>
                            @endcan
                        </div>
                    </div>
                    <div id="assignmentsList">
                        <div id="assignmentsEmpty" class="text-center py-3 text-muted">
                            <i class="fas fa-link fa-2x mb-2"></i>
                            <p>Sin asignaciones. Selecciona un grupo o computadora arriba.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Route Files Modal --}}
    <div class="modal fade" id="routeFilesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-folder-open"></i> Archivos de Ruta</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="files_route_id">
                    <div class="d-flex justify-content-between mb-3">
                        @can('agent-defaults.editar')
                            <form id="uploadFileForm" enctype="multipart/form-data" class="d-flex gap-2 flex-grow-1 mr-2">
                                @csrf
                                <input type="file" name="file" class="form-control" id="fileInput" style="max-width:300px;" multiple>
                                <button type="button" class="btn btn-success" onclick="uploadFiles()">
                                    <i class="fas fa-upload"></i> Subir
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-primary" onclick="syncFiles()">
                                <i class="fas fa-sync-alt"></i> Sincronizar desde ruta
                            </button>
                        @endcan
                    </div>
                    <div id="uploadProgress" class="progress mb-3" style="display:none">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:100%">Subiendo archivos...</div>
                    </div>
                    <div id="routeFilesContainer">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2"></i>
                            <p>Seleccione una ruta para ver sus archivos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast container --}}
    <div id="toastContainer" style="position:fixed;top:70px;right:20px;z-index:9999;"></div>
@stop

@push('css')
<style>
    .route-card {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
    }
    .route-card .route-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .route-pattern {
        font-family: monospace;
        background: #e9ecef;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 13px;
        display: inline-block;
    }
    .file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 6px;
    }
    .file-item .file-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .file-item .file-info i {
        font-size: 20px;
        color: #6c757d;
    }
    .assign-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 6px;
    }
    .assign-section {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 12px;
        margin-top: 10px;
    }
    .plaza-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        background: #e8d5ff;
        color: #6f42c1;
    }
    .info-callout {
        border-left: 4px solid #0d6efd;
        background: #f0f7ff;
        padding: 12px 16px;
        border-radius: 4px;
        margin: 12px 0;
    }
    .text-purple { color: #6f42c1 !important; }
    .toast-msg {
        min-width: 300px;
        padding: 12px 20px;
        border-radius: 6px;
        color: #fff;
        font-weight: 500;
        margin-bottom: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
    }
    .toast-msg.success { background: #28a745; }
    .toast-msg.error { background: #dc3545; }
    .toast-msg.info { background: #17a2b8; }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
</style>
@endpush

@push('js')
<script>
    const categoryId = {{ $category->id }};
    const CATEGORIES = @json($category->routes);
    const CATEGORIES_ENDPOINT = '/admin/agent-defaults';
    const COMPUTERS = @json($computers);
    const GROUPS = @json($groups);

    function toast(message, type = 'success') {
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
        const container = $('#toastContainer');
        const el = $(`<div class="toast-msg ${type}"><i class="fas ${icons[type] || icons.info}"></i> ${message}</div>`);
        container.append(el);
        setTimeout(() => { el.fadeOut(300, () => el.remove()); }, 3500);
    }

    // ---- ROUTES ----
    $('#addRouteForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('[type="submit"]').prop('disabled', true);
        $.ajax({
            url: `${CATEGORIES_ENDPOINT}/${categoryId}/routes`,
            method: 'POST',
            data: $(this).serialize(),
            success: function(resp) {
                $('#addRouteModal').modal('hide');
                $('#addRouteForm')[0].reset();
                addRouteCard(resp.route);
                toast('Ruta creada exitosamente');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al guardar la ruta.', 'error');
            },
            complete: () => btn.prop('disabled', false)
        });
    });

    function addRouteCard(route) {
        $('#noRoutesMsg').remove();
        const tpl = $('#routeTemplate').clone().removeAttr('id').removeClass('d-none').show();
        tpl.attr('id', 'route-card-' + route.id);
        tpl.find('.route-number').text($('#routesContainer .route-card').length + 1);
        tpl.find('.route-pattern-text').text(route.route_pattern);
        tpl.find('.route-label-text').text(route.label || '');
        if (route.label) tpl.find('.route-badge-label').show();
        tpl.find('.btn-edit-route').attr('onclick', `editRoute(${route.id})`);
        tpl.find('.btn-assign-route').attr('onclick', `showAssignments(${route.id})`);
        tpl.find('.btn-files-route').attr('onclick', `showRouteFiles(${route.id})`);
        tpl.find('.btn-delete-route').attr('onclick', `deleteRoute(${route.id})`);
        tpl.find('.btn-assign-route span').text('0');
        tpl.find('.btn-files-route span').text('0');
        CATEGORIES.push({ ...route, assignments: [], files: [] });
        $('#routesContainer').append(tpl);
    }

    function editRoute(routeId) {
        const route = CATEGORIES.find(r => r.id === routeId);
        if (!route) return;
        $('#edit_route_id').val(route.id);
        $('#edit_route_pattern').val(route.route_pattern);
        $('#edit_route_label').val(route.label || '');
        $('#edit_download_path_index').val(route.download_path_index || '');
        $('#editRouteModal').modal('show');
    }

    $('#editRouteForm').on('submit', function(e) {
        e.preventDefault();
        const routeId = $('#edit_route_id').val();
        const btn = $(this).find('[type="submit"]').prop('disabled', true);
        $.ajax({
            url: `/admin/agent-defaults/routes/${routeId}`,
            method: 'PUT',
            data: $(this).serialize(),
            success: function(resp) {
                $('#editRouteModal').modal('hide');
                const route = CATEGORIES.find(r => r.id == routeId);
                if (route) {
                    route.route_pattern = resp.route.route_pattern;
                    route.label = resp.route.label;
                    route.download_path_index = resp.route.download_path_index;
                }
                const card = $(`#route-card-${routeId}`);
                card.find('.route-pattern-text').text(resp.route.route_pattern);
                card.find('.route-label-text').text(resp.route.label || '');
                if (resp.route.label) {
                    card.find('.route-badge-label').show();
                } else {
                    card.find('.route-badge-label').hide();
                }
                toast('Ruta actualizada exitosamente');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al actualizar la ruta.', 'error');
            },
            complete: () => btn.prop('disabled', false)
        });
    });

    function deleteRoute(routeId) {
        if (!confirm('¿Eliminar esta ruta?')) return;
        $.ajax({
            url: `/admin/agent-defaults/routes/${routeId}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                $(`#route-card-${routeId}`).fadeOut(300, function() {
                    $(this).remove();
                    const idx = CATEGORIES.findIndex(r => r.id === routeId);
                    if (idx > -1) CATEGORIES.splice(idx, 1);
                    if ($('#routesContainer .route-card').length === 0) {
                        $('#routesContainer').prepend(
                            '<div class="alert alert-info" id="noRoutesMsg"><i class="fas fa-info-circle"></i> No hay rutas registradas. Agrega una usando el botón de arriba.</div>'
                        );
                    }
                });
                toast('Ruta eliminada');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al eliminar la ruta.', 'error');
            }
        });
    }

    // ---- ASSIGNMENTS ----
    function showAssignments(routeId) {
        $('#assignment_route_id').val(routeId);
        loadAssignments(routeId);
        loadTargets();
        $('#assignmentsModal').modal('show');
    }

    function loadAssignments(routeId) {
        const route = CATEGORIES.find(r => r.id === routeId);
        if (!route) return;
        $.ajax({
            url: `/admin/agent-defaults/routes/${routeId}/assignments`,
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const container = $('#assignmentsList');
                container.empty();
                if (data.assignments && data.assignments.length > 0) {
                    data.assignments.forEach(function(a) {
                        const type = a.assignable_type === 'App\\Models\\Computer' ? 'Computadora' : 'Grupo';
                        const icon = a.assignable_type === 'App\\Models\\Computer' ? 'fa-desktop text-info' : 'fa-users text-primary';
                        const name = a.assignable ? (a.assignable.nombre_instalacion || a.assignable.name || 'N/A') : 'N/A';
                        const plaza = a.assignable && a.assignable.plaza ? `<span class="plaza-badge ml-2">${a.assignable.plaza}</span>` : '';
                        container.append(`
                            <div class="assign-item" id="assignment-row-${a.id}">
                                <div><i class="fas ${icon}"></i> <strong>${name}</strong> ${plaza}</div>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeAssignment(${a.id})"><i class="fas fa-unlink"></i></button>
                            </div>
                        `);
                    });
                } else {
                    container.append('<div class="text-center py-3 text-muted"><i class="fas fa-link fa-2x mb-2"></i><p>Sin asignaciones.</p></div>');
                }
            }
        });
    }

    $('#assignment_type').on('change', function() { loadTargets(); });

    function loadTargets() {
        const type = $('#assignment_type').val();
        const select = $('#assignment_target');
        select.empty().append('<option value="">Cargando...</option>');
        if (type === 'computer') {
            select.empty().append('<option value="">Seleccionar computadora...</option>');
            COMPUTERS.forEach(function(c) {
                select.append(`<option value="${c.id}">${c.nombre_instalacion} (${c.short_key})${c.plaza ? ' - ' + c.plaza : ''}</option>`);
            });
        } else {
            select.empty().append('<option value="">Seleccionar grupo...</option>');
            GROUPS.forEach(function(g) {
                select.append(`<option value="${g.id}">${g.name}</option>`);
            });
        }
    }

    function addAssignment() {
        const routeId = $('#assignment_route_id').val();
        const type = $('#assignment_type').val();
        const targetId = $('#assignment_target').val();
        if (!targetId) { toast('Seleccione un destino.', 'error'); return; }
        $.ajax({
            url: `/admin/agent-defaults/routes/${routeId}/assignments`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', assignable_type: type, assignable_id: targetId },
            success: function(resp) {
                loadAssignments(routeId);
                updateRouteBadges(routeId);
                toast('Asignación agregada');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al agregar asignación.', 'error');
            }
        });
    }

    function removeAssignment(assignmentId) {
        if (!confirm('¿Eliminar esta asignación?')) return;
        $.ajax({
            url: `/admin/agent-defaults/assignments/${assignmentId}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                $(`#assignment-row-${assignmentId}`).fadeOut(300, function() { $(this).remove(); });
                toast('Asignación eliminada');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al eliminar asignación.', 'error');
            }
        });
    }

    function updateRouteBadges(routeId) {
        $.get(`/admin/agent-defaults/routes/${routeId}`, function(resp) {
            const card = $(`#route-card-${routeId}`);
            card.find('.btn-assign-route span').text(resp.assignments_count || 0);
            card.find('.btn-files-route span').text(resp.files_count || 0);
        });
    }

    // ---- ROUTE FILES ----
    function showRouteFiles(routeId) {
        $('#files_route_id').val(routeId);
        $('#fileInput').val('');
        loadRouteFiles(routeId);
        $('#routeFilesModal').modal('show');
    }

    function loadRouteFiles(routeId) {
        const container = $('#routeFilesContainer');
        container.html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando archivos...</p></div>');
        $.get(`/admin/agent-defaults/routes/${routeId}/files`, function(resp) {
            container.empty();
            if (resp.files && resp.files.length > 0) {
                resp.files.forEach(function(f) {
                    const sizeKb = (f.file_size / 1024).toFixed(1);
                    container.append(`
                        <div class="file-item" id="file-row-${f.id}">
                            <div class="file-info">
                                <i class="fas fa-file-code text-info"></i>
                                <div>
                                    <strong>${f.file_name}</strong>
                                    <small class="text-muted">${sizeKb} KB &bull; SHA256: ${f.checksum.substring(0, 16)}...</small>
                                </div>
                            </div>
                            <div>
                                @can('agent-defaults.ver')
                                    <a href="/admin/agent-defaults/routes/${routeId}/files/${f.id}/download" class="btn btn-sm btn-outline-info"><i class="fas fa-download"></i></a>
                                @endcan
                                @can('agent-defaults.eliminar')
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${routeId}, ${f.id})"><i class="fas fa-trash"></i></button>
                                @endcan
                            </div>
                        </div>
                    `);
                });
            } else {
                container.append('<div class="text-center py-4 text-muted"><i class="fas fa-folder-open fa-2x mb-2"></i><p>No hay archivos para esta ruta.</p></div>');
            }
        }).fail(function() {
            container.html('<div class="text-center py-4 text-muted"><i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i><p>Error al cargar archivos.</p></div>');
        });
    }

    function uploadFiles() {
        const routeId = $('#files_route_id').val();
        if (!routeId) return;
        const fileInput = document.getElementById('fileInput');
        const files = fileInput.files;
        if (!files.length) { toast('Seleccione al menos un archivo.', 'error'); return; }

        const total = files.length;
        let uploaded = 0;
        let errors = [];

        $('#uploadProgress').show();
        $('#uploadProgress .progress-bar').text('Subiendo 0 de ' + total);

        function uploadNext(index) {
            if (index >= total) {
                $('#uploadProgress').hide();
                $('#fileInput').val('');
                loadRouteFiles(routeId);
                updateRouteBadges(routeId);
                const msg = uploaded > 0 ? uploaded + ' archivo(s) subido(s)' : '';
                const errMsg = errors.length ? '. ' + errors.length + ' error(es)' : '';
                toast(msg + errMsg || 'Operación completada', errors.length ? 'error' : 'success');
                return;
            }

            const formData = new FormData();
            formData.append('file', files[index]);
            formData.append('_token', '{{ csrf_token() }}');
            $('#uploadProgress .progress-bar').text('Subiendo ' + (index + 1) + ' de ' + total);

            $.ajax({
                url: `/admin/agent-defaults/routes/${routeId}/files`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    uploaded++;
                    uploadNext(index + 1);
                },
                error: function(xhr) {
                    errors.push(files[index].name + ': ' + (xhr.responseJSON?.message || 'Error'));
                    uploadNext(index + 1);
                }
            });
        }

        uploadNext(0);
    }

    function deleteFile(routeId, fileId) {
        if (!confirm('¿Eliminar este archivo?')) return;
        $.ajax({
            url: `/admin/agent-defaults/routes/${routeId}/files/${fileId}`,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
                $(`#file-row-${fileId}`).fadeOut(300, function() { $(this).remove(); });
                updateRouteBadges(routeId);
                toast('Archivo eliminado');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al eliminar archivo.', 'error');
            }
        });
    }

    function syncFiles() {
        const routeId = $('#files_route_id').val();
        if (!routeId) return;
        if (!confirm('¿Sincronizar archivos desde la ruta configurada?')) return;
        $.ajax({
            url: `/admin/agent-defaults/routes/${routeId}/sync-files`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(resp) {
                loadRouteFiles(routeId);
                updateRouteBadges(routeId);
                toast(resp.message || 'Archivos sincronizados');
            },
            error: function(xhr) {
                toast(xhr.responseJSON?.message || 'Error al sincronizar archivos.', 'error');
            }
        });
    }

    // ---- TOGGLES ----
    function toggleAutoValidation(enabled) {
        $.ajax({
            url: `${CATEGORIES_ENDPOINT}/${categoryId}/toggle-auto-validation`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', enabled },
            success: function(resp) {
                toast(resp.message || 'Validación automática actualizada');
            },
            error: function() {
                $('#autoValidationToggle').prop('checked', !enabled);
                toast('Error al cambiar validación automática.', 'error');
            }
        });
    }

    function toggleAutoSync(enabled) {
        $.ajax({
            url: `${CATEGORIES_ENDPOINT}/${categoryId}/toggle-auto-sync`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', enabled },
            success: function(resp) {
                toast(resp.message || 'Sincronización automática actualizada');
            },
            error: function() {
                $('#autoSyncToggle').prop('checked', !enabled);
                toast('Error al cambiar sincronización automática.', 'error');
            }
        });
    }
</script>
@endpush
