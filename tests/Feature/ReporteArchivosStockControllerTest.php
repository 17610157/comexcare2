<?php

use App\Models\Command;
use App\Models\Computer;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'reportes.archivos-stock.ver', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'reportes.archivos-stock.ejecutar', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('reportes.archivos-stock.ver');

    $this->group = Group::factory()->create(['name' => 'GrupoStock', 'type' => 'tienda']);

    // Las senales de peso se cachean por hora; cada prueba trabaja con su propio juego
    // de respaldos, asi que lo que haya calculado una no puede filtrarse a otra.
    Cache::flush();

    // Las fixtures usan fechas fijas de 2026-10-01 y el semaforo depende de los dias de
    // antiguedad. Congelar el reloj a ese dia las deja en "0 dias", asi que la suite no
    // se rompe cada vez que avanza el calendario.
    $this->travelTo(now()->setDate(2026, 10, 1)->setTime(12, 0, 0));
});

/**
 * Encola un comando como si el agente ya lo hubiera recibido hace $minutos.
 * created_at no es fillable, asi que se fija a mano para simular envios antiguos.
 */
function encolarComandoStock(int $computerId, string $bat = 'DASTOCK.BAT', int $minutos = 0, array $extra = []): Command
{
    $createdAt = $extra['created_at'] ?? now()->subMinutes($minutos);
    unset($extra['created_at'], $extra['updated_at']);

    $command = Command::create(array_merge([
        'computer_id' => $computerId,
        'type' => 'execute',
        'data' => ['command' => $bat, 'command_args' => ''],
        'status' => 'pending',
    ], $extra));

    $command->created_at = $createdAt;
    $command->updated_at = $createdAt;
    $command->save();

    return $command;
}

