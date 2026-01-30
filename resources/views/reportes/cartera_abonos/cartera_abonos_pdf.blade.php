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
    <p>Periodo: {{ $start ?? '' }} a {{ $end ?? '' }}</p>
    <table>
      <thead>
        <tr>
          <th>Plaza</th><th>Tienda</th><th>Fecha</th><th>Fecha_vta</th><th>Concepto</th><th>Tipo</th><th>Factura</th><th>Clave</th><th>RFC</th><th>Nombre</th><th>Monto_fa</th><th>Monto_dv</th><th>Monto_cd</th><th>Dias_Cred</th><th>Dias_Vencidos</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($data as $row)
        <tr>
          <td>{{ $row->plaza ?? '' }}</td>
          <td>{{ $row->tienda ?? '' }}</td>
          <td>{{ $row->fecha ?? '' }}</td>
          <td>{{ $row->fecha_vta ?? '' }}</td>
          <td>{{ $row->concepto ?? '' }}</td>
          <td>{{ $row->tipo ?? '' }}</td>
          <td>{{ $row->factura ?? '' }}</td>
          <td>{{ $row->clave ?? '' }}</td>
          <td>{{ $row->rfc ?? '' }}</td>
          <td>{{ $row->nombre ?? '' }}</td>
          <td>{{ $row->monto_fa ?? 0 }}</td>
          <td>{{ $row->monto_dv ?? 0 }}</td>
          <td>{{ $row->monto_cd ?? 0 }}</td>
          <td>{{ $row->dias_cred ?? 0 }}</td>
          <td>{{ $row->dias_vencidos ?? 0 }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </body>
  </html>
