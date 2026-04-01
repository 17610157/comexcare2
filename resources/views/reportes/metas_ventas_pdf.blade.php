<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte Metas de Ventas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: left; }
        th { background-color: #343A40; color: white; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #F8F9FA; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Reporte de Metas de Ventas</h2>
    <p>Periodo: {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
    
    <table>
        <thead>
            <tr>
                <th>CLAVE</th>
                <th>NOMBRE</th>
                <th class="text-right">META</th>
                <th class="text-center">DIAS MES</th>
                <th class="text-center">DIAS AGOTADOS</th>
                <th class="text-right">META PARCIAL</th>
                <th class="text-right">VENTA REAL</th>
                <th class="text-right">PORCENTAJE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultados as $item)
            <tr>
                <td>{{ $item->clave_tienda }}</td>
                <td>{{ $item->sucursal }}</td>
                <td class="text-right">${{ number_format($item->meta_total, 2) }}</td>
                <td class="text-center">{{ number_format($item->dias_mes, 1) }}</td>
                <td class="text-center">{{ number_format($item->dias_agotados, 2) }}</td>
                <td class="text-right">${{ number_format($item->meta_parcial, 2) }}</td>
                <td class="text-right">${{ number_format($item->venta_real, 2) }}</td>
                <td class="text-right">{{ number_format($item->porcentaje, 2) }}%</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">TOTALES:</td>
                <td class="text-right">${{ number_format($estadisticas['total_meta_total'], 2) }}</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-right">${{ number_format($estadisticas['total_meta_parcial'], 2) }}</td>
                <td class="text-right">${{ number_format($estadisticas['total_venta_real'], 2) }}</td>
                <td class="text-right">{{ number_format($estadisticas['porcentaje_promedio'], 2) }}%</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
