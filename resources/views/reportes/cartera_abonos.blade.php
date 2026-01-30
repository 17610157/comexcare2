@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title mb-0">Cartera - Abonos (Mes Anterior)</h3>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-4">
          <label for="plaza">Plaza</label>
          <select id="plaza" class="form-control"></select>
        </div>
        <div class="col-md-4">
          <label for="tienda">Tienda</label>
          <select id="tienda" class="form-control"></select>
        </div>
        <div class="col-md-4 align-self-end">
          <button id="btn_refresh" class="btn btn-primary">Actualizar</button>
        </div>
      </div>

      <table id="report-table" class="table table-bordered table-hover">
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
</div>
@endsection

@section('scripts')
<script>
  // Inicialización de DataTables
  $(function() {
    var table = $('#report-table').DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: "{{ url('/reportes/cartera-abonos/data') }}",
        data: function (d) {
          d.plaza = $('#plaza').val();
          d.tienda = $('#tienda').val();
        },
        dataSrc: function(json) { return json.data || json; }
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

    $('#btn_refresh').on('click', function() {
      table.ajax.reload();
    });
  });
</script>
@endsection
