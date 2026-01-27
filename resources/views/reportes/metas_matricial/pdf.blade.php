<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Metas Matricial</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: center; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .table-primary th { background-color: #cce5ff; }
        .table-info th { background-color: #d1ecf1; }
        .table-warning th { background-color: #fff3cd; }
        .table-success th { background-color: #d4edda; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        h1 { text-align: center; margin-bottom: 20px; }
        .header-info { margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Reporte Metas de Ventas - Matricial</h1>

    <div class="header-info">
        <p><strong>Fecha Inicio:</strong> {{ $filtros['fecha_inicio'] }}</p>
        <p><strong>Fecha Fin:</strong> {{ $filtros['fecha_fin'] }}</p>
        <p><strong>Plaza:</strong> {{ $filtros['plaza'] ?: 'Todas' }}</p>
        <p><strong>Tienda:</strong> {{ $filtros['tienda'] ?: 'Todas' }}</p>
        <p><strong>Zona:</strong> {{ $filtros['zona'] ?: 'Todas' }}</p>
        <p><strong>Fecha de Generación:</strong> {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Categoría / Fecha</th>
                @foreach($datos['tiendas'] as $tienda)
                    <th>{{ $tienda }}<br><small>{{ $datos['matriz']['info'][$tienda]['zona'] }}</small></th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <!-- Fila Plazas -->
            <tr>
                <td>Plaza</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td>{{ $datos['matriz']['info'][$tienda]['plaza'] }}</td>
                @endforeach
                <td>-</td>
            </tr>

            <!-- Fila Zonas -->
            <tr>
                <td>Zona</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td>{{ $datos['matriz']['info'][$tienda]['zona'] }}</td>
                @endforeach
                <td>-</td>
            </tr>

            <!-- Filas Totales Diarios -->
            @foreach($datos['fechas'] as $fecha)
                @php $suma_fecha = 0; @endphp
                <tr>
                    <td>Total {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}</td>
                    @foreach($datos['tiendas'] as $tienda)
                        <td class="text-right">${{ number_format($datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0, 2) }}</td>
                        @php $suma_fecha += $datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0; @endphp
                    @endforeach
                    <td class="text-right">${{ number_format($suma_fecha, 2) }}</td>
                </tr>
            @endforeach

            <!-- Fila Suma Totales -->
            @php $suma_totales = 0; @endphp
            <tr>
                <td>Suma de los Días Consultados</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td class="text-right">${{ number_format($datos['matriz']['totales'][$tienda]['total'] ?? 0, 2) }}</td>
                    @php $suma_totales += $datos['matriz']['totales'][$tienda]['total'] ?? 0; @endphp
                @endforeach
                <td class="text-right">${{ number_format($suma_totales, 2) }}</td>
            </tr>

            <!-- Fila Objetivo -->
            @php $suma_objetivo = 0; @endphp
            <tr>
                <td>Objetivo</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td class="text-right">
                        @if(($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0)
                            ${{ number_format($datos['matriz']['totales'][$tienda]['objetivo'] ?? 0, 2) }}
                            @php $suma_objetivo += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0; @endphp
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                <td class="text-right">${{ number_format($suma_objetivo, 2) }}</td>
            </tr>

            <!-- Fila Suma Valor Día -->
            @php $suma_valor_dia_total = 0; @endphp
            <tr>
                <td>Suma Valor Día</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td class="text-right">${{ number_format($datos['matriz']['info'][$tienda]['suma_valor_dia'] ?? 0, 2) }}</td>
                    @php $suma_valor_dia_total += $datos['matriz']['info'][$tienda]['suma_valor_dia'] ?? 0; @endphp
                @endforeach
                <td class="text-right">${{ number_format($suma_valor_dia_total, 2) }}</td>
            </tr>

            <!-- Fila Días Totales -->
            <tr>
                <td>Días Totales</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td>{{ $datos['matriz']['info'][$tienda]['dias_totales'] ?? $datos['dias_totales'] }}</td>
                @endforeach
                <td>-</td>
            </tr>

            <!-- Fila Porcentaje Total -->
            @php
                $suma_totales_global = 0;
                $suma_objetivos_global = 0;
                foreach($datos['tiendas'] as $tienda) {
                    $suma_totales_global += $datos['matriz']['totales'][$tienda]['total'] ?? 0;
                    if (($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0) {
                        $suma_objetivos_global += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0;
                    }
                }
                $porcentaje_global = $suma_objetivos_global > 0 ? ($suma_totales_global / $suma_objetivos_global) * 100 : 0;
            @endphp
            <tr>
                <td>Porcentaje Total</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td class="text-right">
                        @if(($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0)
                            {{ number_format($datos['matriz']['totales'][$tienda]['porcentaje_total'] ?? 0, 1) }}%
                        @else
                            -
                        @endif
                    </td>
                @endforeach
                <td class="text-right">{{ number_format($porcentaje_global, 1) }}%</td>
            </tr>

            <!-- Fila Meta Total -->
            @php $suma_meta_total = 0; @endphp
            <tr>
                <td>Meta Total</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td class="text-right">${{ number_format($datos['matriz']['totales'][$tienda]['meta_total'] ?? 0, 2) }}</td>
                    @php $suma_meta_total += $datos['matriz']['totales'][$tienda]['meta_total'] ?? 0; @endphp
                @endforeach
                <td class="text-right">${{ number_format($suma_meta_total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 8px; color: #666;">
        <p>Reporte generado el {{ date('Y-m-d H:i:s') }} | {{ count($datos['tiendas']) }} tiendas | {{ count($datos['fechas']) }} días</p>
    </div>
</body>
</html>