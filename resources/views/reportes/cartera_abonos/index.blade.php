@extends('adminlte::page')
@section('title', 'Cartera Abonos')

@section('content_header')
<h1>Cartera - Abonos (Mes Anterior)</h1>
@stop

@section('content')
<div class="card">
  <div class="card-header">
    <div class="row">
      <div class="col-md-4">
        <label class="form-label">Plaza</label>
        <select id="plaza" class="form-control form-control-sm"></select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tienda</label>
        <select id="tienda" class="form-control form-control-sm"></select>
      </div>
      <div class="col-md-4 align-self-end d-flex align-items-end">
        <button id="btn_refresh" class="btn btn-primary btn-sm ml-auto">Actualizar</button>
      </div>
    </div>
  </div>
  <div class="card-body p-0">
    <table id="report-table" class="table table-bordered table-hover mb-0" style="width:100%">
      <thead>
        <tr>
          <th>Plaza</th>
          <th>Tienda</th>
          <th>Fecha</th>
          <th>Fecha_vta</th>
          <th>Concepto</th>
          <th>Tipo</th>
          <th>Factura</th>
          <th>Clave</th>
          <th>RFC</th>
          <th>Nombre</th>
          <th>Monto FA</th>
          <th>Monto DV</th>
          <th>Monto CD</th>
          <th>Días Crédito</th>
          <th>Días Vencidos</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
@endsection

@section('js')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
  $('#report-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ url('/reportes/cartera-abonos/data') }}",
      data: function (d) {
        d.plaza = $('#plaza').val();
        d.tienda = $('#tienda').val();
      }
    },
    columns: [
      { data: 'Plaza' },
      { data: 'Tienda' },
      { data: 'Fecha' },
      { data: 'Fecha_vta' },
      { data: 'Concepto' },
      { data: 'Tipo' },
      { data: 'Factura' },
      { data: 'Clave' },
      { data: 'RFC' },
      { data: 'Nombre' },
      { data: 'monto_fa' },
      { data: 'monto_dv' },
      { data: 'monto_cd' },
      { data: 'Dias_Cred' },
      { data: 'Dias_Vencidos' }
    ]
  });
  $('#btn_refresh').on('click', function(){ $('#report-table').DataTable().ajax.reload(); });
});
</script>
@endsection
