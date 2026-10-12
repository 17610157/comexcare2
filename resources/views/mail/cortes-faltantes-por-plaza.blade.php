<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cortes faltantes - Plaza {{ $plaza }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; color: #222; line-height: 1.5; margin: 0; padding: 16px;">
    <p>Buenos días, comparto las listas de las tiendas que no tienen corte o si tuvieron pero problemas nocturnos no pudieron respaldar su información y la lista de tiendas de la regla.</p>

    <p style="font-size: 14px; color: #555;">
        <strong>Plaza:</strong> {{ $plaza }}<br>
        <strong>Fecha:</strong> {{ $fecha->format('d/m/Y') }}
    </p>

    <h3 style="margin-bottom: 6px;">1. Tiendas con problemas De Equipo / Agente </h3>
    @if (count($sinCorte) > 0)
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; font-size: 13px;">
            <thead style="background: #f2f2f2;">
                <tr>
                    <th align="left">Tienda</th>
                    <th align="left">Nombre / Instalación</th>
                    <th align="left">Estado</th>
                    <th align="left">Detalle</th>
                    <th align="left">Último contacto</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sinCorte as $tienda)
                    <tr>
                        <td>{{ $tienda['clave_tienda'] }}</td>
                        <td>{{ $tienda['nombre'] ?? '-' }}</td>
                        <td>{{ $tienda['motivo'] ?? 'Sin corte' }}</td>
                        <td>{{ $tienda['detalle'] ?? '' }}</td>
                        <td>{{ $tienda['ultimo_contacto'] ?? 'Sin registro' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="font-size: 13px; color: #666;">Sin tiendas en esta lista.</p>
    @endif

    <h3 style="margin-bottom: 6px; margin-top: 20px;">2. Tiendas de la regla (Fecha del Corte distinta a la fecha de elaboración del corte)</h3>
    @if (count($regla) > 0)
        <table cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; font-size: 13px;">
            <thead style="background: #f2f2f2;">
                <tr>
                    <th align="left">Tienda</th>
                    <th align="left">Nombre / Instalación</th>
                    <th align="left">Fecha del Corte</th>
                    <th align="left">Fecha de elaboración del corte</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($regla as $tienda)
                    <tr>
                        <td>{{ $tienda['clave_tienda'] }}</td>
                        <td>{{ $tienda['nombre'] ?? '-' }}</td>
                        <td>{{ $tienda['fecha_corte'] ?? '' }}</td>
                        <td>{{ $tienda['fecha_registro'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="font-size: 13px; color: #666;">Sin tiendas en esta lista.</p>
    @endif



    <p style="font-size: 13px; color: #555;">
        {{ config('mail.from.name') }}<br>
        {{ config('mail.from.address') }}
    </p>
</body>
</html>