function crearLoteStock(string $sucursal, string $disparador, array $archivos, int $id, ?string $createdAt = null): void
{
    $createdAt = $createdAt ?: now()->format('Y-m-d H:i:s');

    DB::table('hash_archivos_lotes')->insert([
        'id' => $id,
        'cliente' => 'test',
        'sucursal' => $sucursal,
        'nombre_carpeta' => $sucursal,
        'ruta_base' => 'C:\\stock\\'.$sucursal,
        'fecha_envio' => $createdAt,
        'disparador' => $disparador,
        'num_archivos' => count($archivos),
        'peso_total' => 0,
        'estado' => 'completado',
        'payload' => json_encode(['Tiendas' => [[
            'NombreCarpeta' => $sucursal,
            'RutaBase' => 'C:\\stock\\'.$sucursal,
            'Disparador' => $disparador,
            'Sucursal' => $sucursal,
            'Archivos' => $archivos,
        ]]]),
        'ip' => '10.0.0.1',
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

function crearConciliacionStock(string $sucursal, string $disparador, string $archivo, string $md5, string $fechaMod, string $fechaApi): void
{
    DB::table('conciliacion_hash_archivos')->insert([
        'sucursal' => $sucursal,
        'archivo' => $archivo,
        'md5' => substr($md5, -5),
        'md5_completo' => $md5,
        'fecha_modificacion' => $fechaMod,
        'disparador' => $disparador,
        'fecha_consulta_api' => $fechaApi,
        'ip' => '10.0.0.1',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function crearComputadorStock(string $shortKey, array $overrides = []): Computer
{
    $defaults = [
        'computer_name' => strtoupper($shortKey),
        'nombre_instalacion' => strtoupper($shortKey),
        'short_key' => $shortKey,
        'plaza' => 'BAJAC',
        'group_id' => test()->group->id,
        'agent_config' => ['dbf_files' => []],
        'last_seen' => now()->subMinutes(2),
    ];

    return Computer::factory()->create(array_merge($defaults, $overrides));
}

/** Registro hoy en RBF y Rebsamen, para que el archivo con fecha quede en verde. */
function crearFechaAlDia(string $sucursal, string $archivo, string $hash): void
{
    $fecha = now()->format('Y-m-d H:i:s');
    crearConciliacionStock($sucursal, 'rbf', $archivo, $hash, $fecha, $fecha);
    crearConciliacionStock($sucursal, 'rebsa', $archivo, $hash, $fecha, $fecha);
}

/** Los cinco archivos con fecha presentes y en verde. */
function instalarAlDia(string $sucursal): void
{
    foreach (['CAT_PROD.DBF', 'DD_CONTROL.DBF', 'DD_DATOS.DBF', 'MOVSINV.DBF', 'PEDIDO.DBF'] as $i => $archivo) {
        crearFechaAlDia($sucursal, $archivo, 'hash'.str_pad((string) $i, 16, '0'));
    }
}

function crearFecha(string $sucursal, string $archivo, string $hash, int $dias): void
{
    $fecha = now()->subDays($dias)->format('Y-m-d H:i:s');
    crearConciliacionStock($sucursal, 'rbf', $archivo, $hash, $fecha, $fecha);
    crearConciliacionStock($sucursal, 'rebsa', $archivo, $hash, $fecha, $fecha);
}

/** Un lote minimo en RBF para que la sucursal cuente como instalacion que reporto. */
function loteMinimo(string $sucursal, int $id = 1): void
{
    crearLoteStock($sucursal, 'rbf', [
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => md5('STOCK'), 'Peso' => 102400],
    ], $id);
}

function filaDe($response, string $archivo): ?array
{
    return collect($response->json('data'))->firstWhere('archivo', $archivo);
}

/** Reemplaza el registro de un archivo por uno de $dias de antiguedad. */
function recrearFecha(string $sucursal, string $archivo, string $hash, int $dias): void
{
    DB::table('conciliacion_hash_archivos')->where('sucursal', $sucursal)->where('archivo', $archivo)->delete();
    crearFecha($sucursal, $archivo, $hash, $dias);
}

/** Todos los archivos presentes y al dia, para hablar de una bandeja "todo verde". */
function instalarBandeja(string $sucursal): void
{
    instalarAlDia($sucursal);

    foreach (['STOCK.DBF', 'CATPROD3.DBF', 'NOHAY.DBF', 'PEDIDO1.DBF', 'PEDIDO2.DBF',
        'EYSIPAR.DBF', 'PROVPROD.DBF', 'TABLACON.DBF', 'TABLA010.DBF'] as $i => $archivo) {
        crearFechaAlDia($sucursal, $archivo, 'hh'.str_pad((string) $i, 16, '0'));
    }
}

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.archivos-stock'));

    $response->assertOk();
    $response->assertSee('Reporte de Archivos de Stock');
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reportes.archivos-stock'))->assertForbidden();
    $this->actingAs($user)->get(route('reportes.archivos-stock.data'))->assertForbidden();
    $this->actingAs($user)->get(route('reportes.archivos-stock.bitacora'))->assertForbidden();
    $this->actingAs($user)->post(route('reportes.archivos-stock.ejecutar'))->assertForbidden();
});

it('builds one row per agent and file, with both rbf and rebsamen data', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]));

    $response->assertOk();
    expect($response->json('recordsTotal'))->toBe(14)
        ->and($response->json('data'))->toHaveCount(14);

    $stock = filaDe($response, 'STOCK.DBF');

    expect($stock['plaza'])->toBe('BAJAC')
        ->and($stock['nombre_instalacion'])->toBe('PTULA')
        ->and($stock['estado_equipo'])->toBe('online')
        ->and($stock['rbf']['archivo'])->toBe('STOCK.DBF')
        ->and($stock['rbf']['hash'])->toBe('aabbccddeeff0011')
        ->and($stock['rbf']['hash_corto'])->toBe('f0011')
        ->and($stock['rebsamen']['hash'])->toBe('aabbccddeeff0011')
        ->and($stock['tipo_archivo'])->toBe('existencia')
        ->and(filaDe($response, 'TABLA010.DBF')['tipo_archivo'])->toBe('consulta')
        ->and(filaDe($response, 'PEDIDO.DBF')['tipo_archivo'])->toBe('fecha');
});

it('marks a green date file with zero or one day and yellow between two and three', function () {
    foreach ([['v0', 0], ['v1', 1], ['a2', 2], ['a3', 3], ['r4', 4]] as $i => [$sk, $dias]) {
        crearComputadorStock($sk);
        crearFecha($sk, 'CAT_PROD.DBF', 'h'.str_pad((string) $dias, 16, '0'), $dias);
        loteMinimo($sk, 100 + $i);
    }

    $data = collect($this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]))->json('data'))
        ->filter(fn ($r) => $r['archivo'] === 'CAT_PROD.DBF')
        ->keyBy('short_key');

    expect($data['v0']['estado'])->toBe('verde')
        ->and($data['v0']['dias'])->toBe(0)
        ->and($data['v1']['estado'])->toBe('verde')
        ->and($data['a2']['estado'])->toBe('amarillo')
        ->and($data['a2']['dias'])->toBe(2)
        ->and($data['a3']['estado'])->toBe('amarillo')
        ->and($data['r4']['estado'])->toBe('rojo')
        ->and($data['r4']['dias'])->toBe(4);
});

it('gives PEDIDO a wider range: green up to three days and yellow up to seven', function () {
    foreach ([['v3', 3], ['a7', 7], ['r8', 8]] as $i => [$sk, $dias]) {
        crearComputadorStock($sk);
        crearFecha($sk, 'PEDIDO.DBF', 'h'.str_pad((string) $dias, 16, '0'), $dias);
        loteMinimo($sk, 200 + $i);
    }

    $data = collect($this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]))->json('data'))
        ->filter(fn ($r) => $r['archivo'] === 'PEDIDO.DBF')
        ->keyBy('short_key');

    expect($data['v3']['estado'])->toBe('verde')
        ->and($data['a7']['estado'])->toBe('amarillo')
        ->and($data['r8']['estado'])->toBe('rojo');
});

