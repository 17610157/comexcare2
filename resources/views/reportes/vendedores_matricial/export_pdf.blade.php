<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Vendedores - Vista Matricial</title>
    <style>
        @page {
            margin: 15px;
            size: landscape;
        }
        
        @page :first {
            margin-top: 30px;
        }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9px; 
            margin: 0;
            padding: 0;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .plaza-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            page-break-after: avoid;
        }
        
        .header h1 { 
            margin: 0; 
            font-size: 16px; 
            color: #333;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        .plaza-title {
            background-color: #f8f9fa;
            padding: 8px;
            border-left: 4px solid #007bff;
            margin-bottom: 10px;
            page-break-after: avoid;
        }
        
        .plaza-title h3 {
            margin: 0;
            font-size: 13px;
            color: #333;
        }
        
        .plaza-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 8px;
            page-break-after: avoid;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px;
            font-size: 7px;
            page-break-inside: avoid;
        }
        
        th { 
            background-color: #343a40; 
            color: white; 
            padding: 4px 3px;
            border: 1px solid #ddd;
            text-align: center;
            font-weight: bold;
        }
        
        td { 
            border: 1px solid #ddd; 
            padding: 3px 2px;
            text-align: left;
        }
        
        .text-right { 
            text-align: right; 
        }
        
        .text-center { 
            text-align: center; 
        }
        
        .fixed-col { 
            background-color: #f8f9fa; 
            font-weight: bold;
        }
        
        .nombre-row { 
            background-color: #f8f9fa; 
            font-style: italic;
        }
        
        .tipo-row { 
            background-color: #e3f2fd; 
            font-weight: 500;
        }
        
        .tienda-row { 
            background-color: #f8f9fa; 
        }
        
        .plaza-row { 
            background-color: #e9ecef; 
            font-weight: bold;
        }
        
        .total-row { 
            background-color: #cfe2ff; 
            font-weight: bold;
        }
        
        .venta-positiva { 
            background-color: #d1e7dd; 
        }
        
        .numero { 
            text-align: right; 
            font-family: "Courier New", monospace;
            font-weight: bold;
        }
        
        .footer { 
            margin-top: 15px; 
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
            text-align: center;
            page-break-before: avoid;
        }
        
        .page-number {
            position: fixed;
            bottom: 10px;
            right: 15px;
            font-size: 8px;
            color: #666;
        }
        
        .resumen-plaza {
            background-color: #f0f8ff;
            padding: 5px;
            border-radius: 3px;
            margin-bottom: 10px;
            font-size: 8px;
            page-break-after: avoid;
        }
    </style>
