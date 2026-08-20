@extends('adminlte::page')

@section('title', 'Estado Configuración RBF')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-server mr-2"></i> Estado Configuración RBF</h1>
        <small class="text-muted">
            Última sincronización: {{ $syncedAt ? $syncedAt->diffForHumans() : 'Sin datos' }}
        </small>
    </div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-database"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Registros</span>
                <span class="info-box-number">{{ number_format($total) }}</span>
            </div>
        </div>
    </div>
    @foreach($columnCounts as $col => $count)
    <div class="col-lg-2 col-md-4 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-tag"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">{{ $columnLabels[$col] ?? strtoupper($col) }}</span>
                <span class="info-box-number">{{ $count }}</span>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h3 class="card-title"><i class="fas fa-list mr-1"></i> Datos</h3>
            <div class="d-flex align-items-center">
                <div class="input-group input-group-sm mr-2" style="width: 250px;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Buscar...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="btnSearch">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="btn-group btn-group-sm mr-2">
                    <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 70px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <button class="btn btn-sm btn-outline-secondary" id="btnRefresh" title="Refrescar">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0" style="max-height: 55vh; overflow-y: auto;">
        <table class="table table-hover table-bordered table-striped mb-0">
            <thead class="thead-dark" style="position: sticky; top: 0; z-index: 2;">
                <tr>
                    <th>PLAZA</th>
                    <th>RAZON</th>
                    <th>TIPO</th>
                    <th>CLAVE</th>
                    <th>LISTA</th>
                    <th>OFERTA</th>
                    <th>PROMO</th>
                    <th>COMBO</th>
                    <th>EXE</th>
                    <th>DBF</th>
                    <th>PVSI</th>
                    <th>USUARIO</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr><td colspan="12" class="text-center text-muted">Cargando datos...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span id="tableInfo" class="text-muted">Mostrando 0 de 0 registros</span>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
var currentPage = 0;
var pageSize = 10;
var totalRecords = 0;
var searchTimer = null;

function loadData() {
    var params = new URLSearchParams();
    params.set('draw', 1);
    params.set('start', currentPage * pageSize);
    params.set('length', pageSize);
    var search = $('#searchInput').val().trim();
    if (search) params.set('search', search);

    $.ajax({
        url: '{{ route("reportes.rbf-config-status.data") }}?' + params.toString(),
        type: 'GET',
        beforeSend: function() {
            $('#tableBody').html('<tr><td colspan="12" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');
        },
        success: function(json) {
            totalRecords = json.recordsTotal;
            var data = json.data;
            var tbody = $('#tableBody');
            tbody.empty();

            if (data.length === 0) {
                tbody.html('<tr><td colspan="12" class="text-center text-muted">Sin resultados</td></tr>');
            } else {
                data.forEach(function(row) {
                    tbody.append(
                        '<tr>' +
                        '<td>' + esc(row.pl) + '</td>' +
                        '<td>' + esc(row.rs) + '</td>' +
                        '<td>' + esc(row.ti) + '</td>' +
                        '<td>' + esc(row.ca) + '</td>' +
                        '<td>' + esc(row.li || '') + '</td>' +
                        '<td>' + esc(row.of || '') + '</td>' +
                        '<td>' + esc(row.pr || '') + '</td>' +
                        '<td>' + esc(row.co || '') + '</td>' +
                        '<td>' + esc(row.ex || '') + '</td>' +
                        '<td>' + esc(row.db || '') + '</td>' +
                        '<td>' + esc(row.pv || '') + '</td>' +
                        '<td>' + esc(row.us || '') + '</td>' +
                        '</tr>'
                    );
                });
            }

            var start = currentPage * pageSize + 1;
            var end = Math.min((currentPage + 1) * pageSize, totalRecords);
            $('#tableInfo').text('Mostrando ' + (data.length > 0 ? start : 0) + '-' + end + ' de ' + totalRecords + ' registros');
            renderPagination();
        },
        error: function() {
            $('#tableBody').html('<tr><td colspan="12" class="text-center text-danger">Error al cargar datos</td></tr>');
        }
    });
}

function esc(text) {
    if (text === null || text === undefined) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(String(text)));
    return div.innerHTML;
}

function renderPagination() {
    var totalPages = Math.ceil(totalRecords / pageSize);
    if (totalPages <= 1) { $('#pagination').empty(); return; }

    var $ul = $('#pagination');
    $ul.empty();

    if (currentPage > 0) {
        $ul.append('<li class="page-item"><a class="page-link page-btn" href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>');
    }

    var startPage = Math.max(0, currentPage - 2);
    var endPage = Math.min(totalPages, startPage + 5);
    if (endPage - startPage < 5) startPage = Math.max(0, endPage - 5);

    for (var i = startPage; i < endPage; i++) {
        var active = i === currentPage ? ' active' : '';
        $ul.append('<li class="page-item' + active + '"><a class="page-link page-btn" href="#" data-page="' + i + '">' + (i + 1) + '</a></li>');
    }

    if (currentPage < totalPages - 1) {
        $ul.append('<li class="page-item"><a class="page-link page-btn" href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>');
    }
}

$(function() {
    loadData();

    $(document).on('click', '.page-btn', function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data('page'));
        loadData();
    });

    $('#btnSearch').on('click', function() { currentPage = 0; loadData(); });
    $('#btnRefresh').on('click', function() { currentPage = 0; loadData(); });

    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { currentPage = 0; loadData(); }, 400);
    });

    $('#pageSizeSelect').on('change', function() {
        pageSize = parseInt($(this).val(), 10) || 10;
        currentPage = 0;
        loadData();
    });
});
</script>
@stop
