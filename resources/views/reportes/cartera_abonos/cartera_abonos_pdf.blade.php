<!doctype html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Cartera Abonos - PDF</title>
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
      h1 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
      .period-info { background-color: #ecf0f1; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      th { background-color: #3498db; color: white; padding: 8px; text-align: center; font-weight: bold; }
      td { border: 1px solid #bdc3c7; padding: 6px; text-align: center; }
      .text-left { text-align: left; }
      .text-right { text-align: right; }
      .currency { text-align: right; }
      .total-row { font-weight: bold; background-color: #f8f9fa; }
      @media print {
        body { margin: 10px; }
        table { font-size: 10px; }
      }
    </style>
  </head>
  <body>
    <h1>Reporte de Cartera - Abonos</h1>
    <div class="period-info">
      <strong>Periodo:</strong> {{ $start ?? '' }} a {{ $end ?? '' }}
      <br>
      <strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i:s') }}
    </div>
    
    @if(count($data) > 0)
      <table>
        <thead>
          <tr>
            <th>Plaza</th><th>Tienda</th><th>Fecha</th><th>Fecha Vta</th><th>Concepto</th><th>Tipo</th><th>Factura</th><th>Clave</th><th>RFC</th><th>Nombre</th><th>Vendedor</th><th>Monto FA</th><th>Monto DV</th><th>Monto CD</th><th>Días Crédito</th><th>Días Vencidos</th>
          </tr>
        </thead>
        <tbody>
          @php
            $total_monto_fa = 0;
            $total_monto_dv = 0;
            $total_monto_cd = 0;
          @endphp
          
          @foreach ($data as $row)
            @php
              $total_monto_fa += $row->monto_fa ?? 0;
              $total_monto_dv += $row->monto_dv ?? 0;
              $total_monto_cd += $row->monto_cd ?? 0;
            @endphp
            <tr>
              <td>{{ $row->plaza ?? '' }}</td>
              <td>{{ $row->tienda ?? '' }}</td>
              <td>{{ $row->fecha ?? '' }}</td>
              <td>{{ $row->fecha_vta ?? '' }}</td>
              <td>{{ $row->concepto ?? '' }}</td>
              <td>{{ $row->tipo ?? '' }}</td>
              <td>{{ $row->factura ?? '' }}</td>
              <td>{{ $row->clave ?? '' }}</td>
              <td class="text-left">{{ $row->rfc ?? '' }}</td>
              <td class="text-left">{{ $row->nombre ?? '' }}</td>
              <td>{{ $row->vend_clave ?? '' }}</td>
              <td class="currency">$ {{ number_format($row->monto_fa ?? 0, 2) }}</td>
              <td class="currency">$ {{ number_format($row->monto_dv ?? 0, 2) }}</td>
              <td class="currency">$ {{ number_format($row->monto_cd ?? 0, 2) }}</td>
              <td>{{ $row->dias_cred ?? 0 }}</td>
              <td>{{ $row->dias_vencidos ?? 0 }}</td>
            </tr>
          @endforeach
          
          <tr class="total-row">
            <td colspan="11"><strong>TOTALES</strong></td>
            <td class="currency"><strong>$ {{ number_format($total_monto_fa, 2) }}</strong></td>
            <td class="currency"><strong>$ {{ number_format($total_monto_dv, 2) }}</strong></td>
            <td class="currency"><strong>$ {{ number_format($total_monto_cd, 2) }}</strong></td>
            <td colspan="2"></td>
          </tr>
        </tbody>
      </table>
    @else
      <p><strong>No se encontraron datos para el periodo seleccionado.</strong></p>
    @endif
  </body>
</html>
