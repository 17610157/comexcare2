<?php

namespace App\Http\Controllers\Reportes;

use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ReporteRedencionesClubController extends Controller
{
    public function index()
    {
        $userFilter = RoleHelper::getUserFilter();

        // Si no tiene acceso permitido, verificar si es admin (acceso completo)
        if (!$userFilter['allowed']) {
            $user = Auth::user();
            if (!$user || !$user->hasRole(['super_admin', 'admin'])) {
                return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
            }
        }

        $startDefault = Carbon::parse('first day of previous month')->toDateString();
        $endDefault = Carbon::parse('last day of previous month')->toDateString();

        // Obtener listas filtradas por asignaciones del usuario
        $listas = RoleHelper::getListasParaFiltros();
        
        $plazas = $listas['plazas'];
        $tiendas = $listas['tiendas'];

        return view('reportes.redenciones_club.index', compact('plazas', 'tiendas', 'startDefault', 'endDefault'));
    }

    public function data(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        // Si no tiene acceso permitido, verificar si es admin (acceso completo)
        if (!$userFilter['allowed']) {
            $user = Auth::user();
            if (!$user || !$user->hasRole(['super_admin', 'admin'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
            // Si es admin, crear filtro de acceso completo
            $userFilter = [
                'allowed' => true,
                'plazas_asignadas' => [],
                'tiendas_asignadas' => [],
                'acceso_todas_tiendas' => true,
            ];
        }

        Log::info('RedencionesClub data request', ['url' => $request->fullUrl(), 'params' => $request->all(), 'filter' => $userFilter]);

        $start = Carbon::parse('first day of previous month')->toDateString();
        $end = Carbon::parse('last day of previous month')->toDateString();
        if ($request->filled('period_start')) {
            $start = $request->input('period_start');
        }
        if ($request->filled('period_end')) {
            $end = $request->input('period_end');
        }

        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value', '');
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;

        // Obtener tiendas y plazas permitidas usando el helper
        $tiendasPermitidas = RoleHelper::getTiendasAcceso();
        $plazasPermitidas = $userFilter['plazas_asignadas'] ?? [];
        $accesoCompleto = $userFilter['acceso_todas_tiendas'] ?? false;

        try {
            // Verificar si existe la tabla de caché
            $tableExists = DB::getSchemaBuilder()->hasTable('redenciones_club_cache');

            if (!$tableExists) {
                // Usar consulta directa si no hay caché
                return $this->getDirectData($request, $start, $end, $draw, $startIdx, $length, $search, $tiendasPermitidas, $plazasPermitidas);
            }

            $query = DB::table('redenciones_club_cache');

            // Filtros según el rol del usuario - plazas (solo si tiene asignaciones específicas)
            if (!empty($plazasPermitidas) && !$accesoCompleto) {
                $query->whereIn('cplaza', $plazasPermitidas);
            }

            // Filtros según el rol del usuario - tiendas específicas (solo si tiene asignaciones específicas)
            if (!empty($tiendasPermitidas) && !$accesoCompleto) {
                $query->whereIn('ctienda', $tiendasPermitidas);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('cplaza', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ctienda', 'ILIKE', '%'.$search.'%')
                        ->orWhere('cve_con', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ref_num', 'ILIKE', '%'.$search.'%')
                        ->orWhere('vend_clave', 'ILIKE', '%'.$search.'%')
                        ->orWhere('nota_folio', 'ILIKE', '%'.$search.'%')
                        ->orWhere('prod_clave', 'ILIKE', '%'.$search.'%');
                });
            }

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $plazaFilter = $request->input('plaza');
                if (is_array($plazaFilter) && count($plazaFilter) > 0) {
                    $query->whereIn('cplaza', $plazaFilter);
                } elseif (!is_array($plazaFilter)) {
                    $query->where('cplaza', trim($plazaFilter));
                }
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $tiendaFilter = $request->input('tienda');
                if (is_array($tiendaFilter) && count($tiendaFilter) > 0) {
                    $query->whereIn('ctienda', $tiendaFilter);
                } elseif (!is_array($tiendaFilter)) {
                    $query->where('ctienda', trim($tiendaFilter));
                }
            }

            if ($request->filled('vendedor') && $request->input('vendedor') !== '') {
                $vendedorFilter = $request->input('vendedor');
                if (is_array($vendedorFilter) && count($vendedorFilter) > 0) {
                    $query->whereIn('vend_clave', $vendedorFilter);
                } else {
                    $query->where('vend_clave', trim($vendedorFilter));
                }
            }

            // Fechas - usar formato correcto
            $query->whereBetween('fecha', [$start, $end]);

            $total = $query->count();

            $data = $query->orderBy('fecha')->orderBy('ctienda')->orderBy('ref_num')
                ->offset($offsetInt)
                ->limit($lengthInt)
                ->get();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('RedencionesClub data error: '.$e->getMessage());

            return response()->json(['draw' => (int) $request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Consulta directa sin caché
     */
    private function getDirectData($request, $start, $end, $draw, $startIdx, $length, $search, $tiendasPermitidas, $plazasPermitidas)
    {
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;
        $startYmd = str_replace('-', '', $start);
        $endYmd = str_replace('-', '', $end);

        $sql = "
            SELECT 
                notas.cplaza,
                notas.ctienda,
                f.cve_con,
                f.fecha,
                f.ref_tipo,
                f.ref_num,
                f.importe,
                f.ing_egr,
                ca.ccampo3 as club_id,
                notas.vend_clave,
                notas.nota_folio,
                notas.cfolio_r,
                CASE WHEN notas.nsaldo > 0 THEN 'credito' ELSE 'contado' END AS tipo_venta,
                notas.clie_clave,
                notas.ban_status,
                notas.nota_fecha,
                notas.prod_clave,
                notas.cdesc_adi,
                notas.nota_canti,
                notas.nota_preci,
                CASE WHEN notas.nota_preci IS NULL THEN 0 ELSE notas.nota_canti * notas.nota_preci END AS subtotal,
                notas.nota_impor,
                notas.ncampo1,
                notas.sr_recno
            FROM (
                SELECT cplaza, ctienda, cve_con, fecha, ref_tipo, ref_num, importe, ing_egr
                FROM flujores
                WHERE fecha >= ? AND fecha <= ? AND cve_con = 'PP' AND cborrado <> '1'
            ) f
            LEFT JOIN (
                SELECT 
                    c.cplaza, c.ctienda, c.nota_folio, c.vend_clave, c.cfolio_r, c.nsaldo, c.clie_clave, 
                    c.ban_status, c.nota_fecha, c.nota_impor,
                    cu.prod_clave, cu.cdesc_adi, cu.nota_canti, cu.nota_preci, cu.sr_recno, cu.ncampo1
                FROM canota c
                JOIN cunota cu ON cu.nota_folio = c.nota_folio AND c.cplaza = cu.cplaza AND c.ctienda = cu.ctienda
                WHERE c.nota_fecha >= ? AND c.nota_fecha <= ?
            ) notas ON notas.nota_folio = f.ref_num AND notas.cplaza = f.cplaza AND notas.ctienda = f.ctienda
            LEFT JOIN (
                SELECT cplaza, ctienda, nota_folio, ccampo3 
                FROM canotaex
            ) ca ON notas.nota_folio = ca.nota_folio AND notas.cplaza = ca.cplaza AND notas.ctienda = ca.ctienda
            WHERE notas.nota_folio IS NOT NULL
        ";

        $params = [$startYmd, $endYmd, $start, $end];

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as subquery";
        $totalResult = DB::select($countSql, $params);
        $total = $totalResult[0]->total ?? 0;

        // Obtener datos con paginación
        $sql .= " LIMIT {$lengthInt} OFFSET {$offsetInt}";
        $data = DB::select($sql, $params);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int) $total,
            'recordsFiltered' => (int) $total,
            'data' => $data,
            'warning' => 'Usando datos en tiempo real. Sincronice para mejor rendimiento.'
        ]);
    }

    public function exportExcel(Request $request)
    {
        // Funcionalidad de Excel no implementada - usar CSV
        return $this->exportCsv($request);
    }

    public function exportCsv(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $vendedor = $request->input('vendedor', '');

        $tableExists = DB::getSchemaBuilder()->hasTable('redenciones_club_cache');
        
        if ($tableExists) {
            $query = DB::table('redenciones_club_cache')
                ->whereBetween('fecha', [$start, $end]);

            if ($plaza !== '') {
                $query->where('cplaza', trim($plaza));
            }
            if ($tienda !== '') {
                $query->where('ctienda', trim($tienda));
            }
            if ($vendedor !== '') {
                $query->where('vend_clave', trim($vendedor));
            }
        } else {
            $query = DB::table('flujores')
                ->whereBetween('fecha', [str_replace('-', '', $start), str_replace('-', '', $end)])
                ->where('cve_con', 'PP')
                ->where('cborrado', '<>', '1');
        }

        $filename = 'redenciones_club_'.str_replace('-', '', $start).'_to_'.str_replace('-', '', $end).'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($query, $tableExists) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Plaza', 'Tienda', 'Concepto', 'Fecha', 'Ref Tipo', 'Ref Num',
                'Importe', 'Ing/Egr', 'Club ID', 'Vendedor', 'Nota Folio', 'Folio R',
                'Tipo Venta', 'Cliente', 'Status', 'Fecha Nota', 'Producto',
                'Descripcion', 'Cantidad', 'Precio', 'Subtotal', 'Importe'
            ]);

            $query->orderBy('fecha')->orderBy('ctienda')->orderBy('ref_num')
                ->chunk(1000, function ($rows) use ($file) {
                    foreach ($rows as $row) {
                        fputcsv($file, [
                            $row->cplaza ?? '',
                            $row->ctienda ?? '',
                            $row->cve_con ?? '',
                            $row->fecha ?? '',
                            $row->ref_tipo ?? '',
                            $row->ref_num ?? '',
                            $row->importe ?? 0,
                            $row->ing_egr ?? '',
                            $row->club_id ?? '',
                            $row->vend_clave ?? '',
                            $row->nota_folio ?? '',
                            $row->cfolio_r ?? '',
                            $row->tipo_venta ?? '',
                            $row->clie_clave ?? '',
                            $row->ban_status ?? '',
                            $row->nota_fecha ?? '',
                            $row->prod_clave ?? '',
                            $row->cdesc_adi ?? '',
                            $row->nota_canti ?? 0,
                            $row->nota_preci ?? 0,
                            $row->subtotal ?? 0,
                            $row->nota_impor ?? 0,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Sincronizar datos a la tabla caché
     */
    public function sync(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        // Si no tiene acceso permitido, verificar si es admin (acceso completo)
        if (!$userFilter['allowed']) {
            $user = Auth::user();
            if (!$user || !$user->hasRole(['super_admin', 'admin'])) {
                return response()->json(['error' => 'No autorizado'], 403);
            }
        }

        $type = $request->input('type', 'lastMonth');

        switch ($type) {
            case 'lastMonth':
                $start = Carbon::parse('first day of previous month')->format('Ymd');
                $end = Carbon::parse('last day of previous month')->format('Ymd');
                break;
            case 'lastDays':
                $days = (int) $request->input('lastDays', 30);
                $end = date('Ymd');
                $start = date('Ymd', strtotime("-{$days} days"));
                break;
            case 'day':
                $day = $request->input('day', date('Y-m-d'));
                $start = str_replace('-', '', $day);
                $end = str_replace('-', '', $day);
                break;
            case 'period':
                $start = str_replace('-', '', $request->input('periodStart'));
                $end = str_replace('-', '', $request->input('periodEnd'));
                break;
            case 'full':
                $start = '20200101';
                $end = date('Ymd');
                break;
            default:
                $start = Carbon::parse('first day of previous month')->format('Ymd');
                $end = Carbon::parse('last day of previous month')->format('Ymd');
        }

        try {
            $tableName = 'redenciones_club_cache';
            $append = $request->input('append', false);
            
            // Si no es append, crear o reemplazar tabla
            if (!$append) {
                DB::statement("DROP TABLE IF EXISTS {$tableName}");
            }
            
            // Verificar si la tabla existe
            $tableExists = DB::getSchemaBuilder()->hasTable($tableName);
            
            if (!$tableExists) {
                DB::statement("CREATE TABLE {$tableName} (
                    id BIGSERIAL PRIMARY KEY,
                    cplaza VARCHAR(50),
                    ctienda VARCHAR(50),
                    cve_con VARCHAR(50),
                    fecha VARCHAR(50),
                    ref_tipo VARCHAR(50),
                    ref_num VARCHAR(50),
                    importe NUMERIC(15,2),
                    ing_egr VARCHAR(50),
                    club_id VARCHAR(100),
                    vend_clave VARCHAR(50),
                    nota_folio VARCHAR(50),
                    cfolio_r VARCHAR(50),
                    tipo_venta VARCHAR(50),
                    clie_clave VARCHAR(50),
                    ban_status VARCHAR(50),
                    nota_fecha DATE,
                    prod_clave VARCHAR(50),
                    cdesc_adi TEXT,
                    nota_canti NUMERIC(15,2),
                    nota_preci NUMERIC(15,2),
                    subtotal NUMERIC(15,2),
                    nota_impor NUMERIC(15,2),
                    ncampo1 VARCHAR(100),
                    sr_recno BIGINT,
                    created_at TIMESTAMP DEFAULT NOW()
                )");
            }

            // Crear índices solo si es una tabla nueva
            if (!$append || !$tableExists) {
                DB::statement("CREATE INDEX IF NOT EXISTS idx_red_club_fecha ON {$tableName}(fecha)");
                DB::statement("CREATE INDEX IF NOT EXISTS idx_red_club_plaza ON {$tableName}(cplaza)");
                DB::statement("CREATE INDEX IF NOT EXISTS idx_red_club_tienda ON {$tableName}(ctienda)");
                DB::statement("CREATE INDEX IF NOT EXISTS idx_red_club_vendedor ON {$tableName}(vend_clave)");
                DB::statement("CREATE INDEX IF NOT EXISTS idx_red_club_ref_num ON {$tableName}(ref_num)");
            }

            // Insertar datos
            $sql = "
                INSERT INTO {$tableName} (
                    cplaza, ctienda, cve_con, fecha, ref_tipo, ref_num, importe, ing_egr,
                    club_id, vend_clave, nota_folio, cfolio_r, tipo_venta, clie_clave,
                    ban_status, nota_fecha, prod_clave, cdesc_adi, nota_canti, nota_preci,
                    subtotal, nota_impor, ncampo1, sr_recno, created_at
                )
                SELECT 
                    notas.cplaza,
                    notas.ctienda,
                    f.cve_con,
                    f.fecha,
                    f.ref_tipo,
                    f.ref_num,
                    f.importe,
                    f.ing_egr,
                    COALESCE(ca.ccampo3, '') as club_id,
                    notas.vend_clave,
                    notas.nota_folio,
                    notas.cfolio_r,
                    CASE WHEN notas.nsaldo > 0 THEN 'credito' ELSE 'contado' END AS tipo_venta,
                    notas.clie_clave,
                    notas.ban_status,
                    notas.nota_fecha::date,
                    notas.prod_clave,
                    notas.cdesc_adi,
                    notas.nota_canti,
                    notas.nota_preci,
                    CASE WHEN notas.nota_preci IS NULL THEN 0 ELSE notas.nota_canti * notas.nota_preci END AS subtotal,
                    notas.nota_impor,
                    notas.ncampo1,
                    notas.sr_recno,
                    NOW()
                FROM (
                    SELECT cplaza, ctienda, cve_con, fecha, ref_tipo, ref_num, importe, ing_egr
                    FROM flujores
                    WHERE fecha >= '{$start}' AND fecha <= '{$end}' AND cve_con = 'PP' AND cborrado <> '1'
                ) f
                LEFT JOIN (
                    SELECT 
                        c.cplaza, c.ctienda, c.nota_folio, c.vend_clave, c.cfolio_r, c.nsaldo, c.clie_clave, 
                        c.ban_status, c.nota_fecha, c.nota_impor,
                        cu.prod_clave, cu.cdesc_adi, cu.nota_canti, cu.nota_preci, cu.sr_recno, cu.ncampo1
                    FROM canota c
                    JOIN cunota cu ON cu.nota_folio = c.nota_folio AND c.cplaza = cu.cplaza AND c.ctienda = cu.ctienda
                    WHERE c.nota_fecha IS NOT NULL
                ) notas ON notas.nota_folio = f.ref_num AND notas.cplaza = f.cplaza AND notas.ctienda = f.ctienda
                LEFT JOIN (
                    SELECT cplaza, ctienda, nota_folio, ccampo3 
                    FROM canotaex
                ) ca ON notas.nota_folio = ca.nota_folio AND notas.cplaza = ca.cplaza AND notas.ctienda = ca.ctienda
                WHERE notas.nota_folio IS NOT NULL
            ";

            DB::statement($sql);

            $count = DB::table($tableName)->count();

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Registros: {$count} (Período: {$start} - {$end})",
            ]);
        } catch (\Exception $e) {
            Log::error('RedencionesClub sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
