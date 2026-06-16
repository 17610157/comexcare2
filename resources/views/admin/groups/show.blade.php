@extends('adminlte::page')

@section('title', 'Grupo: ' . $group->name)

@section('content_header')
    <h1>{{ $group->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <button class="btn btn-warning" onclick="editGroup()">
                    <i class="fas fa-edit"></i> Editar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Nombre</th>
                            <td>{{ $group->name }}</td>
                        </tr>
                        <tr>
                            <th>Tipo</th>
                            <td>
                                @if($group->type)
                                    <span class="badge badge-info">{{ ucfirst($group->type) }}</span>
                                @else
                                    <span class="text-muted">Sin tipo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Descripción</th>
                            <td>{{ $group->description ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Computadoras</th>
                            <td>
                                <a href="{{ route('admin.computers.index', ['group_id' => $group->id]) }}">
                                    {{ $group->computers_count }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Fecha de creación</th>
                            <td>{{ $group->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title">Claves Cortas</h3>
                        </div>
                        <div class="card-body">
                            @if($group->shortKeys->count() > 0)
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($group->shortKeys as $shortKey)
                                        <span class="badge badge-primary badge-lg p-2">{{ $shortKey->short_key }}</span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted mb-0">No hay claves cortas asignadas</p>
                            @endif
                        </div>
                    </div>
                </div>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Claves Cortas</label>
                            <input type="text" name="short_keys" id="editShortKeys" class="form-control" placeholder="TCE001, TCE002, TCE003">
                            <small class="form-text text-muted">Separa múltiples short keys con coma</small>
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
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
                    </div>
                    <div class="modal-footer">
<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                         <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
function editGroup() {
    document.getElementById('editGroupForm').action = '{{ url("admin/groups") }}/{{ $group->id }}';
    document.getElementById('editName').value = '{{ $group->name }}';
    document.getElementById('editDescription').value = '{{ $group->description ?? '' }}';
    document.getElementById('editType').value = '{{ $group->type ?? '' }}';
    document.getElementById('editShortKeys').value = '{{ $group->shortKeys->pluck('short_key')->implode(', ') }}';
    $('#editGroupModal').modal('show');
}
</script>
@stop
