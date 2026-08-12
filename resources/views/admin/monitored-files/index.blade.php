@extends('adminlte::page')

@section('title', 'Archivos Monitoreados')

@section('content_header')
    <h1>Archivos Monitoreados</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="btn-group flex-wrap">
                    @can('monitored-files.crear')
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createRecordModal">
                            <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Crear registro</span>
                        </button>
                    @endcan
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#seedDefaultsModal">
                        <i class="fas fa-sync"></i> <span class="d-none d-sm-inline">Lista predeterminada</span>
                    </button>
                </div>
                <div class="text-muted small">
                    {{ $records->total() }} registros en total
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm mb-0">
                    <thead class="bg-dark">
                        <tr>
                            <th class="text-nowrap">Nivel</th>
                            <th class="text-nowrap">Destino</th>
                            <th class="text-nowrap">Ruta (path)</th>
                            <th class="text-nowrap">Archivos</th>
                            <th class="text-nowrap text-center">Recursivo</th>
                            <th class="text-nowrap text-center">Orden</th>
                            <th class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>
                                    @if($record->general)
                                        <span class="badge badge-dark">General</span>
                                    @elseif($record->group_id)
                                        <span class="badge badge-info">Grupo</span>
                                    @else
                                        <span class="badge badge-primary">Equipo</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($record->general)
                                        Todas las máquinas
                                    @elseif($record->group_id)
                                        {{ $record->group?->name ?? 'Grupo #'.$record->group_id }}
                                    @else
                                        {{ $record->computer?->computer_name ?? 'Equipo #'.$record->computer_id }}
                                        @if($record->computer?->short_key)
                                            <span class="badge badge-dark ml-1">{{ $record->computer->short_key }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-nowrap"><code>{{ $record->path }}</code></td>
                                <td>
                                    @php $fileNames = $record->file_names ?? []; @endphp
                                    @if(empty($fileNames))
                                        <span class="text-muted small">Todos los archivos</span>
                                    @else
                                        @foreach($fileNames as $name)
                                            <span class="badge badge-dark mr-1">{{ $name }}</span>
                                        @endforeach
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($record->recursive)
                                        <span class="badge badge-success">Sí</span>
                                    @else
                                        <span class="badge badge-secondary">No</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $record->sort_order }}</td>
                                <td class="text-center text-nowrap">
                                    @can('monitored-files.editar')
                                        <button class="btn btn-warning btn-sm"
                                            onclick='editRecord({{ $record->id }}, "{{ $record->general ? 'general' : ($record->group_id ? 'group' : 'computer') }}", {{ $record->group_id ?? 'null' }}, {{ $record->computer_id ?? 'null' }}, {{ json_encode($record->path) }}, {{ json_encode($record->file_names ?? []) }}, {{ $record->recursive ? 'true' : 'false' }}, {{ $record->sort_order }})'
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endcan
                                    @can('monitored-files.eliminar')
                                        <button class="btn btn-danger btn-sm" onclick='deleteRecord({{ $record->id }}, {{ json_encode($record->path) }})' title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay archivos monitoreados registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $records->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createRecordModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.monitored-files.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Archivo Monitoreado</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.monitored-files._form', ['formScope' => 'group', 'form' => null, 'formId' => 'create'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editRecordForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Archivo Monitoreado</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.monitored-files._form', ['formScope' => 'group', 'form' => null, 'formId' => 'edit'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Seed Defaults Modal -->
    <div class="modal fade" id="seedDefaultsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.monitored-files.seed-defaults') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Asignar lista predeterminada</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Reemplaza los registros del grupo seleccionado con la lista completa que hoy
                            reporta el agente (ConciliacionApp.exe, pvsi_bepartners.exe, CareAgentResurtido.exe,
                            *.DBF/*.EXE/*.CDX, MODEM/ATM y quickbck/*.DBF).
                        </div>
                        <div class="form-group">
                            <label>Grupo *</label>
                            <select name="group_id" class="form-control" required>
                                <option value="">Seleccionar grupo...</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-sync"></i> Asignar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
const groupsData = @json($groups);
const computersData = @json($computers);

function syncScope(container) {
    const scope = container.querySelector('[name="scope"]').value;
    const groupField = container.querySelector('[name="group_id"]');
    const computerField = container.querySelector('[name="computer_id"]');
    const hint = container.querySelector('.general-hint');
    groupField.closest('.form-group').classList.toggle('d-none', scope !== 'group');
    computerField.closest('.form-group').classList.toggle('d-none', scope !== 'computer');
    groupField.required = scope === 'group';
    computerField.required = scope === 'computer';
    if (hint) hint.classList.toggle('d-none', scope !== 'general');
}

document.querySelectorAll('.monitored-form [name="scope"]').forEach(function(select) {
    select.addEventListener('change', function() {
        syncScope(this.closest('.monitored-form'));
    });
    syncScope(select.closest('.monitored-form'));
});

function addFileNameRow(container, value) {
    const div = document.createElement('div');
    div.className = 'input-group mb-1 file-name-row';
    div.innerHTML = '<input type="text" name="file_names[]" class="form-control" '
        + 'placeholder="ConciliacionApp.exe, *.DBF o vacío para listar todos" '
        + 'value="' + (value || '') + '">'
        + '<div class="input-group-append">'
        + '<button type="button" class="btn btn-outline-danger remove-file-name" tabindex="-1"><i class="fas fa-times"></i></button>'
        + '</div>';
    container.appendChild(div);
}

function fillFileNames(container, names) {
    container.innerHTML = '';
    const list = (names && names.length) ? names : [null];
    list.forEach(function(name) {
        addFileNameRow(container, name);
    });
}

document.querySelectorAll('.monitored-form').forEach(function(form) {
    const list = form.querySelector('.file-names-list');
    const addBtn = form.querySelector('.add-file-name');
    if (!list || !addBtn) return;

    addBtn.addEventListener('click', function() {
        addFileNameRow(list, '');
        list.lastElementChild.querySelector('input').focus();
    });

    list.querySelectorAll('.file-name-row').forEach(function(row) {
        row.querySelector('.remove-file-name').addEventListener('click', function() {
            const rows = list.querySelectorAll('.file-name-row');
            if (rows.length > 1) {
                row.remove();
            } else {
                row.querySelector('input').value = '';
            }
        });
    });
});

function editRecord(id, scope, groupId, computerId, path, fileNames, recursive, sortOrder) {
    const form = document.getElementById('editRecordForm');
    form.action = '{{ url("admin/monitored-files") }}/' + id;

    form.querySelector('[name="scope"]').value = scope;
    form.querySelector('[name="group_id"]').value = groupId || '';
    form.querySelector('[name="computer_id"]').value = computerId || '';
    form.querySelector('[name="path"]').value = path || '';
    fillFileNames(form.querySelector('.file-names-list'), fileNames);
    form.querySelector('[name="recursive"]').checked = !!recursive;
    form.querySelector('[name="sort_order"]').value = sortOrder;

    syncScope(form.querySelector('.monitored-form'));

    $('#editRecordModal').modal('show');
}

function deleteRecord(id, path) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Eliminar el registro monitoreado "' + path + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/monitored-files") }}/' + id,
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    Swal.fire({
                        title: 'Eliminado',
                        text: 'Registro eliminado exitosamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    let message = 'Error al eliminar el registro';
                    if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.status === 419) {
                        message = 'La sesión ha expirado. Recarga la página.';
                    } else if (xhr.status === 403) {
                        message = 'No tienes permiso para eliminar registros.';
                    }
                    Swal.fire({ title: 'Error', text: message, icon: 'error' });
                }
            });
        }
    });
}

$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop

@section('css')
<meta name="csrf-token" content="{{ csrf_token() }}">
@stop