it('marks a date file as red when it does not exist or has no modification date', function () {
    $ptula = crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'MOVSINV.DBF', 'aabbccddeeff0011', '', now()->format('Y-m-d H:i:s'));
    crearConciliacionStock('ptula', 'rebsa', 'MOVSINV.DBF', 'aabbccddeeff0011', '', now()->format('Y-m-d H:i:s'));

    $data = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]))->json('data');

    $movsinv = collect($data)->firstWhere('archivo', 'MOVSINV.DBF');
    $catProd = collect($data)->firstWhere('archivo', 'CAT_PROD.DBF');

    // MOVSINV existe pero sin fecha; CAT_PROD ni siquiera existe.
    expect($movsinv['existe'])->toBeTrue()
        ->and($movsinv['dias'])->toBeNull()
        ->and($movsinv['estado'])->toBe('rojo')
        ->and($catProd['existe'])->toBeFalse()
        ->and($catProd['estado'])->toBe('rojo');
});

it('treats the existence-only files by presence and ignores their days', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 30);
    crearFecha('ptula', 'PEDIDO1.DBF', 'aabbccddeeff0022', 0);

    $data = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]))->json('data');

    // STOCK lleva 30 dias sin tocarse pero existe: para un archivo de solo existencia
    // eso es suficiente y sigue en verde.
    expect(collect($data)->firstWhere('archivo', 'STOCK.DBF')['estado'])->toBe('verde')
        ->and(collect($data)->firstWhere('archivo', 'PEDIDO1.DBF')['estado'])->toBe('verde')
        ->and(collect($data)->firstWhere('archivo', 'CATPROD3.DBF')['existe'])->toBeFalse()
        ->and(collect($data)->firstWhere('archivo', 'CATPROD3.DBF')['estado'])->toBe('rojo');
});

it('marks EYSIPAR as not applicable for storage rooms but green for stores', function () {
    $almacen = Group::factory()->create(['type' => 'almacen']);

    crearComputadorStock('ptula');
    crearComputadorStock('bodega', ['group_id' => $almacen->id]);
    crearFecha('ptula', 'EYSIPAR.DBF', 'aaaabbbbccccdddd', 0);
    loteMinimo('ptula', 1);
    loteMinimo('bodega', 2);

    $data = collect($this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]))->json('data'))
        ->filter(fn ($r) => $r['archivo'] === 'EYSIPAR.DBF')
        ->keyBy('short_key');

    expect($data['ptula']['estado'])->toBe('verde')
        ->and($data['bodega']['es_almacen'])->toBeTrue()
        ->and($data['bodega']['estado'])->toBe('no_aplica');
});

it('marks TABLA010 as not counted for every agent', function () {
    crearComputadorStock('ptula');

    $row = filaDe($this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100])), 'TABLA010.DBF');

    expect($row['estado'])->toBe('no_cuenta')
        ->and($row['existe'])->toBeFalse();
});

it('considers the point covered when PROVPROD or TABLACON exists', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'qbck', 'PROVPROD.DBF', 'aabbccddeeff0011', '', now()->format('Y-m-d H:i:s'));

    $data = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]))->json('data');

    expect(collect($data)->firstWhere('archivo', 'PROVPROD.DBF')['estado'])->toBe('verde')
        ->and(collect($data)->firstWhere('archivo', 'TABLACON.DBF')['estado'])->toBe('verde')
        // Sin ninguno de los dos, el par queda pendiente en rojo.
        ->and(collect($data)->firstWhere('archivo', 'NOHAY.DBF')['estado'])->toBe('rojo');
});

it('filters by estado with the new semaphore values', function () {
    $ptula = crearComputadorStock('ptula');
    crearFecha('ptula', 'CAT_PROD.DBF', 'aabbccddeeff0011', 0);
    crearFecha('ptula', 'DD_CONTROL.DBF', 'aabbccddeeff0022', 40);
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0033', 0);
    loteMinimo('ptula', 1);

    $verdes = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['estado' => 'verde', 'length' => 100]));
    $verdes->assertOk();
    expect($verdes->json('recordsTotal'))->toBe(6)
        ->and(collect($verdes->json('data'))->every(fn ($r) => $r['estado'] === 'verde'))->toBeTrue();

    $rojos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['estado' => 'rojo', 'length' => 100]));
    expect(collect($rojos->json('data'))->every(fn ($r) => $r['estado'] === 'rojo'))->toBeTrue()
        ->and(collect($rojos->json('data'))->pluck('archivo'))->toContain('DD_CONTROL.DBF');

    $noCuenta = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['estado' => 'no_cuenta', 'length' => 100]));
    expect($noCuenta->json('recordsTotal'))->toBe(1)
        ->and($noCuenta->json('data.0.archivo'))->toBe('TABLA010.DBF');
});

