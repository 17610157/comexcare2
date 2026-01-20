<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Metas de Ventas</title>
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
        .bg-info { background-color: #d1ecf1 !important; }
        .bg-warning { background-color: #fff3cd !important; }
        .bg-success { background-color: #d4edda !important; }
        .bg-secondary { background-color: #e2e3e5 !important; }
        .text-success { color: #28a745 !important; }
        .text-warning { color: #ffc107 !important; }
        .text-danger { color: #dc3545 !important; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REPORTE DE METAS DE VENTAS</h1>
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
        @if($zona)
            <p><strong>Zona:</strong> {{ $zona }}</p>
        @endif
        <p><strong>Total de registros:</strong> {{ $estadisticas['total_registros'] }}</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="30">#</th>
                <th>Plaza</th>
                <th>Tienda</th>
                <th>Sucursal</th>
                <th>Fecha</th>
                <th>Zona</th>
                <th class="text-right">Meta Total</th>
                <th class="text-right">Días Total</th>
                <th class="text-right">Valor Día</th>
                <th class="text-right">Meta Día</th>
                <th class="text-right">Venta Día</th>
                <th class="text-right">Venta Acum.</th>
                <th class="text-right">% Cumplimiento</th>
                <th class="text-right">% Acumulado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultados as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->id_plaza }}</td>
                <td>{{ $item->clave_tienda }}</td>
                <td>{{ $item->sucursal }}</td>
                <td>{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}</td>
                <td>{{ $item->zona }}</td>
                <td class="text-right">{{ number_format($item->meta_total, 2) }}</td>
                <td class="text-right">{{ $item->dias_total }}</td>
                <td class="text-right">{{ number_format($item->valor_dia, 2) }}</td>
                <td class="text-right">{{ number_format($item->meta_dia, 2) }}</td>
                <td class="text-right">{{ number_format($item->venta_del_dia, 2) }}</td>
                <td class="text-right">{{ number_format($item->venta_acumulada, 2) }}</td>
                <td class="text-right">
                    @php
                        $porcentaje = floatval($item->porcentaje);
                        $color = $porcentaje >= 100 ? 'text-success' : ($porcentaje >= 80 ? 'text-warning' : 'text-danger');
                    @endphp
                    <span class="{{ $color }}">{{ number_format($porcentaje, 2) }}%</span>
                </td>
                <td class="text-right">
                    @php
                        $porcentaje_acumulado = floatval($item->porcentaje_acumulado);
                        $color_acum = $porcentaje_acumulado >= 100 ? 'text-success' : ($porcentaje_acumulado >= 80 ? 'text-warning' : 'text-danger');
                    @endphp
                    <span class="{{ $color_acum }}">{{ number_format($porcentaje_acumulado, 2) }}%</span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="9" class="text-right"><strong>TOTALES:</strong></td>
                <td class="text-right"><strong>{{ number_format($estadisticas['total_meta_dia'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($estadisticas['total_venta_dia'], 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($estadisticas['total_venta_acumulada'], 2) }}</strong></td>
                <td class="text-right">
                    @php
                        $porcentaje_total = $estadisticas['porcentaje_promedio'];
                        $color_total = $porcentaje_total >= 100 ? 'text-success' : ($porcentaje_total >= 80 ? 'text-warning' : 'text-danger');
                    @endphp
                    <span class="{{ $color_total }}"><strong>{{ number_format($porcentaje_total, 2) }}%</strong></span>
                </td>
                <td class="text-right">
                    @php
                        // ¡IMPORTANTE! Aquí calculamos el % acumulado TOTAL de la tabla
                        $porcentaje_acumulado_total = $estadisticas['porcentaje_acumulado_global'];
                        $color_acum_total = $porcentaje_acumulado_total >= 100 ? 'text-success' : ($porcentaje_acumulado_total >= 80 ? 'text-warning' : 'text-danger');
                    @endphp
                    <span class="{{ $color_acum_total }}"><strong>{{ number_format($porcentaje_acumulado_total, 2) }}%</strong></span>
                </td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        <p>Documento generado automáticamente por el sistema</p>
        <p>Página 1 de 1</p>
    </div>
</body>
</html>