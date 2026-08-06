@extends('adminlte::page')
@section('title', 'API Demo')

@section('content_header')
<h1>API Demo - Datos recibidos</h1>
@stop

@section('content')
<div class="container-fluid">
  <div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap">
      <h5 class="mb-0">
        <i class="fas fa-database"></i> Datos recibidos por API
      </h5>
      <div>
        <span id="total_registros" class="badge bg-light text-dark"></span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table id="api-demo-table" class="table table-bordered table-hover table-striped mb-0" style="width:100%">
          <thead class="thead-light">
            <tr>
              <th>ID</th>
              <th>Parámetro 1</th>
              <th>Parámetro 2</th>
              <th>Fecha de registro</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<style>
.table th { background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem; white-space: nowrap; }
.table td { font-size: 0.85rem; }
</style>
@endsection

@section('js')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(function() {
  const dataTable = $('#api-demo-table').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    language: {
      search: "Buscar:",
      lengthMenu: "Mostrar _MENU_ por página",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" },
      emptyTable: "No hay datos disponibles",
      zeroRecords: "No se encontraron resultados",
      loadingRecords: "Cargando...",
      processing: "Procesando..."
    },
    ajax: {
      url: "{{ url('/reportes/api-demo/data') }}",
      type: "GET",
      dataSrc: function(json) {
        $('#total_registros').text(json.recordsTotal + ' registros');
        return json.data;
      },
      error: function(xhr) { console.log('Error:', xhr.responseText); }
    },
    columns: [
      { data: 'id', className: 'text-center' },
      { data: 'param1' },
      { data: 'param2' },
      { data: 'created_at', className: 'text-center' },
    ]
  });
});
</script>
@endsection