it('keeps the installation percentage when a file filter is applied', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    $todos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['length' => 100]));
    $soloStock = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', ['archivo' => ['STOCK.DBF'], 'length' => 100]));

    expect($soloStock->json('recordsTotal'))->toBe(1)
        ->and($soloStock->json('data.0.archivo'))->toBe('STOCK.DBF')
        ->and($soloStock->json('instalaciones_stats.percent'))->toBe($todos->json('instalaciones_stats.percent'))
        ->and($soloStock->json('instalaciones_stats.listas'))->toBe($todos->json('instalaciones_stats.listas'));
});

it('counts a contemplated installation as ready when its date files are green', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    $stats = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('instalaciones_stats');

    expect($stats['total_agentes'])->toBe(1)
        ->and($stats['total_instalaciones'])->toBe(1)
        ->and($stats['listas'])->toBe(1)
        ->and($stats['pendientes'])->toBe(0)
        ->and($stats['no_contempladas'])->toBe(0)
        ->and($stats['percent'])->toBe(100.0)
        ->and($stats['causas'])->toBe([])
        ->and($stats['per_plaza'])->toHaveCount(1)
        ->and($stats['per_plaza'][0])->toMatchArray(['plaza' => 'BAJAC', 'total' => 1, 'listas' => 1, 'pendientes' => 0, 'percent' => 100.0]);
});

it('leaves an installation pending when one date file turns yellow', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    crearFecha('ptula', 'DD_CONTROL.DBF', 'aabbccddeeff0022', 5);
    loteMinimo('ptula', 1);

    $stats = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('instalaciones_stats');

    expect($stats['total_instalaciones'])->toBe(1)
        ->and($stats['listas'])->toBe(0)
        ->and($stats['pendientes'])->toBe(1)
        ->and($stats['percent'])->toBe(0.0)
        ->and(collect($stats['causas'])->pluck('codigo')->all())->toBe(['DD_CONTROL.DBF']);
});

it('leaves an installation pending when a date file is missing', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    // MOVSINV nunca se registro.
    DB::table('conciliacion_hash_archivos')->where('archivo', 'MOVSINV.DBF')->delete();
    loteMinimo('ptula', 1);

    $stats = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('instalaciones_stats');

    expect($stats['listas'])->toBe(0)
        ->and($stats['pendientes'])->toBe(1)
        ->and(collect($stats['causas'])->pluck('codigo')->all())->toContain('MOVSINV.DBF');
});

it('excludes groups that are not stores or storage rooms from the denominator', function () {
    $vendedor = Group::factory()->create(['type' => 'vendedor']);

    crearComputadorStock('ptula');
    crearComputadorStock('vend1', ['group_id' => $vendedor->id]);
    crearComputadorStock('sinlote', ['plaza' => 'XALAP']);
    instalarAlDia('ptula');
    instalarAlDia('vend1');
    instalarAlDia('sinlote');
    loteMinimo('ptula', 1);
    loteMinimo('vend1', 2);

    $stats = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('instalaciones_stats');

    expect($stats['total_agentes'])->toBe(3)
        ->and($stats['total_instalaciones'])->toBe(1)
        ->and($stats['no_contempladas'])->toBe(2)
        ->and($stats['listas'])->toBe(1);
});

it('groups the installation statistics per plaza', function () {
    crearComputadorStock('ptula', ['plaza' => 'BAJAC']);
    crearComputadorStock('chetu', ['plaza' => 'CHETU']);
    instalarAlDia('ptula');
    instalarAlDia('chetu');
    crearFecha('chetu', 'PEDIDO.DBF', 'aabbccddeeff0099', 9);
    loteMinimo('ptula', 1);
    loteMinimo('chetu', 2);

    $stats = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('instalaciones_stats');

    expect($stats['listas'])->toBe(1)
        ->and($stats['pendientes'])->toBe(1)
        ->and($stats['per_plaza'])->toHaveCount(2);

    $bajac = collect($stats['per_plaza'])->firstWhere('plaza', 'BAJAC');
    $chetu = collect($stats['per_plaza'])->firstWhere('plaza', 'CHETU');

    expect($bajac)->toMatchArray(['total' => 1, 'listas' => 1, 'pendientes' => 0, 'percent' => 100.0])
        ->and($chetu)->toMatchArray(['total' => 1, 'listas' => 0, 'pendientes' => 1, 'percent' => 0.0]);
});

