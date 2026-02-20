@extends('adminlte::page')

@section('title', 'Tiendas')

@section('content_header')
    <h1>Gestión de Tiendas</h1>
@stop

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="card">
        <div class="card-header">
            <div class="row">
                @can('tiendas.crear')
                <div class="col-md-6">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#tiendaModal" onclick="resetTiendaForm()">
                        <i class="fas fa-plus"></i> Nueva Tienda
                    </button>
                </div>
                @endcan
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" placeholder="Buscar tiendas...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" id="searchBtn">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped" id="tiendas-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Clave Tienda</th>
                        <th>Nombre</th>
                        <th>Plaza</th>
                        <th>Zona</th>
                        <th>Clave Alterna</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para crear/editar tienda -->
    <div class="modal fade" id="tiendaModal" tabindex="-1" role="dialog" aria-labelledby="tiendaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="tiendaForm">
                    @csrf
                    <input type="hidden" name="id" id="tienda_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tiendaModalLabel">Nueva Tienda</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="tienda_clave">Clave Tienda</label>
                            <input type="text" name="clave_tienda" id="tienda_clave" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="tienda_nombre">Nombre</label>
                            <input type="text" name="nombre" id="tienda_nombre" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="tienda_plaza">Plaza</label>
                            <input type="text" name="id_plaza" id="tienda_plaza" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="tienda_zona">Zona</label>
                            <input type="text" name="zona" id="tienda_zona" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="tienda_clave_alterna">Clave Alterna</label>
                            <input type="text" name="clave_alterna" id="tienda_clave_alterna" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="deleteTiendaModal" tabindex="-1" role="dialog" aria-labelledby="deleteTiendaModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTiendaModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de eliminar la tienda <strong id="delete_tienda_nombre"></strong>?</p>
                    <p class="text-danger">Esta acción puede afectar los reportes.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="confirmDeleteTienda">Eliminar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    initDataTable();
    
    $('#tiendaForm').on('submit', function(e) {
        e.preventDefault();
        saveTienda();
    });

    $('#searchBtn').on('click', function() {
        $('#tiendas-table').DataTable().draw();
    });

    $('#searchInput').on('keypress', function(e) {
        if(e.which == 13) {
            $('#tiendas-table').DataTable().draw();
        }
    });

    $('#deleteTiendaModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var nombre = button.data('nombre');
        $('#delete_tienda_nombre').text(nombre);
        $('#confirmDeleteTienda').data('id', id);
    });

    $('#confirmDeleteTienda').on('click', function() {
        deleteTienda($(this).data('id'));
    });
});

function initDataTable() {
    $('#tiendas-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.tiendas.data") }}',
            data: function(d) {
                d.search.value = $('#searchInput').val();
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'clave_tienda', name: 'clave_tienda' },
            { data: 'nombre', name: 'nombre' },
            { data: 'id_plaza', name: 'id_plaza' },
            { data: 'zona', name: 'zona' },
            { data: 'clave_alterna', name: 'clave_alterna' },
            {
                data: null,
                render: function(data) {
                    let buttons = '';
                    @can('tiendas.editar')
                    buttons += `<button class="btn btn-sm btn-warning" onclick="editTienda(${data.id}, '${data.clave_tienda}', '${data.nombre || ''}', '${data.id_plaza || ''}', '${data.zona || ''}', '${data.clave_alterna || ''}')">
                        <i class="fas fa-edit"></i>
                    </button> `;
                    @endcan
                    @can('tiendas.eliminar')
                    buttons += `<button class="btn btn-sm btn-danger" data-id="${data.id}" data-nombre="${data.nombre || data.clave_tienda}" data-toggle="modal" data-target="#deleteTiendaModal">
                        <i class="fas fa-trash"></i>
                    </button>`;
                    @endcan
                    return buttons;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando página _PAGE_ de _PAGES_",
            infoEmpty: "No hay registros disponibles",
            infoFiltered: "(filtrado de _MAX_ registros totales)"
        },
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10
    });
}

function editTienda(id, clave, nombre, plaza, zona, claveAlterna) {
    $('#tienda_id').val(id);
    $('#tienda_clave').val(clave);
    $('#tienda_nombre').val(nombre);
    $('#tienda_plaza').val(plaza);
    $('#tienda_zona').val(zona);
    $('#tienda_clave_alterna').val(claveAlterna);
    $('#tiendaModalLabel').text('Editar Tienda');
    $('#tiendaModal').modal('show');
}

function saveTienda() {
    let id = $('#tienda_id').val();
    let url = id ? '/admin/tiendas/' + id : '{{ route("admin.tiendas.store") }}';
    let method = id ? 'PUT' : 'POST';

    $.ajax({
        url: url,
        method: method,
        data: $('#tiendaForm').serialize(),
        success: function(response) {
            $('#tiendaModal').modal('hide');
            $('#tiendas-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Tienda guardada correctamente',
                type: 'success'
            });
            resetTiendaForm();
        },
        error: function(xhr) {
            let error = xhr.responseJSON?.message || 'Error al guardar la tienda';
            Swal.fire({
                title: 'Error',
                text: error,
                type: 'error'
            });
        }
    });
}

function deleteTienda(id) {
    $.ajax({
        url: '/admin/tiendas/' + id,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#deleteTiendaModal').modal('hide');
            $('#tiendas-table').DataTable().ajax.reload();
            Swal.fire({
                title: 'Éxito',
                text: 'Tienda eliminada correctamente',
                type: 'success'
            });
        },
        error: function(xhr) {
            let error = xhr.responseJSON?.message || 'Error al eliminar la tienda';
            Swal.fire({
                title: 'Error',
                text: error,
                type: 'error'
            });
        }
    });
}

function resetTiendaForm() {
    $('#tienda_id').val('');
    $('#tienda_clave').val('');
    $('#tienda_nombre').val('');
    $('#tienda_plaza').val('');
    $('#tienda_zona').val('');
    $('#tienda_clave_alterna').val('');
    $('#tiendaModalLabel').text('Nueva Tienda');
}

$('#tiendaModal').on('hidden.bs.modal', function() {
    resetTiendaForm();
});

function resetTiendaForm() {
    $('#tienda_id').val('');
    $('#tienda_clave').val('');
    $('#tienda_nombre').val('');
    $('#tienda_plaza').val('');
    $('#tienda_zona').val('');
    $('#tienda_clave_alterna').val('');
    $('#tiendaModalLabel').text('Nueva Tienda');
}
</script>
@stop
