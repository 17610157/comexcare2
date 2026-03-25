<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportIntegrationTest extends TestCase
{
    protected $connection;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => '192.168.10.200',
            'database.connections.pgsql.port' => '5432',
            'database.connections.pgsql.database' => 'pgdm-Index',
            'database.connections.pgsql.username' => 'bryan.vazquez',
            'database.connections.pgsql.password' => '3ha]PMJbqK-YnGC&OjAt',
            'database.connections.pgsql.persistent' => false,
        ]);

        $this->connection = DB::connection('pgsql');
    }

    protected function tearDown(): void
    {
        $this->connection->disconnect();
        parent::tearDown();
    }

    public function test_cartera_abonos_cache_exists()
    {
        $result = $this->connection->table('cartera_abonos_cache')->limit(1)->get();
        $this->assertNotNull($result, 'La tabla cartera_abonos_cache debe existir');
    }

    public function test_cartera_abonos_cache_basic_query()
    {
        $result = $this->connection->table('cartera_abonos_cache')
            ->select('plaza', 'tienda', 'fecha', 'nombre', 'monto_fa')
            ->limit(10)
            ->get();

        $this->assertCount(10, $result);
    }

    public function test_cartera_abonos_cache_with_date_filter()
    {
        $result = $this->connection->table('cartera_abonos_cache')
            ->select('plaza', 'tienda', 'fecha', 'monto_fa', 'monto_dv')
            ->whereBetween('fecha', ['2024-01-01', '2024-01-31'])
            ->limit(10)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_cartera_abonos_cache_with_plaza_filter()
    {
        $result = $this->connection->table('cartera_abonos_cache')
            ->select('*')
            ->where('plaza', '01')
            ->limit(5)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_cartera_abonos_cache_pagination()
    {
        $page1 = $this->connection->table('cartera_abonos_cache')
            ->select('*')
            ->limit(10)
            ->offset(0)
            ->get();

        $page2 = $this->connection->table('cartera_abonos_cache')
            ->select('*')
            ->limit(10)
            ->offset(10)
            ->get();

        $this->assertCount(10, $page1);
        $this->assertCount(10, $page2);
    }

    public function test_cartera_abonos_cache_search_by_nombre()
    {
        $result = $this->connection->table('cartera_abonos_cache')
            ->where('nombre', 'ILIKE', '%test%')
            ->limit(5)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_cartera_abonos_cache_search_by_rfc()
    {
        $result = $this->connection->table('cartera_abonos_cache')
            ->where('rfc', 'ILIKE', 'XAXX%')
            ->limit(5)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_cartera_abonos_cache_aggregation()
    {
        $result = $this->connection->table('cartera_abonos_cache')
            ->select([
                'plaza',
                'tienda',
                DB::raw('COUNT(*) as total_registros'),
                DB::raw('SUM(monto_fa) as total_monto_fa'),
                DB::raw('AVG(dias_vencidos) as promedio_dias_vencidos'),
            ])
            ->whereBetween('fecha', ['2024-01-01', '2024-01-31'])
            ->groupBy('plaza', 'tienda')
            ->limit(5)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_metas_cache_exists()
    {
        $result = $this->connection->table('metas_cache')->limit(1)->get();
        $this->assertNotNull($result, 'La tabla metas_cache debe existir');
    }

    public function test_metas_cache_query()
    {
        $result = $this->connection->table('metas_cache')
            ->select('plaza_ajustada', 'tienda_vendedor', 'meta_dia', 'venta')
            ->limit(10)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_vendedores_cache_exists()
    {
        $result = $this->connection->table('vendedores_cache')->limit(1)->get();
        $this->assertNotNull($result, 'La tabla vendedores_cache debe existir');
    }

    public function test_vendedores_cache_query()
    {
        $result = $this->connection->table('vendedores_cache')
            ->select('tienda_vendedor', 'vend_clave', 'venta_total')
            ->limit(10)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_bi_sys_tiendas_exists()
    {
        $result = $this->connection->table('bi_sys_tiendas')->limit(1)->get();
        $this->assertNotNull($result, 'La tabla bi_sys_tiendas debe existir');
    }

    public function test_bi_sys_tiendas_query()
    {
        $result = $this->connection->table('bi_sys_tiendas')
            ->select('id_plaza', 'clave_tienda', 'nombre', 'zona')
            ->limit(10)
            ->get();

        $this->assertIsArray($result->toArray());
    }

    public function test_join_cartera_with_tiendas()
    {
        $result = $this->connection->table('cartera_abonos_cache as cac')
            ->select([
                'cac.plaza',
                'cac.tienda',
                'cac.fecha',
                'cac.monto_fa',
                'bst.nombre as tienda_nombre',
                'bst.zona',
            ])
            ->leftJoin('bi_sys_tiendas as bst', function ($join) {
                $join->on('cac.plaza', '=', 'bst.id_plaza')
                    ->on('cac.tienda', '=', 'bst.clave_tienda');
            })
            ->whereBetween('cac.fecha', ['2024-01-01', '2024-01-31'])
            ->limit(5)
            ->get();

        $this->assertIsArray($result->toArray());
    }
}
