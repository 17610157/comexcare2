<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Compras Directo</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .filters { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; font-weight: bold; }
        .table .text-right { text-align: right; }
        .totals { background-color: #007bff; color: white; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Reporte de Compras Directo</h2>
        <p>Período: {{ $fecha_inicio }} al {{ $fecha_fin }}</p>
        @if($plaza) <p>Plaza: {{ $plaza }}</p> @endif
        @if($tienda) <p>Tienda: {{ $tienda }}</p> @endif
        @if($proveedor) <p>Proveedor: {{ $proveedor }}</p> @endif
        <p>Fecha de generación: {{ $fecha_reporte }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Plaza</th>
                <th>Tienda</th>
                <th>Tipo Doc</th>
                <th>No. Referencia</th>
                <th>Tipo Doc A</th>
                <th>No. Factura</th>
                <th>Clave Proveedor</th>
                <th>Nombre Proveedor</th>
                <th>Cuenta</th>
                <th>Fecha Emisión</th>
                <th>Clave Artículo</th>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $item)
            <tr>
                <td>{{ $item['no'] }}</td>
                <td>{{ $item['cplaza'] }}</td>
                <td>{{ $item['ctienda'] }}</td>
                <td>{{ $item['tipo_doc'] }}</td>
                <td>{{ $item['no_referen'] }}</td>
                <td>{{ $item['tipo_doc_a'] }}</td>
                <td>{{ $item['no_fact_pr'] }}</td>
                <td>{{ $item['clave_pro'] }}</td>
                <td>{{ $item['nombre'] }}</td>
                <td>{{ $item['cuenta'] }}</td>
                <td>{{ $item['f_emision'] }}</td>
                <td>{{ $item['clave_art'] }}</td>
                <td>{{ $item['descripcio'] }}</td>
                <td class="text-right">{{ number_format($item['cantidad'], 2) }}</td>
                <td class="text-right">{{ number_format($item['precio_uni'], 2) }}</td>
                <td class="text-right">{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="13">TOTALES</td>
                <td class="text-right">{{ number_format($total_cantidad, 2) }}</td>
                <td></td>
                <td class="text-right">{{ number_format($total_compras, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Resumen:</p>
        <p>Total Registros: {{ $total_registros }} | Total Compras: ${{ number_format($total_compras, 2) }} | Total Cantidad: {{ number_format($total_cantidad, 2) }} | Promedio Precio: ${{ number_format($promedio_precio, 2) }}</p>
    </div>
</body>
</html>