it('blocks an installation with a peso anomaly on one of its date files', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    // CAT_PROD pesa 1300KB ocho dias y hoy salta a 1690KB (+30%).
    $id = 1000;
    for ($atras = 8; $atras >= 1; $atras--) {
        $fecha = now()->subDays($atras)->format('Y-m-d H:i:s');
        crearLoteStock('ptula', 'rbf', [
            ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c'.$fecha), 'Peso' => 1331200],
        ], $id++, $fecha);
    }
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c hoy'), 'Peso' => 1730560],
    ], $id++);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $catProd = filaDe($response, 'CAT_PROD.DBF');
    $stats = $response->json('instalaciones_stats');

    expect($catProd['peso_senal']['tipo'])->toBe('anomalia')
        ->and($catProd['peso_senal']['bloquea'])->toBeTrue()
        ->and($stats['pendientes'])->toBe(1)
        ->and($stats['listas'])->toBe(0)
        ->and(collect($stats['causas'])->pluck('codigo')->all())->toContain('PESO');
});

it('does not block with a peso anomaly on an existence-only file', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    // STOCK es de sola existencia: aunque su peso salte, no deja la instalacion pendiente.
    $id = 2000;
    for ($atras = 8; $atras >= 1; $atras--) {
        $fecha = now()->subDays($atras)->format('Y-m-d H:i:s');
        crearLoteStock('ptula', 'rbf', [
            ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => md5('s'.$fecha), 'Peso' => 1331200],
        ], $id++, $fecha);
    }
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => md5('s hoy'), 'Peso' => 1730560],
    ], $id++);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $stock = filaDe($response, 'STOCK.DBF');
    $stats = $response->json('instalaciones_stats');

    expect($stock['peso_senal']['tipo'])->toBe('anomalia')
        ->and($stock['peso_senal']['bloquea'])->toBeFalse()
        ->and($stats['listas'])->toBe(1)
        ->and(collect($stats['causas'])->pluck('codigo'))->not->toContain('PESO');
});

it('shows the unchanged-weight signal without blocking the installation', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    $id = 3000;
    for ($atras = 8; $atras >= 0; $atras--) {
        $fecha = now()->subDays($atras)->format('Y-m-d H:i:s');
        crearLoteStock('ptula', 'rbf', [
            ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c'.$fecha), 'Peso' => 512000],
        ], $id++, $fecha);
    }

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $catProd = filaDe($response, 'CAT_PROD.DBF');

    expect($catProd['peso_senal']['tipo'])->toBe('sin_cambios')
        ->and($catProd['peso_senal']['bloquea'])->toBeFalse();
});

it('flags a file that disappears from the latest backup as critical', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'CAT_PROD.DBF', 'aabbccddeeff0011', 0);
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0099', 0);

    $id = 4000;
    for ($atras = 8; $atras >= 1; $atras--) {
        $fecha = now()->subDays($atras)->format('Y-m-d H:i:s');
        crearLoteStock('ptula', 'rbf', [
            ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c'.$fecha), 'Peso' => 1331200],
        ], $id++, $fecha);
    }
    // El respaldo de hoy ya no trae CAT_PROD.
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => md5('s hoy'), 'Peso' => 102400],
    ], $id++);

    $catProd = filaDe($this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data')), 'CAT_PROD.DBF');

    expect($catProd['peso_senal']['tipo'])->toBe('desaparecido')
        ->and($catProd['peso_senal']['bloquea'])->toBeTrue();
});

it('annotates a file that appears for the first time without counting it as an error', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0099', 0);

    $fechas = [];
    for ($atras = 8; $atras >= 1; $atras--) {
        $fechas[] = now()->subDays($atras)->format('Y-m-d H:i:s');
    }
    foreach ($fechas as $i => $fecha) {
        crearLoteStock('ptula', 'rbf', [
            ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => md5('s'.$fecha), 'Peso' => 102400],
        ], 5000 + $i, $fecha);
    }
    // CAT_PROD aparece por primera vez en el respaldo de hoy.
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c hoy'), 'Peso' => 1331200],
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => md5('s hoy'), 'Peso' => 102400],
    ], 5009);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $catProd = filaDe($response, 'CAT_PROD.DBF');

    expect($catProd['peso_senal']['tipo'])->toBe('nuevo')
        ->and($catProd['peso_senal']['bloquea'])->toBeFalse();
});

