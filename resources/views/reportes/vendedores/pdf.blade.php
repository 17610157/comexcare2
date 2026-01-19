<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Vendedores</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 11px; 
            margin: 10px;
        }
        .header { 
            text-align: center; 
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 { 
            margin: 0; 
            font-size: 16px; 
            color: #333;
        }
        .info { 
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .info p { 
            margin: 3px 0; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            font-size: 10px;
        }
        th { 
            background-color: #343a40; 
            color: white; 
            padding: 6px 4px;
            border: 1px solid #ddd;
            text-align: center;
        }
        td { 
            border: 1px solid #ddd; 
            padding: 5px 4px;
            text-align: left;
        }
        .text-right { 
            text-align: right; 
        }
        .text-center { 
            text-align: center; 
        }
        .footer { 
            margin-top: 20px; 
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .total-row { 
            font-weight: bold; 
            background-color: #f8f9fa;
        }
        .bg-success { background-color: #d4edda !important; }
        .bg-danger { background-color: #f8d7da !important; }
        .bg-primary { background-color: #cce5ff !important; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE VENDEDORES</h1>
        <p style="font-size: 12px; margin-top: 5px;">Fecha de generación: {{ $fecha_reporte }}</p>
    </div>
    
    <div class="info">
        <p><strong>Periodo:</strong> {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        @if($plaza)
            <p><strong>Plaza:</strong> {{ $plaza }}</p>
        @endif
        @if($tienda)
            <p><strong>Tienda:</strong> {{ $tienda }}</p>
        @endif
        @if($vendedor)
            <p><strong>Vendedor:</strong> {{ $vendedor }}</p>
        @endif
        <p><strong>Total de registros:</strong> {{ $total_registros }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="30">#</th>
                <th>Tienda-Vendedor</th>
                <th>Vendedor-Día</th>
                <th>Plaza Ajustada</th>
                <th>Tienda</th>
                <th>Vendedor</th>
                <th>Fecha</th>
                <th class="text-right">Venta Total</th>
                <th class="text-right">Devolución</th>
                <th class="text-right">Venta Neta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $item)
            <tr>
                <td class="text-center">{{ $item['no'] }}</td>
                <td>{{ $item['tienda_vendedor'] }}</td>
                <td>{{ $item['vendedor_dia'] }}</td>
                <td>{{ $item['plaza_ajustada'] }}</td>
                <td>{{ $item['ctienda'] }}</td>
                <td>{{ $item['vend_clave'] }}</td>
                <td>{{ $item['fecha'] }}</td>
                <td class="text-right">{{ number_format($item['venta_total'], 2) }}</td>
                <td class="text-right">{{ number_format($item['devolucion'], 2) }}</td>
                <td class="text-right">{{ number_format($item['venta_neta'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" class="text-right"><strong>TOTALES:</strong></td>
                <td class="text-right"><strong>{{ number_format($total_ventas, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_devoluciones, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($total_neto, 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>Documento generado automáticamente por el sistema</p>
        <p>Página 1 de 1</p>
    </div>
</body>
</html>