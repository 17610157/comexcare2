@extends('adminlte::page')

@section('title', 'Computers')

@section('content_header')
    <h1>Computers</h1>
@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .table-responsive {
        overflow-x: auto;
    }
    .table-responsive table {
        min-width: 900px;
    }
    .status-online { color: #28a745; font-weight: bold; }
    .status-offline { color: #dc3545; }
    .status-pending { color: #ffc107; }
    .filters-card {
        background: #f8f9fa;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
    }
</style>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row filters-card">
            <div class="col-md-3">
                <label>Short Key</label>
                <input type="text" id="filterShortKey" class="form-control" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <label>Grupo</label>
                <select id="filterGroup" class="form-select">
                    <option value="">Todos</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">Todos</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" id="btnClearFilters" class="btn btn-secondary btn-sm w-100">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-bordered table-hover table-sm" id="computers-table" style="min-width: 1500px;">
                <thead class="table-dark">
                    <tr>
                        <th>Short Key</th>
                        <th>Name</th>
                        <th>MAC</th>
                        <th>IP</th>
                        <th>Status</th>
                        <th>Group</th>
                        <th>Agent Version</th>
                        <th>PVSI Version</th>
                        <th>PVSI Files</th>
                        <th>Windows</th>
                        <th>Arquitectura</th>
                        <th>RAM</th>
                        <th>Disco</th>
                        <th>BitLocker</th>
                        <th>Download Path</th>
                        <th>Last Seen</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
.status-online {
    color: #28a745;
    font-weight: bold;
}
.status-offline {
    color: #dc3545;
    font-weight: bold;
}
.status-pending {
    color: #ffc107;
    font-weight: bold;
}
</style>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
(function() {
    'use strict';
    
    var table;
    
    function initTable() {
        table = jQuery('#computers-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.computers.index') }}',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: function(d) {
                    d.short_key = jQuery('#filterShortKey').val();
                    d.group_id = jQuery('#filterGroup').val();
                    d.status = jQuery('#filterStatus').val();
                },
                error: function(xhr, error, thrown) {
                    console.error('AJAX Error:', thrown);
                }
            },
            columns: [
                { 
                    data: 'short_key',
                    name: 'short_key',
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="badge bg-info">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { data: 'computer_name', name: 'computer_name' },
                { data: 'mac_address', name: 'mac_address' },
                { data: 'ip_address', name: 'ip_address' },
                { 
                    data: 'status',
                    name: 'status',
                    render: function(data) {
                        var cls = 'status-offline';
                        if (data === 'online') cls = 'status-online';
                        else if (data === 'pending') cls = 'status-pending';
                        return '<span class="' + cls + '">' + jQuery('<div>').text(data).html() + '</span>';
                    }
                },
                { data: 'group_name', name: 'group_name' },
                { data: 'agent_version', name: 'agent_version', render: function(data) {
                    if (data && data !== '-') {
                        return '<span class="badge bg-secondary">' + jQuery('<div>').text(data).html() + '</span>';
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'pvsi_version', name: 'pvsi_version', render: function(data) {
                    if (data && data !== '-') {
                        return '<span class="badge bg-primary">' + jQuery('<div>').text(data).html() + '</span>';
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'pvsi_files', name: 'pvsi_files', render: function(data) {
                    if (data && Array.isArray(data) && data.length > 0) {
                        var html = '<div>';
                        data.forEach(function(file) {
                            html += '<span class="badge bg-info" style="margin:2px;">' + (file.file_name || 'N/A') + '</span>';
                        });
                        html += '</div>';
                        return html;
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'windows_version', name: 'windows_version', render: function(data) {
                    if (data && data !== '-') {
                        return jQuery('<div>').text(data).html();
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'architecture', name: 'architecture', render: function(data) {
                    if (data && data !== '-') {
                        return '<span class="badge bg-dark">' + jQuery('<div>').text(data).html() + '</span>';
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'total_ram', name: 'total_ram', render: function(data) {
                    if (data && data > 0) {
                        return Math.round(data / 1073741824) + ' GB';
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'total_disk_space', name: 'total_disk_space', render: function(data) {
                    if (data && data > 0) {
                        return Math.round(data / 1073741824) + ' GB';
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'bitlocker_status', name: 'bitlocker_status', render: function(data) {
                    if (data && typeof data === 'object' && Object.keys(data).length > 0) {
                        var html = '';
                        for (var drive in data) {
                            var status = data[drive];
                            var cls = status === 'Enabled' ? 'bg-success' : 'bg-danger';
                            html += '<span class="badge ' + cls + ' me-1" style="font-size:0.7rem;">' + drive + ' ' + status + '</span>';
                        }
                        return html;
                    }
                    return '<span class="text-muted">-</span>';
                }},
                { data: 'download_path', name: 'download_path' },
                { data: 'last_seen', name: 'last_seen' },
                { 
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        var showUrl = '{{ url('admin/computers') }}/' + data;
                        var editUrl = showUrl + '/edit';
                        return '<a href="' + showUrl + '" class="btn btn-info btn-sm">View</a> ' +
                               '<a href="' + editUrl + '" class="btn btn-warning btn-sm">Edit</a>';
                    }
                }
            ],
            dom: 'lBfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], ['10', '25', '50', '100']],
            language: {
                processing: "Procesando...",
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_",
                infoEmpty: "Sin datos",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron",
                emptyTable: "No hay datos",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Sig",
                    previous: "Ant"
                }
            }
        });
    }
    
    function bindFilters() {
        function triggerSearch() {
            table.draw();
        }
        
        jQuery('#filterShortKey').on('keyup', function() {
            triggerSearch();
        });
        
        jQuery('#filterGroup').on('change', function() {
            triggerSearch();
        });
        
        jQuery('#filterStatus').on('change', function() {
            triggerSearch();
        });
        
        jQuery('#btnClearFilters').on('click', function() {
            jQuery('#filterShortKey').val('');
            jQuery('#filterGroup').val('');
            jQuery('#filterStatus').val('');
            triggerSearch();
        });
    }
    
    jQuery(document).ready(function() {
        initTable();
        bindFilters();
    });
})();
</script>
@stop
