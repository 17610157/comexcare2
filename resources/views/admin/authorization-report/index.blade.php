@extends('adminlte::page')

@section('title', 'Reporte de Autorizaciones')

@section('content_header')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <h1>Reporte de Autorizaciones</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
                </div>
                <div class="card-body">
                    <form id="filterForm" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="module_id" class="mr-2">Módulo:</label>
                            <select name="module_id" id="module_id" class="form-control">
                                <option value="">Todos</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module->id }}">{{ $module->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label for="date_from" class="mr-2">Desde:</label>
                            <input type="date" name="date_from" id="date_from" class="form-control">
                        </div>
                        <div class="form-group mr-3">
                            <label for="date_to" class="mr-2">Hasta:</label>
                            <input type="date" name="date_to" id="date_to" class="form-control">
                        </div>
                        <button type="button" class="btn btn-primary mr-2" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button type="button" class="btn btn-secondary mr-2" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                        <button type="button" class="btn btn-success" onclick="exportReport()">
                            <i class="fas fa-file-csv"></i> Exportar CSV
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Historial de Autorizaciones</h3>
                </div>
                <div class="card-body">
                    <table id="reportTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Archivo</th>
                                <th>Módulo</th>
                                <th>Creado por</th>
                                <th>Correo Autorizador</th>
                                <th>Autorizado por</th>
                                <th>Fecha Autorización</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
let table;

$(document).ready(function() {
    loadData();
});

function loadData() {
    const moduleId = $('#module_id').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();

    let url = '{{ route("reportes.authorization-report.data") }}?';
    if (moduleId) url += 'module_id=' + moduleId + '&';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            if (table) {
                table.clear().destroy();
            }

            table = $('#reportTable').DataTable({
                data: response.data,
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'file_list_info', name: 'file_list_info', orderable: false, searchable: false },
                    { data: 'module_name', name: 'module_name' },
                    { data: 'creator_name', name: 'creator_name' },
                    { data: 'email', name: 'email' },
                    { data: 'authorizer_name', name: 'authorizer_name' },
                    { data: 'authorized_date', name: 'authorized_date' },
                ],
                order: [[6, 'desc']],
                language: {
                    decimal: "",
                    emptyTable: "No hay autorizaciones registradas",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                    infoEmpty: "Mostrando 0 a 0 de 0 entradas",
                    infoFiltered: "(filtrado de _MAX_ entradas totales)",
                    lengthMenu: "Mostrando _MENU_ entradas",
                    loadingRecords: "Cargando...",
                    processing: "Procesando...",
                    search: "Buscar:",
                    zeroRecords: "No se encontraron registros coincidentes",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                }
            });
        }
    });
}

function applyFilters() {
    loadData();
}

function clearFilters() {
    $('#module_id').val('');
    $('#date_from').val('');
    $('#date_to').val('');
    loadData();
}

function exportReport() {
    const moduleId = $('#module_id').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();

    let url = '{{ route("reportes.authorization-report.export") }}?';
    if (moduleId) url += 'module_id=' + moduleId + '&';
    if (dateFrom) url += 'date_from=' + dateFrom + '&';
    if (dateTo) url += 'date_to=' + dateTo + '&';

    window.location.href = url;
}
</script>
@stop
