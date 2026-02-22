<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Vendedores - Vista Matricial</title>
    <style>
        table {border-collapse: collapse; width: 100%; font-size: 11px;}
        th {background-color: #343A40; color: white; font-weight: bold; padding: 5px; border: 1px solid #ddd; text-align: center;}
        td {padding: 4px; border: 1px solid #ddd;}
        .fixed-col {background-color: #f8f9fa; font-weight: bold;}
        .nombre-row {background-color: #f8f9fa; font-style: italic;}
        .tipo-row {background-color: #e3f2fd; font-weight: 500;}
        .tienda-row {background-color: #f8f9fa;}
        .plaza-row {background-color: #e9ecef; font-weight: bold;}
        .total-row {background-color: #cfe2ff; font-weight: bold;}
        .venta-positiva {background-color: #d1e7dd;}
        .numero {text-align: right; font-family: "Courier New", monospace;}
        .titulo {font-size: 16px; font-weight: bold; text-align: center; margin: 10px 0;}
    </style>
</head>
<body>
    <div class="titulo">REPORTE DE VENDEDORES - VISTA MATRICIAL</div>
    <div>Fecha exportación: {{ date('d/m/Y H:i:s') }}</div>
    <div>Periodo: {{ $fecha_inicio }} al {{ $fecha_fin }}</div>
    
    @if($plaza) 
        <div>Plaza: {{ $plaza }}</div>
    @endif
    @if($tienda) 
        <div>Tienda: {{ $tienda }}</div>
    @endif
    @if($vendedor) 
        <div>Vendedor: {{ $vendedor }}</div>
    @endif
    
    <br>
    
    <table>
        <tr>
            <th class="fixed-col">Descripción</th>
            @foreach(array_keys($vendedores_info) as $vendedor_id)
                <th>{{ $vendedor_id }}</th>
            @endforeach
            <th>TOTAL DÍA</th>
        </tr>
        
        <!-- Fila de nombres -->
        <tr class="nombre-row">
            <td class="fixed-col">NOMBRE</td>
            @foreach($vendedores_info as $data)
                <td style="text-align: center;">{{ $data['nombre'] }}</td>
            @endforeach
            <td style="text-align: center;">-</td>
        </tr>
        
        <!-- Fila de tipo -->
        <tr class="tipo-row">
            <td class="fixed-col">TIPO</td>
            @foreach($vendedores_info as $data)
                <td style="text-align: center;">{{ $data['tipo'] }}</td>
            @endforeach
            <td style="text-align: center;">-</td>
        </tr>
        
        <!-- Fila de tiendas -->
        <tr class="tienda-row">
            <td class="fixed-col">TIENDAS</td>
            @foreach($vendedores_info as $data)
                <td style="text-align: center;">{{ implode(', ', $data['tiendas']) }}</td>
            @endforeach
            <td style="text-align: center;">-</td>
        </tr>
        
        <!-- Fila de plazas -->
        <tr class="plaza-row">
            <td class="fixed-col">PLAZA</td>
            @foreach($vendedores_info as $data)
                <td style="text-align: center;">{{ implode(', ', $data['plazas']) }}</td>
            @endforeach
            <td style="text-align: center;">-</td>
        </tr>
        
        <!-- Filas de días -->
        @foreach($dias as $dia_key => $dia_formatted)
        <tr>
            <td class="fixed-col">{{ $dia_formatted }}</td>
            @php
                $total_dia = 0;
            @endphp
            @foreach($vendedores_info as $vendedor_id => $data)
                @php
                    $venta = $data['ventas'][$dia_key] ?? 0;
                    $total_dia += $venta;
                @endphp
                @if($venta > 0)
                    <td class="numero venta-positiva">${{ number_format($venta, 2) }}</td>
                @else
                    <td class="numero">$-</td>
                @endif
            @endforeach
            <td class="numero total-row">${{ number_format($total_dia, 2) }}</td>
        </tr>
        @endforeach
        
        <!-- Fila de totales por vendedor -->
        <tr class="total-row">
            <td class="fixed-col">TOTAL VENDEDOR</td>
            @php
                $total_general = 0;
            @endphp
            @foreach($vendedores_info as $vendedor_id => $data)
                @php
                    $total_vendedor = array_sum($data['ventas']);
                    $total_general += $total_vendedor;
                @endphp
                <td class="numero">${{ number_format($total_vendedor, 2) }}</td>
            @endforeach
            <td class="numero">${{ number_format($total_general, 2) }}</td>
        </tr>
    </table>
    
    <br>
    <div><strong>Resumen:</strong> {{ count($vendedores_info) }} vendedores, {{ count($dias) }} días</div>
</body>
</html>