</head>
<body>
    <!-- Cabecera principal -->
    <div class="header">
        <h1>REPORTE DE VENDEDORES - VISTA MATRICIAL</h1>
        <h2>Periodo: {{ $fecha_inicio }} al {{ $fecha_fin }}</h2>
        <div style="font-size: 9px; margin-top: 5px;">
            <strong>Fecha de generación:</strong> {{ date('d/m/Y H:i:s') }}
            @if($plaza)
                | <strong>Filtro Plaza:</strong> {{ $plaza }}
            @endif
            @if($tienda)
                | <strong>Filtro Tienda:</strong> {{ $tienda }}
            @endif
            @if($vendedor)
                | <strong>Filtro Vendedor:</strong> {{ $vendedor }}
            @endif
        </div>
    </div>
    
    <!-- Iterar por cada plaza -->
    @foreach($plazas as $plaza_nombre => $plaza)
        <div class="plaza-section">
            <!-- Título de la plaza -->
            <div class="plaza-title">
                <h3>PLAZA: {{ $plaza_nombre }}</h3>
            </div>
            
            <!-- Resumen de la plaza -->
            <div class="resumen-plaza">
                <strong>Resumen:</strong> 
                {{ count($plaza['vendedores']) }} vendedores | 
                {{ count($plaza['dias']) }} días | 
                <strong>Total Plaza:</strong> ${{ number_format($plaza['total_plaza'], 2) }}
            </div>
            
            <!-- Tabla de la plaza -->
            <table>
                <thead>
                    <tr>
                        <th class="fixed-col" width="80">Descripción</th>
                        @foreach(array_keys($plaza['vendedores']) as $vendedor_id)
                            <th width="50">{{ $vendedor_id }}</th>
                        @endforeach
                        <th width="60">TOTAL DÍA</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila de nombres -->
                    <tr class="nombre-row">
                        <td class="fixed-col">NOMBRE</td>
                        @foreach($plaza['vendedores'] as $data)
                            <td class="text-center">{{ $data['nombre'] }}</td>
                        @endforeach
                        <td class="text-center">-</td>
                    </tr>
                    
                    <!-- Fila de tipo -->
                    <tr class="tipo-row">
                        <td class="fixed-col">TIPO</td>
                        @foreach($plaza['vendedores'] as $data)
                            <td class="text-center">{{ $data['tipo'] }}</td>
                        @endforeach
                        <td class="text-center">-</td>
                    </tr>
                    
                    <!-- Fila de tiendas -->
                    <tr class="tienda-row">
                        <td class="fixed-col">TIENDAS</td>
                        @foreach($plaza['vendedores'] as $data)
                            <td class="text-center">{{ implode(', ', $data['tiendas']) }}</td>
                        @endforeach
                        <td class="text-center">-</td>
                    </tr>
                    
                    <!-- Fila de plazas -->
                    <tr class="plaza-row">
                        <td class="fixed-col">PLAZA</td>
                        @foreach($plaza['vendedores'] as $data)
                            <td class="text-center">{{ implode(', ', $data['plazas']) }}</td>
                        @endforeach
                        <td class="text-center">-</td>
                    </tr>
                    
                    <!-- Filas de días -->
                    @foreach($plaza['dias'] as $dia_key => $dia_formatted)
                    <tr>
                        <td class="fixed-col"><strong>{{ $dia_formatted }}</strong></td>
                        @php
                            $total_dia = 0;
                        @endphp
                        @foreach($plaza['vendedores'] as $vendedor_id => $data)
                            @php
                                $venta = $data['ventas'][$dia_key] ?? 0;
                                $total_dia += $venta;
                            @endphp
                            <td class="numero {{ $venta > 0 ? 'venta-positiva' : '' }}">
                                @if($venta > 0)
                                    ${{ number_format($venta, 2) }}
                                @else
                                    $-
                                @endif
                            </td>
                        @endforeach
                        <td class="numero total-row"><strong>${{ number_format($total_dia, 2) }}</strong></td>
                    </tr>
                    @endforeach
                    
                    <!-- Fila de totales por vendedor -->
                    <tr class="total-row">
                        <td class="fixed-col"><strong>TOTAL VENDEDOR</strong></td>
                        @php
                            $total_general = 0;
                        @endphp
                        @foreach($plaza['vendedores'] as $vendedor_id => $data)
                            @php
                                $total_vendedor = array_sum($data['ventas']);
                                $total_general += $total_vendedor;
                            @endphp
                            <td class="numero"><strong>${{ number_format($total_vendedor, 2) }}</strong></td>
                        @endforeach
                        <td class="numero"><strong>${{ number_format($total_general, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Pie de página de la plaza -->
            <div class="footer">
                Plaza: {{ $plaza_nombre }} | 
                Vendedores: {{ count($plaza['vendedores']) }} | 
                Total Plaza: ${{ number_format($plaza['total_plaza'], 2) }}
            </div>
        </div>
        
        <!-- Salto de página después de cada plaza (excepto la última) -->
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
    
    <!-- Resumen general -->
    <div style="page-break-before: always;"></div>
    <div class="header">
        <h2>RESUMEN GENERAL</h2>
    </div>
    
    <table style="width: 60%; margin: 20px auto; font-size: 10px;">
        <thead>
            <tr>
                <th>Plaza</th>
                <th>Vendedores</th>
                <th>Total Venta</th>
                <th>% del Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_general_plazas = 0;
            @endphp
            @foreach($plazas as $plaza_nombre => $plaza)
                @php
                    $total_general_plazas += $plaza['total_plaza'];
                @endphp
            @endforeach
            
            @foreach($plazas as $plaza_nombre => $plaza)
            <tr>
                <td>{{ $plaza_nombre }}</td>
                <td class="text-center">{{ count($plaza['vendedores']) }}</td>
                <td class="numero">${{ number_format($plaza['total_plaza'], 2) }}</td>
                <td class="numero">
                    @if($total_general_plazas > 0)
                        {{ number_format(($plaza['total_plaza'] / $total_general_plazas) * 100, 2) }}%
                    @else
                        0%
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #cfe2ff; font-weight: bold;">
                <td>TOTAL GENERAL</td>
                <td class="text-center">
                    @php
                        $total_vendedores = 0;
                        foreach($plazas as $plaza) {
                            $total_vendedores += count($plaza['vendedores']);
                        }
                    @endphp
                    {{ $total_vendedores }}
                </td>
                <td class="numero">${{ number_format($total_general_plazas, 2) }}</td>
                <td class="numero">100%</td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer" style="margin-top: 30px;">
        <p>Documento generado automáticamente por el sistema</p>
        <p>Total de plazas reportadas: {{ count($plazas) }}</p>
    </div>
</body>
</html>