it('remembers a file that returned to its normal weight the day after a spike', function () {
    crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    // Un dia raro CAT_PROD pesa el doble; al dia siguiente vuelve a 1300KB. Como la
    // mediana de la semana es 1300KB, el cambio de hoy no cumple la condicion y no se
    // senala ninguna anomalia.
    $fechas = [];
    for ($atras = 8; $atras >= 1; $atras--) {
        $fechas[] = now()->subDays($atras)->format('Y-m-d H:i:s');
    }
    foreach ($fechas as $i => $fecha) {
        $peso = $i === 1 ? 2662400 : 1331200;
        crearLoteStock('ptula', 'rbf', [
            ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c'.$fecha), 'Peso' => $peso],
        ], 6000 + $i);
    }
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'CAT_PROD.DBF', 'Existe' => true, 'Md5' => md5('c hoy'), 'Peso' => 1331200],
    ], 6009);

    $catProd = filaDe($this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data')), 'CAT_PROD.DBF');

    expect($catProd['peso_senal'])->toBeNull();
});

it('paginates and sorts the rows', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);
    crearFecha('ptula', 'PEDIDO.DBF', 'aabbccddeeff0022', 5);
    crearFecha('ptula', 'MOVSINV.DBF', 'aabbccddeeff0033', 9);

    $asc = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'sort' => 'archivo', 'direction' => 'asc', 'length' => 1, 'start' => 0,
    ]));
    $asc->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.archivo', 'CAT_PROD.DBF');
    expect($asc->json('recordsTotal'))->toBe(14);

    $papel = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'sort' => 'estado', 'direction' => 'asc', 'length' => 2, 'start' => 0,
    ]));
    // verde(0) y amarillo(1) van primero.
    $papel->assertOk();
    expect($papel->json('data.0.estado'))->toBe('verde')
        ->and($papel->json('data.1.estado'))->toBe('amarillo');
});

it('filters by plaza, search and connection state', function () {
    crearComputadorStock('ptula', ['plaza' => 'BAJAC', 'nombre_instalacion' => 'AGENTE UNO']);
    crearComputadorStock('chetu', ['plaza' => 'CHETU', 'nombre_instalacion' => 'OTRO AGENTE', 'last_seen' => now()->subHours(3)]);
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['plaza' => ['BAJAC'], 'length' => 100]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 14);

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['search' => 'OTRO AGENTE', 'length' => 100]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 14)
        ->assertJsonPath('data.0.short_key', 'chetu');

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['conexion' => 'offline', 'length' => 100]))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 14)
        ->assertJsonPath('data.0.short_key', 'chetu');
});

it('filters the report to the agents selected with checkboxes', function () {
    $ptula = crearComputadorStock('ptula');
    crearComputadorStock('chetu');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);
    crearFecha('chetu', 'STOCK.DBF', 'aabbccddeeff0011', 0);

    $filtrado = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['agente' => [$ptula->id], 'length' => 100]));

    $filtrado->assertOk();
    expect($filtrado->json('recordsTotal'))->toBe(14)
        ->and($filtrado->json('data.0.short_key'))->toBe('ptula');
});

it('filters by several stock files selected at once', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aa01', 0);
    crearFecha('ptula', 'PEDIDO.DBF', 'aa02', 0);
    crearFecha('ptula', 'MOVSINV.DBF', 'aa03', 0);

    $dos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'archivo' => ['STOCK.DBF', 'MOVSINV.DBF'],
    ]));

    $dos->assertOk();
    expect($dos->json('recordsTotal'))->toBe(2)
        ->and(collect($dos->json('data'))->pluck('archivo')->sort()->values()->all())
        ->toBe(['MOVSINV.DBF', 'STOCK.DBF']);
});

it('filters by the minimum and maximum days range', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aa01', 3);
    crearFecha('ptula', 'PEDIDO.DBF', 'aa02', 10);
    crearFecha('ptula', 'MOVSINV.DBF', 'aa03', 45);

    $desde10 = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['dias_min' => 10]));
    $desde10->assertOk();
    expect(collect($desde10->json('data'))->pluck('archivo')->sort()->values()->all())
        ->toBe(['MOVSINV.DBF', 'PEDIDO.DBF']);

    $hasta10 = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['dias_max' => 10]));
    $hasta10->assertOk();
    expect(collect($hasta10->json('data'))->pluck('archivo')->sort()->values()->all())
        ->toBe(['CATPROD3.DBF', 'CAT_PROD.DBF', 'DD_CONTROL.DBF', 'DD_DATOS.DBF', 'EYSIPAR.DBF',
            'NOHAY.DBF', 'PEDIDO.DBF', 'PEDIDO1.DBF', 'PEDIDO2.DBF', 'PROVPROD.DBF',
            'STOCK.DBF', 'TABLACON.DBF']);
});

it('excludes rows without a known date when a days range is applied', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aa01', 30);
    crearFecha('ptula', 'PEDIDO.DBF', 'aa02', 0);

    $filtrado = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['dias_min' => 1]));

    $filtrado->assertOk();
    expect($filtrado->json('recordsTotal'))->toBe(1)
        ->and(collect($filtrado->json('data'))->pluck('archivo')->all())->toContain('STOCK.DBF')
        ->not->toContain('PEDIDO.DBF')
        ->not->toContain('TABLA010.DBF');
});

