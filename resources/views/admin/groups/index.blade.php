@extends('adminlte::page')

@section('title', 'Grupos')

@section('content_header')
    <h1>Grupos</h1>
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
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createGroupModal">
                        <i class="fas fa-plus"></i> <span class="d-none d-sm-inline">Crear</span>
                    </button>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#importExcelModal">
                        <i class="fas fa-file-import"></i> <span class="d-none d-sm-inline">Importar</span>
                    </button>
                    <a href="{{ route('admin.groups.export') }}" class="btn btn-secondary">
                        <i class="fas fa-download"></i> <span class="d-none d-sm-inline">Exportar</span>
                    </a>
                </div>
                <div class="text-muted small">
                    {{ $groups->total() }} grupos en total
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm mb-0">
                    <thead class="bg-dark">
                        <tr>
                            <th class="text-nowrap">Nombre</th>
                            <th class="text-nowrap">Claves Cortas</th>
                            <th class="text-nowrap">Tipo</th>
                            <th class="text-nowrap d-none d-lg-table-cell">Description</th>
                            <th class="text-nowrap text-center">Computadoras</th>
                            <th class="text-nowrap text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td class="text-nowrap">{{ $group->name }}</td>
                                <td>
                                    @if($group->shortKeys->count() > 0)
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($group->shortKeys as $shortKey)
                                                <span class="badge badge-primary">{{ $shortKey->short_key }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small">Sin asignar</span>
                                    @endif
                                </td>
                                <td>
                                    @if($group->type)
                                        <span class="badge badge-info">{{ ucfirst($group->type) }}</span>
                                    @else
                                        <span class="text-muted small">Sin tipo</span>
                                    @endif
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <span class="text-truncate d-inline-block" style="max-width: 150px;">
                                        {{ $group->description ?: '-' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.computers.index', ['group_id' => $group->id]) }}" class="badge badge-dark">
                                        {{ $group->computers_count }}
                                    </a>
                                </td>
                                <td class="text-center text-nowrap">
                                    <button class="btn btn-warning btn-sm" onclick='editGroup({{ $group->id }}, {{ json_encode($group->name) }}, {{ json_encode($group->description) }}, {{ json_encode($group->type) }}, {{ json_encode($group->computers->pluck('id')->toArray()) }}, {{ json_encode($group->computers->pluck('plaza')->toArray()) }})' title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick='deleteGroup({{ $group->id }}, {{ json_encode($group->name) }})' title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No hay grupos registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $groups->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.groups.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Grupo</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
<div class="form-group">
                             <label>Nombre *</label>
                             <input type="text" name="name" class="form-control" required>
                         </div>
                         <div class="form-group">
                             <label>Tipo</label>
                            <select name="type" class="form-control">
                                <option value="">Seleccionar...</option>
                                <option value="tienda">Tienda</option>
                                <option value="almacen">Almacén</option>
                                <option value="cedis">CEDIS</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="especial">Especial</option>
                                <option value="cobranza">Cobranza</option>
                            </select>
                        </div>
<div class="form-group">
                             <label>Descripción</label>
                             <textarea name="description" class="form-control" rows="2"></textarea>
                         </div>
 
                         <div class="card card-info mt-3">
                             <div class="card-header">
                                 <h6 class="card-title mb-0"><i class="fas fa-desktop"></i> Agregar Computadoras al Grupo</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label>Seleccionar Plaza</label>
                                    <select id="plazaSelect" class="form-control">
                                        <option value="">-- Seleccionar Plaza --</option>
                                        @foreach($plazas as $plaza)
                                            <option value="{{ $plaza }}">{{ $plaza }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="computersContainer" class="d-none">
                                    <label>Computers en la Plaza seleccionada:</label>
                                    <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                        <div id="computersList"></div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-link" onclick="selectAllComputers()">Seleccionar todas</button>
                                        <button type="button" class="btn btn-sm btn-link" onclick="deselectAllComputers()">Deseleccionar todas</button>
                                    </div>
                                </div>
                                <input type="hidden" name="computer_ids" id="computerIds">
                            </div>
                        </div>
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
    <div class="modal fade" id="editGroupModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editGroupForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Grupo</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" id="editType" class="form-control">
                                <option value="">Seleccionar...</option>
                                <option value="tienda">Tienda</option>
                                <option value="almacen">Almacén</option>
                                <option value="cedis">CEDIS</option>
                                <option value="vendedor">Vendedor</option>
                                <option value="especial">Especial</option>
                                <option value="cobranza">Cobranza</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="card card-info mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="fas fa-desktop"></i> Asignar Computers al Grupo</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group mb-3">
                                    <label>Seleccionar Plaza</label>
                                    <select id="editPlazaSelect" class="form-control">
                                        <option value="">-- Seleccionar Plaza --</option>
                                        @foreach($plazas as $plaza)
                                            <option value="{{ $plaza }}">{{ $plaza }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div id="editComputersContainer" class="d-none">
                                    <label>Computers en la Plaza seleccionada:</label>
                                    <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                        <div id="editComputersList"></div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-link" onclick="editSelectAllComputers()">Seleccionar todas</button>
                                        <button type="button" class="btn btn-sm btn-link" onclick="editDeselectAllComputers()">Deseleccionar todas</button>
                                    </div>
                                </div>
                                <input type="hidden" name="computer_ids" id="editComputerIds">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Excel Modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.groups.import-excel') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Importar Grupos desde Excel</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-3">
                            <strong>Formato del archivo Excel:</strong>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Claves Cortas (separadas por coma)</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>IDs Tiendas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Tienda Centro</td>
                                            <td>TCE001,TCE002</td>
                                            <td>tienda</td>
                                            <td>Tienda del centro</td>
                                            <td>1,2,3</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="d-block mt-2">Los IDs de tiendas son opcionales y sirven para asignar computadoras al grupo.</small>
                        </div>
                        <div class="form-group">
                            <label>Archivo Excel *</label>
                            <input type="file" name="file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        </div>
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                         <button type="submit" class="btn btn-success">
                             <i class="fas fa-upload"></i> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
const computersData = @json($computers);

let selectedComputerIds = [];

function editGroup(id, name, description, type, computerIdsArray, computerPlazas) {
    document.getElementById('editGroupForm').action = '{{ url("admin/groups") }}/' + id;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description || '';
    document.getElementById('editType').value = type || '';

    editSelectedComputerIds = computerIdsArray || [];
    document.getElementById('editComputerIds').value = editSelectedComputerIds.join(',');

    document.getElementById('editPlazaSelect').value = '';
    document.getElementById('editComputersContainer').classList.add('d-none');

    if (computerPlazas && computerPlazas.length > 0) {
        const uniquePlazas = [...new Set(computerPlazas)];
        if (uniquePlazas.length === 1) {
            document.getElementById('editPlazaSelect').value = uniquePlazas[0];
            document.getElementById('editPlazaSelect').dispatchEvent(new Event('change'));
        } else {
            document.getElementById('editComputersContainer').classList.remove('d-none');
            const listContainer = document.getElementById('editComputersList');
            const allGroupComputers = computersData.filter(c => computerIdsArray.includes(c.id));
            listContainer.innerHTML = allGroupComputers.map(c => `
                <div class="form-check">
                    <input type="checkbox" class="form-check-input edit-computer-checkbox"
                           id="edit_computer_${c.id}" value="${c.id}" checked
                           onchange="editToggleComputer(${c.id})">
                    <label class="form-check-label" for="edit_computer_${c.id}">
                        ${c.computer_name} <span class="text-muted small">(${c.short_key || 'Sin short key'})</span>
                        <span class="badge badge-info ml-1">${c.plaza}</span>
                    </label>
                </div>
            `).join('');
        }
    }

    $('#editGroupModal').modal('show');
}

function deleteGroup(id, name) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: `Eliminar grupo "${name}" y todas sus short keys?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url("admin/groups") }}/' + id;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

document.getElementById('plazaSelect').addEventListener('change', function() {
    const plaza = this.value;
    const container = document.getElementById('computersContainer');
    const listContainer = document.getElementById('computersList');

    if (!plaza) {
        container.classList.add('d-none');
        return;
    }

    const computersInPlaza = computersData.filter(c => c.plaza === plaza);

    if (computersInPlaza.length === 0) {
        listContainer.innerHTML = '<div class="text-muted">No hay computadoras en esta plaza</div>';
        container.classList.remove('d-none');
        return;
    }

    listContainer.innerHTML = computersInPlaza.map(c => `
        <div class="form-check">
            <input type="checkbox" class="form-check-input computer-checkbox"
                   id="computer_${c.id}" value="${c.id}"
                   ${selectedComputerIds.includes(c.id) ? 'checked' : ''}
                   onchange="toggleComputer(${c.id})">
            <label class="form-check-label" for="computer_${c.id}">
                ${c.computer_name} <span class="text-muted small">(${c.short_key || 'Sin short key'})</span>
            </label>
        </div>
    `).join('');

    container.classList.remove('d-none');
});

function toggleComputer(id) {
    const index = selectedComputerIds.indexOf(id);
    if (index === -1) {
        selectedComputerIds.push(id);
    } else {
        selectedComputerIds.splice(index, 1);
    }
    document.getElementById('computerIds').value = selectedComputerIds.join(',');
}

function selectAllComputers() {
    const checkboxes = document.querySelectorAll('.computer-checkbox');
    checkboxes.forEach(cb => {
        const id = parseInt(cb.value);
        if (!selectedComputerIds.includes(id)) {
            selectedComputerIds.push(id);
        }
        cb.checked = true;
    });
    document.getElementById('computerIds').value = selectedComputerIds.join(',');
}

function deselectAllComputers() {
    const checkboxes = document.querySelectorAll('.computer-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    selectedComputerIds = [];
    document.getElementById('computerIds').value = '';
}

$('#createGroupModal').on('hidden.bs.modal', function() {
    selectedComputerIds = [];
    document.getElementById('plazaSelect').value = '';
    document.getElementById('computersContainer').classList.add('d-none');
    document.getElementById('computerIds').value = '';
});

let editSelectedComputerIds = [];

document.getElementById('editPlazaSelect').addEventListener('change', function() {
    const plaza = this.value;
    const container = document.getElementById('editComputersContainer');
    const listContainer = document.getElementById('editComputersList');

    if (!plaza) {
        container.classList.add('d-none');
        return;
    }

    const computersInPlaza = computersData.filter(c => c.plaza === plaza);

    if (computersInPlaza.length === 0) {
        listContainer.innerHTML = '<div class="text-muted">No hay computadoras en esta plaza</div>';
        container.classList.remove('d-none');
        return;
    }

    listContainer.innerHTML = computersInPlaza.map(c => `
        <div class="form-check">
            <input type="checkbox" class="form-check-input edit-computer-checkbox"
                   id="edit_computer_${c.id}" value="${c.id}"
                   ${editSelectedComputerIds.includes(c.id) ? 'checked' : ''}
                   onchange="editToggleComputer(${c.id})">
            <label class="form-check-label" for="edit_computer_${c.id}">
                ${c.computer_name} <span class="text-muted small">(${c.short_key || 'Sin short key'})</span>
            </label>
        </div>
    `).join('');

    container.classList.remove('d-none');
});

function editToggleComputer(id) {
    const index = editSelectedComputerIds.indexOf(id);
    if (index === -1) {
        editSelectedComputerIds.push(id);
    } else {
        editSelectedComputerIds.splice(index, 1);
    }
    document.getElementById('editComputerIds').value = editSelectedComputerIds.join(',');
}

function editSelectAllComputers() {
    const checkboxes = document.querySelectorAll('.edit-computer-checkbox');
    checkboxes.forEach(cb => {
        const id = parseInt(cb.value);
        if (!editSelectedComputerIds.includes(id)) {
            editSelectedComputerIds.push(id);
        }
        cb.checked = true;
    });
    document.getElementById('editComputerIds').value = editSelectedComputerIds.join(',');
}

function editDeselectAllComputers() {
    const checkboxes = document.querySelectorAll('.edit-computer-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    editSelectedComputerIds = [];
    document.getElementById('editComputerIds').value = '';
}

$('#editGroupModal').on('hidden.bs.modal', function() {
    editSelectedComputerIds = [];
    document.getElementById('editPlazaSelect').value = '';
    document.getElementById('editComputersContainer').classList.add('d-none');
    document.getElementById('editComputerIds').value = '';
});

$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop

@section('css')
<style>
@media (max-width: 576px) {
    .table-sm th, .table-sm td {
        padding: 0.4rem;
        font-size: 0.8rem;
    }
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
    .badge {
        font-size: 0.7rem;
    }
}
</style>
@stop
