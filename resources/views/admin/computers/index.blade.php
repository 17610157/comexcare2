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
    
    .info-badge {
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 500;
        margin: 1px;
        display: inline-block;
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

<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-desktop mr-2"></i>Gestión de Computadoras</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.computers.export') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-file-csv"></i> Exportar Todo
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="overflow-x: auto;">
                    <table class="table table-bordered table-striped table-hover table-sm mb-0" id="computers-table" style="min-width: 900px;">
                        <thead class="bg-dark">
                            <tr>
                                <th class="text-nowrap text-center">Short Key</th>
                                <th class="text-nowrap">Nombre</th>
                                <th class="text-nowrap text-center">Status</th>
                                <th class="text-nowrap">Grupo</th>
                                <th class="text-nowrap">Agent</th>
                                <th class="text-nowrap">PVSI</th>
                                <th class="text-nowrap">Windows</th>
                                <th class="text-nowrap text-center">Plaza</th>
                                <th class="text-nowrap">Última Actividad</th>
                                <th class="text-nowrap text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
    
    function getWindowsIcon(version) {
        if (!version) return '<i class="fab fa-windows text-secondary" title="Windows"></i>';
        var v = version.toLowerCase();
        if (v.includes('11') || v.includes('22h2') || v.includes('23h2')) {
            return '<i class="fab fa-windows text-primary" title="' + version + '"></i>';
        }
        if (v.includes('10')) {
            return '<i class="fab fa-windows text-info" title="' + version + '"></i>';
        }
        if (v.includes('server')) {
            return '<i class="fas fa-server text-secondary" title="' + version + '"></i>';
        }
        return '<i class="fab fa-windows text-secondary" title="' + version + '"></i>';
    }
    
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
                    className: 'text-center',
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="badge badge-primary">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { data: 'computer_name', name: 'computer_name', className: 'text-center' },
                { 
                    data: 'status',
                    name: 'status',
                    className: 'text-center',
                    render: function(data) {
                        if (data === 'online') {
                            return '<span class="text-success" title="Online"><i class="fas fa-circle"></i></span>';
                        }
                        return '<span class="text-danger" title="Offline"><i class="fas fa-circle"></i></span>';
                    }
                },
                { data: 'group_name', name: 'group_name', className: 'text-center' },
                { 
                    data: 'agent_version', 
                    name: 'agent_version', 
                    className: 'text-center',
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
                    className: 'text-center',
                    render: function(data) {
                        if (data && data !== '-') {
                            return '<span class="info-badge bg-info">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { 
                    data: 'windows_version', 
                    name: 'windows_version', 
                    className: 'text-center',
                    render: function(data) {
                        return getWindowsIcon(data);
                    }
                },
                { 
                    data: 'plaza', 
                    name: 'plaza', 
                    className: 'text-center',
                    render: function(data) {
                        if (data) {
                            return '<span class="badge badge-success">' + jQuery('<div>').text(data).html() + '</span>';
                        }
                        return '<span class="text-muted">-</span>';
                    }
                },
                { data: 'last_seen', name: 'last_seen', className: 'text-center' },
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
