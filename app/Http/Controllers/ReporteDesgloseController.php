<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReporteDesgloseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $hasPermission = $user && $user->can('reportes.metas-matricial.ver');

        $userFilter = RoleHelper::getUserFilter();
        $allowed = $userFilter['allowed'] || $hasPermission;

        if (! $allowed) {
            return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
        }

        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $zona = $request->input('zona', []);

        $listas = RoleHelper::getListasParaFiltros();

        // Obtener TODAS las plazas y tiendas (sin filtro de asignaciones) para los filtros
        $todasPlazas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->where('estado', '<>', 'c')
            ->whereRaw('CAST(id_tipo AS INTEGER) = 1')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values();

        $todasTiendas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('clave_tienda')
            ->where('estado', '<>', 'c')
            ->whereRaw('CAST(id_tipo AS INTEGER) = 1')
            ->orderBy('clave_tienda')
            ->pluck('clave_tienda')
            ->filter()
            ->values();

        // Usar todas (sin filtro de asignaciones) para los filtros de la vista
        $plazas = $todasPlazas->toArray();
        $tiendas = $todasTiendas->toArray();
        $plazasAsignadas = $listas['plazas_asignadas'];
        $tiendasAsignadas = $listas['tiendas_asignadas'];

        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');
        $zonaInput = $request->input('zona', []);

        $plaza = '';
        if (! empty($plazaInput)) {
            $plazaValues = is_array($plazaInput) ? $plazaInput : explode(',', $plazaInput);
            $plazaFilter = array_filter($plazaValues, fn ($p) => in_array($p, $plazasAsignadas));
            $plaza = ! empty($plazaFilter) ? implode(',', array_values($plazaFilter)) : '';
        }

        $tienda = '';
        if (! empty($tiendaInput)) {
            $tiendaValues = is_array($tiendaInput) ? $tiendaInput : explode(',', $tiendaInput);
            $tiendaFilter = array_filter($tiendaValues, fn ($t) => in_array($t, $tiendasAsignadas));
            $tienda = ! empty($tiendaFilter) ? implode(',', array_values($tiendaFilter)) : '';
        }

        $zona = '';
        if (! empty($zonaInput)) {
            $zonaValues = is_array($zonaInput) ? $zonaInput : explode(',', $zonaInput);
            $zona = implode(',', array_map('trim', $zonaValues));
        }

        $tiendasExcluidas = [
            'ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', 'BOVER',
            '2ORIE', 'ALMAP', 'ALMAR', 'AMAGT', 'APSIV', 'AUTO4', 'BCEDV', 'BMADE', 'BOLGT', 'BPROS',
            'BRECL', 'BVENA', 'CHIGT', 'CMOBI', 'COAGT', 'CPUBL', 'CRECL', 'CRYST', 'FERRE', 'FUTGT',
            'GABED', 'GALAM', 'GARBO', 'GATLA', 'GCALL', 'GCATO', 'GCHCV', 'GCIUD', 'GCOLO', 'GCTLA',
            'GECHE', 'GESTA', 'GGOLF', 'GIGNA', 'GJUNT', 'GLADG', 'GLAZA', 'GLAZB', 'GLAZC', 'GLOMA',
            'GMAGN', 'GMIRA', 'GMOLI', 'GNIOB', 'GNOGA', 'GPANG', 'GPARQ', 'GPATB', 'GPROS', 'GPUBL',
            'GSJUA', 'GSPAL', 'GTORR', 'GVALL', 'GVENA', 'GVENB', 'GVENC', 'GVEND', 'GVENE', 'GZAPC',
            'GZAPO', 'HCENB', 'HCXVE', 'HMORE', 'HNAVA', 'HPUBL', 'HRECL', 'ICORO', 'IGUAL', 'ISLUC',
            'JUARE', 'JUMAG', 'LUPA', 'LUPAN', 'MASTE', 'MCEDV', 'MINGT', 'MRTGT', 'MTAPE', 'N8SUR',
            'NCANO', 'NCAOR', 'NCIJA', 'NJINO', 'NMASA', 'NMASB', 'NMATA', 'NMEOR', 'NPESP', 'NVENA',
            'NVENB', 'NVENC', 'PCEDJ', 'PCEDV', 'PLAKA', 'PMOBI', 'POETA', 'PPUBL', 'PRECL', 'PROGT',
            'PSJGT', 'PUB08', 'RECLA', 'RETGT', 'REYES', 'ROOGT', 'RVENB', 'RVENC', 'SC2GT', 'SJUGT',
            'SLCGT', 'SLUGT', 'SUBAL', 'SUMID', 'SUR06', 'T0022', 'T0044', 'T0048', 'TB2B1', 'TCEDV',
            'TCSUR', 'TPAUL', 'TPKTO', 'TPSER', 'TVEN3', 'TVESP', 'VBCSA', 'VCEDV', 'VEND2', 'VPLAK',
            'XAUTD', 'XCEDV', 'XCHED', 'XMERA', 'XPIPI',
        ];

        $whereTienda = "bst.clave_tienda IS NOT NULL AND bst.estado <> 'c'";
        foreach ($tiendasExcluidas as $t) {
            $whereTienda .= " AND bst.clave_tienda <> '$t'";
        }
        $whereTienda .= ' AND CAST(bst.id_tipo AS INTEGER) = 1';

        $sql = "
        SELECT
            xc.fecha,
            xc.cplaza as plaza,
            xc.ctienda as tienda,
            bst.nombre as nombre_tienda,
            bst.zona,
            COALESCE(xc.vtacont, 0) - COALESCE(xc.descont, 0) as venta_contado,
            COALESCE(xc.vtacred, 0) - COALESCE(xc.descred, 0) as venta_credito,
            (COALESCE(xc.vtacont, 0) - COALESCE(xc.descont, 0)) +
            (COALESCE(xc.vtacred, 0) - COALESCE(xc.descred, 0)) as venta_total
        FROM xcorte xc
        LEFT JOIN bi_sys_tiendas bst ON xc.ctienda = bst.clave_tienda OR xc.ctienda LIKE bst.clave_tienda || '%' OR xc.ctienda = bst.clave_alterna
        WHERE $whereTienda
          AND xc.fecha BETWEEN ? AND ?
        ";

        $params = [$fecha_inicio, $fecha_fin];

        if (! empty($plaza)) {
            $plazas = array_map('trim', explode(',', $plaza));
            if (count($plazas) > 1) {
                $placeholders = implode(',', array_fill(0, count($plazas), '?'));
                $sql .= " AND xc.cplaza IN ($placeholders)";
                $params = array_merge($params, $plazas);
            } else {
                $sql .= ' AND xc.cplaza = ?';
                $params[] = $plaza;
            }
        }

        if (! empty($tienda)) {
            $tiendas = array_map('trim', explode(',', $tienda));
            if (count($tiendas) > 1) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $sql .= " AND xc.ctienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            } else {
                $sql .= ' AND xc.ctienda = ?';
                $params[] = $tienda;
            }
        }

        if (! empty($zona)) {
            $sql .= ' AND bst.zona = ?';
            $params[] = $zona;
        }

        $sql .= ' ORDER BY xc.cplaza, xc.ctienda, xc.fecha';

        $datos = DB::select($sql, $params);

        $agrupado = [];
        foreach ($datos as $row) {
            $key = $row->plaza.'|'.$row->tienda;
            if (! isset($agrupado[$key])) {
                $agrupado[$key] = [
                    'plaza' => $row->plaza,
                    'tienda' => $row->tienda,
                    'nombre' => $row->nombre_tienda,
                    'zona' => $row->zona,
                    'contado' => [],
                    'credito' => [],
                    'total_contado' => 0,
                    'total_credito' => 0,
                ];
            }
            $agrupado[$key]['contado'][$row->fecha] = $row->venta_contado;
            $agrupado[$key]['credito'][$row->fecha] = $row->venta_credito;
            $agrupado[$key]['total_contado'] += $row->venta_contado;
            $agrupado[$key]['total_credito'] += $row->venta_credito;
        }

        $fechas = [];
        $currentDate = strtotime($fecha_inicio);
        $endDate = strtotime($fecha_fin);
        while ($currentDate <= $endDate) {
            $fechas[] = date('Y-m-d', $currentDate);
            $currentDate = strtotime('+1 day', $currentDate);
        }

        return view('reportes.desglose.index', compact(
            'fecha_inicio',
            'fecha_fin',
            'plaza',
            'tienda',
            'zona',
            'plazas',
            'tiendas',
            'agrupado',
            'fechas'
        ));
    }
}
