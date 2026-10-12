<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Concentrado de Archivos de Stock</title>
    <style>
        @page { margin: 24px 28px 42px 28px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #212529; }
        h1 { font-size: 17px; margin: 0 0 2px 0; }
        h2 { font-size: 12px; margin: 16px 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #dee2e6; }
        h3 { font-size: 11px; margin: 12px 0 4px 0; }
        p { margin: 4px 0; line-height: 1.45; }
        .muted { color: #6c757d; }
        .small { font-size: 9px; }
        .header { border-bottom: 2px solid #007bff; padding-bottom: 8px; margin-bottom: 10px; }
        .header .fecha { float: right; text-align: right; font-size: 9px; color: #6c757d; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 8px 0; }
        .cards td { width: 25%; text-align: center; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px 4px; background: #f8f9fa; }
        .cards .num { display: block; font-size: 18px; font-weight: bold; }
        .cards .lbl { display: block; font-size: 9px; color: #6c757d; }
        .cards .verde { color: #28a745; }
        .cards .rojo { color: #dc3545; }
        .cards .azul { color: #0d6efd; }
        table.data { width: 100%; border-collapse: collapse; margin: 4px 0 8px 0; }
        table.data th, table.data td { border: 1px solid #ced4da; padding: 3px 5px; text-align: left; vertical-align: top; }
        table.data th { background: #e9ecef; font-size: 9px; }
        table.data td.num, table.data th.num { text-align: right; }
        table.data td.c { text-align: center; }
        .bar { height: 10px; background: #e9ecef; border-radius: 6px; overflow: hidden; }
        .bar span { display: block; height: 10px; background: #28a745; }
        .pen-list { margin: 0 0 8px 0; }
        .pen-list .tienda { margin-bottom: 3px; }
        .pen-list .clave { font-family: DejaVu Sans Mono, monospace; font-weight: bold; }
        .chart { text-align: center; margin: 6px 0 10px 0; }
        .chart img { border: 1px solid #e9ecef; }
        .chart-pie img { width: 240px; }
        .chart-wide img { width: 100%; max-width: 640px; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; text-align: center; font-size: 8px; color: #adb5bd; }
        .tag { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 8px; border: 1px solid #ced4da; }
        .tag-off { color: #dc3545; border-color: #f5c2c7; }
        .tag-on { color: #198754; border-color: #badbcc; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>

@php
    $nombre = fn ($codigo) => $plaza_nombres[$codigo] ?? $codigo;
    $fmt = fn ($n) => number_format((float) $n, 1, '.', ',');
    $mx = $resumen_mx;
    $fuera = $resumen_fuera;
    $totalPendientesMx = (int) $mx['pendientes'];
@endphp

<div class="header">
    <div class="fecha">
        Corte de revisión<br>
        <strong>{{ $generado->format('d/m/Y H:i') }} hrs</strong>
    </div>
    <h1>Concentrado de Archivos de Stock</h1>
    <div class="small muted">Análisis de respaldos de archivos (RBF / Rebsamen) · Unidad W:</div>
</div>

<h2>Resumen del análisis</h2>
<p>
    El día de hoy se generó el concentrado de los archivos del área con el
    <strong>{{ $fmt($mx['percent']) }}%</strong> de las tiendas
    ({{ $mx['listas'] }} de {{ $mx['total'] }}), tomando como base los respaldos
    disponibles en la unidad W:.
</p>
<p>
    Se considera lista una tienda cuando sus archivos principales
    (CAT_PROD, DD_CONTROL, DD_DATOS, MOVSINV, PEDIDO) están en W: con una antigüedad
    máxima de 1 día (PEDIDO hasta 3 días) y sin variaciones anormales de tamaño.
    Las plazas GUATE y NICAR (Fuera de México) se muestran por separado y no se
    contemplan en el porcentaje.
</p>

<table class="cards">
    <tr>
        <td><span class="num">{{ $mx['total'] }}</span><span class="lbl">Tiendas contempladas</span></td>
        <td><span class="num verde">{{ $mx['listas'] }}</span><span class="lbl">Listas</span></td>
        <td><span class="num rojo">{{ $mx['pendientes'] }}</span><span class="lbl">Pendientes</span></td>
        <td><span class="num azul">{{ $fmt($mx['percent']) }}%</span><span class="lbl">Instalaciones listas</span></td>
    </tr>
</table>

<p class="small muted">
    México: {{ $mx['listas'] }} listas y {{ $mx['pendientes'] }} pendientes de {{ $mx['total'] }} tiendas contempladas.
    Fuera de México (aparte): {{ $fuera['listas'] }} listas y {{ $fuera['pendientes'] }} pendientes de {{ $fuera['total'] }} tiendas.
</p>

@if($chart_pie || $chart_plaza)
<h2>Gráficas</h2>
<table style="width:100%; border-collapse:collapse;">
    <tr>
        @if($chart_pie)
        <td style="width:32%; text-align:center; vertical-align:top;">
            <div class="chart chart-pie"><img src="{{ $chart_pie }}" alt="Listas vs pendientes"></div>
        </td>
        @endif
        @if($chart_plaza)
        <td style="text-align:center; vertical-align:top;">
            <div class="chart chart-wide"><img src="{{ $chart_plaza }}" alt="Instalaciones por plaza"></div>
        </td>
        @endif
    </tr>
</table>
@endif
@if($chart_causas)
<div class="chart chart-wide"><img src="{{ $chart_causas }}" alt="Motivos de pendencia"></div>
@endif

<h2>Detalle por plaza (México)</h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:70px;">Plaza</th>
            <th>Nombre</th>
            <th class="num" style="width:60px;">Tiendas</th>
            <th class="num" style="width:55px;">Listas</th>
            <th class="num" style="width:70px;">Pendientes</th>
            <th class="num" style="width:60px;">% Listas</th>
            <th style="width:110px;">Avance</th>
        </tr>
    </thead>
    <tbody>
        @forelse($per_plaza_mx as $p)
        <tr>
            <td><strong>{{ $p['plaza'] }}</strong></td>
            <td>{{ $nombre($p['plaza']) }}</td>
            <td class="num">{{ $p['total'] }}</td>
            <td class="num">{{ $p['listas'] }}</td>
            <td class="num">{{ $p['pendientes'] }}</td>
            <td class="num">{{ $fmt($p['percent']) }}%</td>
            <td>
                <div class="bar"><span style="width: {{ $p['percent'] }}%;"></span></div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="c muted">Sin plazas contempladas.</td></tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <th colspan="2">TOTAL</th>
            <th class="num">{{ $mx['total'] }}</th>
            <th class="num">{{ $mx['listas'] }}</th>
            <th class="num">{{ $mx['pendientes'] }}</th>
            <th class="num">{{ $fmt($mx['percent']) }}%</th>
            <th></th>
        </tr>
    </tfoot>
</table>

<h2>Top del análisis</h2>
@if(!empty($top_plazas))
<h3>Plazas con más pendientes</h3>
<table class="data">
    <thead>
        <tr><th style="width:70px;">Plaza</th><th>Nombre</th><th class="num">Pendientes</th><th class="num">De</th><th class="num">% Listas</th></tr>
    </thead>
    <tbody>
        @foreach($top_plazas as $p)
        @if($p['pendientes'] > 0)
        <tr>
            <td><strong>{{ $p['plaza'] }}</strong></td>
            <td>{{ $nombre($p['plaza']) }}</td>
            <td class="num">{{ $p['pendientes'] }}</td>
            <td class="num">{{ $p['total'] }}</td>
            <td class="num">{{ $fmt($p['percent']) }}%</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($top_causas))
<h3>Motivos de pendencia más frecuentes</h3>
<table class="data">
    <thead>
        <tr><th>Motivo</th><th class="num" style="width:120px;">Instalaciones</th></tr>
    </thead>
    <tbody>
        @foreach($top_causas as $c)
        <tr>
            <td>{{ $c['etiqueta'] }}</td>
            <td class="num">{{ $c['cantidad'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($top_atrasadas))
<h3>Tiendas con mayor atraso</h3>
<table class="data">
    <thead>
        <tr>
            <th style="width:70px;">Clave</th>
            <th>Tienda</th>
            <th style="width:60px;">Plaza</th>
            <th style="width:90px;">Archivo</th>
            <th class="num" style="width:60px;">Días</th>
        </tr>
    </thead>
    <tbody>
        @foreach($top_atrasadas as $t)
        <tr>
            <td class="clave">{{ $t['short_key'] }}</td>
            <td>{{ $t['nombre'] }}</td>
            <td>{{ $t['plaza'] }}</td>
            <td>{{ $t['archivo'] }}</td>
            <td class="num">{{ $t['dias'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="page-break"></div>

<h2>Tiendas pendientes (México)</h2>
<p class="small muted">Total de tiendas pendientes en México: <strong>{{ $totalPendientesMx }}</strong>. Se listan agrupadas por plaza.</p>

@forelse($per_plaza_mx as $p)
    @php $detalle = $pendientes_mx[$p['plaza']] ?? []; @endphp
    @if(count($detalle))
    <h3>{{ $p['plaza'] }} - {{ $nombre($p['plaza']) }} ({{ count($detalle) }})</h3>
    <div class="pen-list">
        @foreach($detalle as $t)
        <div class="tienda">
            <span class="clave">{{ $t['short_key'] }}</span>
            <span>{{ $t['nombre'] }}</span>
            @if($t['equipo_online'])
            <span class="tag tag-on">online</span>
            @else
            <span class="tag tag-off">offline</span>
            @endif
            <span class="muted">- {{ implode('; ', $t['detalles']) }}</span>
        </div>
        @endforeach
    </div>
    @endif
@empty
    <p class="muted">Sin tiendas pendientes contempladas en México.</p>
@endforelse

<h2>Fuera de México (aparte, no se contempla en el %)</h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:70px;">Plaza</th>
            <th>Nombre</th>
            <th class="num" style="width:60px;">Tiendas</th>
            <th class="num" style="width:55px;">Listas</th>
            <th class="num" style="width:70px;">Pendientes</th>
            <th class="num" style="width:60px;">% Listas</th>
        </tr>
    </thead>
    <tbody>
        @forelse($per_plaza_fuera as $p)
        <tr>
            <td><strong>{{ $p['plaza'] }}</strong></td>
            <td>{{ $nombre($p['plaza']) }}</td>
            <td class="num">{{ $p['total'] }}</td>
            <td class="num">{{ $p['listas'] }}</td>
            <td class="num">{{ $p['pendientes'] }}</td>
            <td class="num">{{ $fmt($p['percent']) }}%</td>
        </tr>
        @empty
        <tr><td colspan="6" class="c muted">Sin plazas fuera de México.</td></tr>
        @endforelse
    </tbody>
</table>

@if(!empty($pendientes_fuera))
<h3>Pendientes fuera de México</h3>
@foreach($per_plaza_fuera as $p)
    @php $detalle = $pendientes_fuera[$p['plaza']] ?? []; @endphp
    @if(count($detalle))
    <h3>{{ $p['plaza'] }} - {{ $nombre($p['plaza']) }} ({{ count($detalle) }})</h3>
    <div class="pen-list">
        @foreach($detalle as $t)
        <div class="tienda">
            <span class="clave">{{ $t['short_key'] }}</span>
            <span>{{ $t['nombre'] }}</span>
            <span class="muted">- {{ implode('; ', $t['detalles']) }}</span>
        </div>
        @endforeach
    </div>
    @endif
@endforeach
@endif

<p class="small muted" style="margin-top:14px;">
    Nota: el detalle por archivo (fecha, hora y peso) puede consultarse en el dashboard.
    Reporte generado automáticamente el {{ $generado->format('d/m/Y H:i') }} hrs.
</p>

<div class="footer">
    Sistema ComexCare · Concentrado de Archivos de Stock
</div>

</body>
</html>