it('renders the stock button, the state filter and the new statistics ids', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.archivos-stock'));

    $response->assertOk();
    $response->assertSee('id="btn_run_stock"', false)
        ->assertSee('id="btn_bitacora"', false)
        ->assertSee('id="dias_min"', false)
        ->assertSee('id="dias_max"', false)
        ->assertSee('data-sort="dias"', false)
        ->assertSee('id="select_all_computers"', false)
        ->assertSee('id="statContempladas"', false)
        ->assertSee('id="statListas"', false)
        ->assertSee('id="statPendientes"', false)
        ->assertSee('value="amarillo"', false)
        ->assertSee('value="no_cuenta"', false)
        ->assertSee('value="no_aplica"', false);
});

it('exports a csv with the new estado labels and the weight signal column', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);
    loteMinimo('ptula', 1);

    $response = $this->actingAs($this->user)->get(route('reportes.archivos-stock.export'));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))
        ->toMatch('/^attachment; filename="Reporte_Archivos_Stock_\d{8}_\d{6}\.csv"$/');

    $csv = $response->streamedContent();
    $lineas = array_values(array_filter(explode("\n", $csv)));
    $header = str_getcsv(ltrim($lineas[0], "\xEF\xBB\xBF"), ';');

    expect($header)->toBe([
        'Plaza', 'Agente', 'Estado Equipo',
        'Archivo RBF', 'Hash RBF', 'Fecha Mod RBF', 'Peso RBF (KB)',
        'Archivo Rebsamen', 'Hash Rebsamen', 'Fecha Mod Rebsamen', 'Peso Rebsamen (KB)',
        'Estado', 'Días', 'Señal de peso',
    ]);

    expect($lineas)->toHaveCount(15)
        ->and($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain('aabbccddeeff0011')
        ->and($csv)->toContain(';Verde;');
});

it('exports the rows with the state and the weight signal of each file', function () {
    crearComputadorStock('ptula');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);
    loteMinimo('ptula', 1);

    $csv = $this->actingAs($this->user)->get(route('reportes.archivos-stock.export'))->streamedContent();
    $lineas = array_values(array_filter(explode("\n", $csv)));

    // La fila del STOCK en verde tiene "Verde" y sin senal; en la de TABLA010 "No cuenta".
    expect($lineas)->toHaveCount(15)
        ->and($csv)->toContain('Verde')
        ->and($csv)->toContain('No cuenta');
});

it('exports only the agents selected with checkboxes', function () {
    $ptula = crearComputadorStock('ptula');
    crearComputadorStock('chetu');
    crearFecha('ptula', 'STOCK.DBF', 'aabbccddeeff0011', 0);
    crearFecha('chetu', 'STOCK.DBF', 'aabbccddeeff0011', 0);

    $csv = $this->actingAs($this->user)
        ->get(route('reportes.archivos-stock.export', ['agente' => [$ptula->id]]))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('PTULA')
        ->and($csv)->not->toContain('CHETU');
});

it('returns 400 when no computer is selected for the stock command', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');

    $this->actingAs($this->user)
        ->postJson(route('reportes.archivos-stock.ejecutar'))
        ->assertStatus(400)
        ->assertJsonPath('success', false);
});

it('creates a single DASTOCK command per computer with a yellow or red file', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    instalarAlDia('ptula');
    crearFecha('ptula', 'CAT_PROD.DBF', 'aabbccddeeff0011', 9);
    loteMinimo('ptula', 1);

    $response = $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
    ]);

    $response->assertOk()->assertJsonPath('success', true)->assertJsonPath('bat', 'DASTOCK.BAT');
    expect($response->json('computers.0.archivos'))->toBe(1);

    $commands = Command::where('type', 'execute')->get();
    expect($commands)->toHaveCount(1)
        ->and($commands->first()->computer_id)->toBe($ptula->id)
        ->and($commands->first()->data['command'])->toBe('DASTOCK.BAT')
        ->and($commands->first()->data['command_args'])->toBe('');
});

it('does not send the stock command when every date file is green', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    instalarAlDia('ptula');
    loteMinimo('ptula', 1);

    $response = $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
    ]);

    $response->assertOk()->assertJsonPath('count', 0);
    expect(Command::where('type', 'execute')->count())->toBe(0);
});

it('previews the computers with pending files without creating commands', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    instalarAlDia('ptula');
    crearFecha('ptula', 'PEDIDO.DBF', 'aabbccddeeff0011', 4);
    crearFecha('ptula', 'DD_CONTROL.DBF', 'aabbccddeeff0022', 4);
    loteMinimo('ptula', 1);

    $preview = $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
        'preview' => true,
    ]);

    $preview->assertOk()->assertJsonPath('count', 1)->assertJsonPath('bat', 'DASTOCK.BAT');
    expect($preview->json('computers.0.archivos'))->toBe(2)
        ->and($preview->json('computers.0.plaza'))->toBe('BAJAC')
        ->and(Command::where('type', 'execute')->count())->toBe(0);
});

