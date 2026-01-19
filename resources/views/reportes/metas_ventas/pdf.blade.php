<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Metas vs Ventas</title>
    <style>
        @page { margin: 15px; }
        
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            margin: 0;
            padding: 0;
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
            font-size: 9px;
        }
        
        .info p { 
            margin: 3px 0; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            font-size: 8px;
        }
        
        th { 
            background-color: #343a40; 
            color: white; 
            padding: 5px 3px;
            border: 1px solid #ddd;
            text-align: center;
            font-weight: bold;
        }
        
        td { 
            border: 1px solid #ddd; 
            padding: 4px 3px;
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
            font-size: 8px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            text-align: center;
        }
        
        .total-row { 
            font-weight: bold; 
            background-color: #f8f9fa;
        }
        
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        
        .summary-box {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px 10px 0;
            background-color: #e9ecef;
            border-radius: 3px;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <!-- Cabecera -->
    <div class="header">
        <h1>REPORTE DE METAS VS VENTAS</h1>
        <div style="font-size: 11px; margin-top: 5px;">
            <strong>Fecha de generación:</strong> {{ $fecha_reporte }}
        </div>
    </div>
    
    <!-- Información del reporte -->
    <div class="info">
        <p><strong>Periodo:</strong> {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        @if($plaza)
            <p><strong>Plaza:</strong> {{ $plaza }}</p>
        @endif
        @if($tienda)
            <p><strong>Tienda:</strong> {{ $tienda }}</p>
        @endif
        @if($zona)
            <p><strong>Zona:</strong> {{ $zona }}</p>
        @endif
        
        <!-- Resumen rápido -->
        <div style="margin-top: 8px;">
            <span class="summary-box">
                <strong>Registros:</strong> {{ count($resultados) }}
            </span>
            <span class="summary-box">
                <strong>Meta Total:</strong> ${{ number_format($total_meta, 2) }}
            </span>
            <span class="summary-box">
                <strong>Total Vendido:</strong> ${{ number_format($total_vendido, 2) }}
            </span>
            <span class="summary-box {{ $porcentaje_promedio >= 100 ? 'text-success' : 'text-danger' }}">
                <strong>% Cumplimiento:</strong> {{ number_format($porcentaje_promedio, 2) }}%
            </span>
        </div>
    </div>
    
    <!-- Tabla de resultados -->
    <table>
        <thead>
            <tr>
                <th>Plaza</th>
                <th>Tienda</th>
                <th>Zona</th>
                <th>Sucursal</th>
                <th>Fecha</th>
                <th class="text-right">Meta Total</th>
                <th class="text-right">Días Total</th>
                <th class="text-right">Valor Día</th>
                <th class="text-right">Meta Día</th>
                <th class="text-right">Total Vendido</th>
                <th class="text-right">% Cumplimiento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultados as $item)
            <tr>
                <td>{{ $item->plaza }}</td>
                <td>{{ $item->tienda }}</td>
                <td>{{ $item->zona }}</td>
                <td>{{ $item->sucursal }}</td>
                <td>{{ $item->fecha }}</td>
                <td class="text-right">${{ number_format($item->meta_total ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($item->dias_total ?? 0, 0) }}</td>
                <td class="text-right">${{ number_format($item->valor_dia ?? 0, 2) }}</td>
                <td class="text-right">${{ number_format($item->meta_dia ?? 0, 2) }}</td>
                <td class="text-right">${{ number_format($item->total_vendido ?? 0, 2) }}</td>
                <td class="text-right {{ ($item->porcentaje_cumplimiento ?? 0) >= 100 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($item->porcentaje_cumplimiento ?? 0, 2) }}%
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right"><strong>TOTALES:</strong></td>
                <td class="text-right"><strong>${{ number_format(collect($resultados)->sum('meta_total') ?? 0, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format(collect($resultados)->sum('dias_total') ?? 0, 0) }}</strong></td>
                <td class="text-right"><strong>${{ number_format(collect($resultados)->avg('valor_dia') ?? 0, 2) }}</strong></td>
                <td class="text-right"><strong>${{ number_format($total_meta, 2) }}</strong></td>
                <td class="text-right"><strong>${{ number_format($total_vendido, 2) }}</strong></td>
                <td class="text-right {{ $porcentaje_promedio >= 100 ? 'text-success' : 'text-danger' }}">
                    <strong>{{ number_format($porcentaje_promedio, 2) }}%</strong>
                </td>
            </tr>
        </tfoot>
    </table>
    
    <!-- Pie de página -->
    <div class="footer">
        <p>Documento generado automáticamente por el sistema</p>
        <p>Página 1 de 1 | Total de registros: {{ count($resultados) }}</p>
    </div>
</body>
</html>