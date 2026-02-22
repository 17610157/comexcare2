<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Metas Matricial por Plaza</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 8px; margin: 0; padding: 10px; }
        .page-break { page-break-before: always; }
        .header { text-align: center; margin-bottom: 20px; }
        .plaza-title { font-size: 12px; font-weight: bold; margin: 20px 0 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 7px; }
        th, td { border: 1px solid #000; padding: 3px; text-align: center; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { background-color: #e9ecef; font-weight: bold; }
        .meta-row { background-color: #d4edda; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Metas de Ventas - Matricial</h1>
        <p>Periodo: {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        <p>Fecha de generación: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    @foreach($plazas as $plaza => $data)
        <div class="page-break">
            <h2 class="plaza-title">Plaza: {{ $plaza }}</h2>

            <table>
                <thead>
                    <tr>
                        <th>Categoría / Fecha</th>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <th>{{ $tienda }}<br>{{ $tiendaData['info']['zona'] }}</th>
                        @endforeach
                        <th>Total Plaza</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila Plazas -->
                    <tr>
                        <td>Plaza</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <td>{{ $tiendaData['info']['plaza'] }}</td>
                        @endforeach
                        <td>-</td>
                    </tr>

                    <!-- Fila Zonas -->
                    <tr>
                        <td>Zona</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <td>{{ $tiendaData['info']['zona'] }}</td>
                        @endforeach
                        <td>-</td>
                    </tr>

                    <!-- Filas Totales Diarios -->
                    @foreach($dias as $dia_key => $dia_formatted)
                        <tr>
                            <td>Total {{ $dia_formatted }}</td>
                            @php $suma_dia = 0; @endphp
                            @foreach($data['tiendas'] as $tienda => $tiendaData)
                                <td class="text-right">${{ number_format($tiendaData['datos'][$dia_key]['total'] ?? 0, 2) }}</td>
                                @php $suma_dia += $tiendaData['datos'][$dia_key]['total'] ?? 0; @endphp
                            @endforeach
                            <td class="text-right">${{ number_format($data['datos_diarios'][$dia_key] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach

                    <!-- Fila Suma Totales -->
                    <tr class="total-row">
                        <td>Suma de los Días Consultados</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <td class="text-right">${{ number_format($tiendaData['totales']['total'] ?? 0, 2) }}</td>
                        @endforeach
                        <td class="text-right">${{ number_format($data['totales']['total'] ?? 0, 2) }}</td>
                    </tr>

                    <!-- Fila Objetivo -->
                    <tr>
                        <td>Objetivo</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            @if(($tiendaData['info']['meta_total'] ?? 0) > 0)
                                <td class="text-right">${{ number_format($tiendaData['totales']['objetivo'] ?? 0, 2) }}</td>
                            @else
                                <td>-</td>
                            @endif
                        @endforeach
                        <td class="text-right">${{ number_format($data['totales']['objetivo'] ?? 0, 2) }}</td>
                    </tr>

                    <!-- Fila Suma Valor Día -->
                    <tr>
                        <td>Suma Valor Día</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <td class="text-right">${{ number_format($tiendaData['info']['suma_valor_dia'] ?? 0, 2) }}</td>
                        @endforeach
                        <td class="text-right">${{ number_format($data['totales']['suma_valor_dia'] ?? 0, 2) }}</td>
                    </tr>

                    <!-- Fila Días Totales -->
                    <tr>
                        <td>Días Totales</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <td>{{ $tiendaData['info']['dias_totales'] ?? $dias_totales }}</td>
                        @endforeach
                        <td>-</td>
                    </tr>

                    <!-- Fila Porcentaje Total -->
                    <tr>
                        <td>Porcentaje Total</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            @if(($tiendaData['info']['meta_total'] ?? 0) > 0)
                                <td class="text-right">{{ number_format($tiendaData['totales']['porcentaje_total'] ?? 0, 1) }}%</td>
                            @else
                                <td>-</td>
                            @endif
                        @endforeach
                        <td class="text-right">{{ number_format($data['totales']['porcentaje_total'] ?? 0, 1) }}%</td>
                    </tr>

                    <!-- Fila Meta Total -->
                    <tr class="meta-row">
                        <td>Meta Total</td>
                        @foreach($data['tiendas'] as $tienda => $tiendaData)
                            <td class="text-right">${{ number_format($tiendaData['info']['meta_total'] ?? 0, 2) }}</td>
                        @endforeach
                        <td class="text-right">${{ number_format($data['totales']['meta_total'] ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top: 10px; font-size: 6px; color: #666;">
                <p>Plaza: {{ $plaza }} | Tiendas: {{ count($data['tiendas']) }} | Fechas: {{ count($dias) }}</p>
            </div>
        </div>
    @endforeach
</body>
</html>