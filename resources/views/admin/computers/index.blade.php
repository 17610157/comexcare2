@extends('adminlte::page')

@section('title', 'Computers')

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endsection

@push('styles')
<style>
    .computers-table-container {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        padding: 15px;
    }
    
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: capitalize;
        display: inline-block;
        min-width: 70px;
        text-align: center;
    }
    
    .status-online { 
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
        color: #155724; 
        border: 1px solid #c3e6cb;
    }
    .status-offline { 
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
        color: #721c24; 
        border: 1px solid #f5c6cb;
    }
    .status-pending { 
        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%); 
        color: #856404; 
        border: 1px solid #ffeeba;
    }
    
    .info-badge {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 500;
        margin: 1px;
        display: inline-block;
    }
    
    .table {
        font-size: 0.8rem;
    }
    
    .table thead th {
        background: linear-gradient(135deg, #343a40 0%, #495057 100%);
        color: white;
        font-size: 0.75rem;
        padding: 12px 8px;
        white-space: nowrap;
        border: none;
        text-align: center;
    }
    
    .table thead th:first-child {
        border-top-left-radius: 8px;
    }
    .table thead th:last-child {
        border-top-right-radius: 8px;
    }
    
    .table tbody td {
        padding: 10px 8px;
        vertical-align: middle;
        white-space: nowrap;
        text-align: center;
    }
    
    .table tbody tr:hover {
        background-color: rgba(0,123,255,.05);
    }
    
    .table td:first-child {
        text-align: left;
    }
    .table td:nth-child(2) {
        text-align: left;
    }
    .table td:nth-child(3) {
        text-align: left;
        font-family: monospace;
        font-size: 0.7rem;
    }
    .table td:nth-child(15) {
        text-align: left;
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .short-key-badge {
        background: #0d6efd;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .group-badge {
        background: #6c757d;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.65rem;
    }
    
    .btn-action {
        padding: 4px 8px;
        font-size: 0.75rem;
        border-radius: 4px;
    }
    
    .table-responsive {
        border-radius: 8px;
    }
    
    .dataTables_wrapper .dt-buttons {
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_length select {
        border-radius: 4px;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 20px;
        padding: 5px 15px;
    }

    .bitlocker-item {
        display: inline-block;
        margin: 2px;
    }
    
    .pvsi-files {
        font-size: 0.65rem;
        color: #6c757d;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .computers-table-container {
            padding: 10px;
        }
        
        .table {
            font-size: 0.7rem;
        }
        
        .table thead th, .table tbody td {
            padding: 6px 4px;
        }
        
        .status-badge {
            padding: 2px 6px;
            font-size: 0.6rem;
            min-width: 50px;
        }
        
        .short-key-badge {
            font-size: 0.6rem;
            padding: 2px 5px;
        }
    }
</style>
@endpush

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

<div class="computers-table-container mt-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0"><i class="fas fa-desktop mr-2"></i>Gestión de Computadoras</h5>
        <a href="{{ route('admin.computers.export') }}" class="btn btn-success btn-sm">
            <i class="fas fa-file-csv"></i> <span class="d-none d-sm-inline">Exportar Todo</span>
        </a>
    </div>
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table table-bordered table-striped table-hover table-sm mb-0" id="computers-table" style="min-width: 1200px;">
        <thead class="bg-dark">
            <tr>
                <th class="text-nowrap text-center">Short Key</th>
                <th class="text-nowrap">Nombre</th>
                <th class="text-nowrap">MAC</th>
                <th class="text-nowrap">IP</th>
                <th class="text-nowrap text-center">Status</th>
                <th class="text-nowrap">Grupo</th>
                <th class="text-nowrap">Agent</th>
                <th class="text-nowrap">PVSI</th>
                <th class="text-nowrap">PVSI Files</th>
                <th class="text-nowrap">Windows</th>
                <th class="text-nowrap text-center">Arq.</th>
                <th class="text-nowrap text-center">RAM</th>
                <th class="text-nowrap text-center">Disco</th>
                <th class="text-nowrap">Última Actividad</th>
                <th class="text-nowrap text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    </div>
</div>
@endsection

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
            processing: false,
            serverSide: true,
            ajax: {
                url: '{{ route('admin.computers.index') }}',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                error: function(xhr, error, thrown) {
                    console.log('Ajax Error:', xhr.responseText);
                    alert('Error: ' + xhr.status + ' - ' + xhr.statusText);
                }
            },
            columns: [
                { 
                    data: 'short_key',
                    name: 'short_key',
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="badge badge-primary">' + jQuery('<div>').text(data).html() + '</span>';
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
                        return '<span class="status-badge ' + cls + '">' + jQuery('<div>').text(data).html() + '</span>';
                    }
                },
                { data: 'group_name', name: 'group_name' },
                { 
                    data: 'agent_version', 
                    name: 'agent_version', 
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="info-badge bg-secondary">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'pvsi_version', 
                    name: 'pvsi_version', 
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="info-badge bg-info">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'pvsi_files', 
                    name: 'pvsi_files',
                    render: function(data) {
                        if (data && Array.isArray(data) && data.length > 0) {
                            return data.map(function(f) { return f.file_name || 'N/A'; }).join(', ');
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { data: 'windows_version', name: 'windows_version' },
                { 
                    data: 'architecture', 
                    name: 'architecture', 
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="info-badge bg-dark">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'total_ram', 
                    name: 'total_ram', 
                    render: function(data) {
                        if (data && data > 0) {
                            return Math.round(data / 1073741824) + ' GB';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'total_disk_space', 
                    name: 'total_disk_space', 
                    render: function(data) {
                        if (data && data > 0) {
                            return Math.round(data / 1073741824) + ' GB';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                }, 
                { data: 'last_seen', name: 'last_seen' },
                { 
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        var showUrl = '{{ url('admin/computers') }}/' + data;
                        var editUrl = showUrl + '/edit';
                        return '<a href="' + showUrl + '" class="btn btn-info btn-sm" title="Ver"><i class="fas fa-eye"></i></a> ' +
                               '<a href="' + editUrl + '" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>';
                    }
                }
            ],
            dom: 'lfBrtip',
            responsive: true,
            autoWidth: false,
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
    
    jQuery(document).ready(function() {
        initTable();
        
        setTimeout(function() {
            jQuery('.alert').fadeOut('slow');
        }, 5000);
    });
})();
</script>
@stop