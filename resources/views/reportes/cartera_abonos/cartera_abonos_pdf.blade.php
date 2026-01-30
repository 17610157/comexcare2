<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Cartera Abonos - PDF</title>
    <style>
      table { width: 100%; border-collapse: collapse; }
      th, td { border: 1px solid #333; padding: 4px; font-family: sans-serif; font-size: 12px; }
      th { background: #f0f0f0; }
    </style>
  </head>
  <body>
    <h2>Cartera Abonos - Mes Anterior</h2>
    <table>
      <thead>
        <tr>
          <th>Plaza</th><th>Tienda</th><th>Fecha</th><th>Fecha_vta</th><th>Concepto</th><th>Tipo</th><th>Factura</th><th>Clave</th><th>RFC</th><th>Nombre</th><th>Monto_fa</th><th>Monto_dv</th><th>Monto_cd</th><th>Dias_Cred</th><th>Dias_Vencidos</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($data as $row)
        <tr>
          <td>{{ $row->Plaza ?? $row['Plaza'] }}</td>
          <td>{{ $row->Tienda ?? $row['Tienda'] }}</td>
          <td>{{ $row->Fecha ?? $row['Fecha'] }}</td>
          <td>{{ $row->Fecha_vta ?? $row['Fecha_vta'] }}</td>
          <td>{{ $row->Concepto ?? $row['Concepto'] }}</td>
          <td>{{ $row->Tipo ?? $row['Tipo'] }}</td>
          <td>{{ $row->Factura ?? $row['Factura'] }}</td>
          <td>{{ $row->Clave ?? $row['Clave'] }}</td>
          <td>{{ $row->RFC ?? $row['RFC'] }}</td>
          <td>{{ $row->Nombre ?? $row['Nombre'] }}</td>
          <td>{{ $row->monto_fa ?? $row['monto_fa'] }}</td>
          <td>{{ $row->monto_dv ?? $row['monto_dv'] }}</td>
          <td>{{ $row->monto_cd ?? $row['monto_cd'] }}</td>
          <td>{{ $row->Dias_Cred ?? $row['Dias_Cred'] }}</td>
          <td>{{ $row->Dias_Vencidos ?? $row['Dias_Vencidos'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </body>
  </html>
