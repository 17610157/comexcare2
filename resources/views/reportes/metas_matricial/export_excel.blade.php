<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte Metas Matricial</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Reporte Metas Matricial</h1>
    <p>Periodo: {{ $filtros['fecha_inicio'] }} al {{ $filtros['fecha_fin'] }}</p>

    <table>
        <thead>
            <tr>
                <th>Categoría / Fecha</th>
                @foreach($datos['tiendas'] as $tienda)
                    <th>{{ $tienda }}<br>{{ $datos['matriz']['info'][$tienda]['zona'] }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Plaza</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td>{{ $datos['matriz']['info'][$tienda]['plaza'] }}</td>
                @endforeach
                <td>-</td>
            </tr>
            <tr>
                <td>Zona</td>
                @foreach($datos['tiendas'] as $tienda)
                    <td>{{ $datos['matriz']['info'][$tienda]['zona'] }}</td>
                @endforeach
                <td>-</td>
            </tr>
            @foreach($datos['fechas'] as $fecha)
                <tr>
                    <td>Total {{ \Carbon\Carbon::parse($fecha)->format('d/m') }}</td>
                    @php $suma = 0; @endphp
                    @foreach($datos['tiendas'] as $tienda)
                        <td>${{ number_format($datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0, 2) }}</td>
                        @php $suma += $datos['matriz']['datos'][$tienda][$fecha]['total'] ?? 0; @endphp
                    @endforeach
                    <td>${{ number_format($suma, 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td>Suma de los Días Consultados</td>
                @php $suma = 0; @endphp
                @foreach($datos['tiendas'] as $tienda)
                    <td>${{ number_format($datos['matriz']['totales'][$tienda]['total'] ?? 0, 2) }}</td>
                    @php $suma += $datos['matriz']['totales'][$tienda]['total'] ?? 0; @endphp
                @endforeach
                <td>${{ number_format($suma, 2) }}</td>
            </tr>
            <tr>
                <td>Objetivo</td>
                @php $suma = 0; @endphp
                @foreach($datos['tiendas'] as $tienda)
                    @if(($datos['matriz']['info'][$tienda]['meta_total'] ?? 0) > 0)
                        <td>${{ number_format($datos['matriz']['totales'][$tienda]['objetivo'] ?? 0, 2) }}</td>
                        @php $suma += $datos['matriz']['totales'][$tienda]['objetivo'] ?? 0; @endphp
                    @else
                        <td>-</td>
                    @endif
                @endforeach
                <td>${{ number_format($suma, 2) }}</td>
            </tr>
            <!-- Agregar más filas... -->
        </tbody>
    </table>
</body>
</html>