it('does not resend the command to the same computer within five minutes', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    instalarAlDia('ptula');
    crearFecha('ptula', 'PEDIDO.DBF', 'aabbccddeeff0011', 9);
    loteMinimo('ptula', 1);
    encolarComandoStock($ptula->id, 'DASTOCK.BAT', 2);

    $preview = $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
        'preview' => true,
    ]);
    $preview->assertOk()->assertJsonPath('count', 0);

    $response = $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
    ]);

    $response->assertOk()->assertJsonPath('count', 0)->assertJsonPath('en_espera', 1);
    expect(Command::where('type', 'execute')->count())->toBe(1);
});

it('allows the command again once the five minutes have passed', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    instalarAlDia('ptula');
    crearFecha('ptula', 'PEDIDO.DBF', 'aabbccddeeff0011', 9);
    loteMinimo('ptula', 1);
    encolarComandoStock($ptula->id, 'DASTOCK.BAT', 6);

    $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
    ])->assertOk()->assertJsonPath('count', 1);

    expect(Command::where('type', 'execute')->count())->toBe(2);
});

it('ignores commands of other bats when applying the cooldown', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    instalarAlDia('ptula');
    crearFecha('ptula', 'PEDIDO.DBF', 'aabbccddeeff0011', 9);
    loteMinimo('ptula', 1);
    encolarComandoStock($ptula->id, 'DALISTA.BAT', 1);

    $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id],
    ])->assertOk()->assertJsonPath('count', 1);
});

it('only applies the cooldown to the computers that were sent', function () {
    $this->user->givePermissionTo('reportes.archivos-stock.ejecutar');
    $ptula = crearComputadorStock('ptula');
    $chetu = crearComputadorStock('chetu');
    instalarAlDia('ptula');
    instalarAlDia('chetu');
    crearFecha('ptula', 'PEDIDO.DBF', 'aabbccddeeff0011', 9);
    crearFecha('chetu', 'PEDIDO.DBF', 'aabbccddeeff0011', 9);
    loteMinimo('ptula', 1);
    loteMinimo('chetu', 2);
    encolarComandoStock($ptula->id, 'DASTOCK.BAT', 1);

    $response = $this->actingAs($this->user)->postJson(route('reportes.archivos-stock.ejecutar'), [
        'computer_ids' => [$ptula->id, $chetu->id],
    ]);

    $response->assertOk()->assertJsonPath('count', 1)->assertJsonPath('en_espera', 1);
    expect($response->json('computer_ids'))->toBe([$chetu->id])
        ->and(Command::where('type', 'execute')->count())->toBe(2);
});

it('groups the stock bitacora by dispatch time', function () {
    $ptula = crearComputadorStock('ptula');
    $cuando = now()->startOfHour();

    encolarComandoStock($ptula->id, 'DASTOCK.BAT', 0, [
        'status' => 'failed',
        'response' => "[ERROR]\n####-#O#-#\nNo se encontro la carpeta de stock\n[EXIT_CODE]1",
        'created_at' => $cuando,
    ]);
    encolarComandoStock($ptula->id, 'DASTOCK.BAT', 0, [
        'status' => 'completed',
        'created_at' => $cuando,
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.bitacora'));

    $response->assertOk()->assertJsonPath('success', true)->assertJsonCount(1, 'groups');
    expect($response->json('groups.0.created_at'))->toBe($cuando->format('Y-m-d H:i:s'))
        ->and($response->json('groups.0.total'))->toBe(2)
        ->and($response->json('groups.0.items.0.computer'))->toBe('PTULA')
        ->and($response->json('groups.0.items.0.plaza'))->toBe('BAJAC')
        ->and($response->json('groups.0.items.0.label'))->toBe('STOCK')
        ->and($response->json('groups.0.items.0.error'))->toBe('No se encontro la carpeta de stock');
});

it('keeps the commands of other bats out of the stock bitacora', function () {
    $ptula = crearComputadorStock('ptula');
    encolarComandoStock($ptula->id, 'DALISTA.BAT', 1);
    encolarComandoStock($ptula->id, 'DASTOCK.BAT', 1);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.bitacora'));

    $response->assertOk()->assertJsonCount(1, 'groups');
    expect($response->json('groups.0.total'))->toBe(1)
        ->and($response->json('groups.0.items.0.bat'))->toBe('DASTOCK.BAT');
});

it('returns an empty bitacora when nothing was ever executed', function () {
    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.bitacora'))
        ->assertOk()
        ->assertJsonPath('groups', []);
});
