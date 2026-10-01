<?php

use App\Models\Computer;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'reportes.archivos-stock.ver', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('reportes.archivos-stock.ver');

    $this->group = Group::factory()->create(['name' => 'GrupoStock', 'type' => 'tienda']);
});

function crearLoteStock(string $sucursal, string $disparador, array $archivos, int $id): void
{
    DB::table('hash_archivos_lotes')->insert([
        'id' => $id,
        'cliente' => 'test',
        'sucursal' => $sucursal,
        'nombre_carpeta' => $sucursal,
        'ruta_base' => 'C:\\stock\\'.$sucursal,
        'fecha_envio' => '2026-10-01 10:00:00',
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
        'created_at' => now(),
        'updated_at' => now(),
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

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.archivos-stock'));

    $response->assertOk();
    $response->assertSee('Reporte de Archivos de Stock');
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reportes.archivos-stock'))->assertForbidden();
    $this->actingAs($user)->get(route('reportes.archivos-stock.data'))->assertForbidden();
});

it('returns one row per agent and file with both rbf and rebsamen data', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rbf', 'PEDIDO.DBF', '1111111111111111', '2026-10-01 09:05:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'PEDIDO.DBF', '1111111111111111', '2026-10-01 09:05:00', '2026-10-01 10:00:00');

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');

    $stock = collect($response->json('data'))->firstWhere('archivo', 'STOCK.DBF');

    expect($stock['plaza'])->toBe('BAJAC')
        ->and($stock['nombre_instalacion'])->toBe('PTULA')
        ->and($stock['estado_equipo'])->toBe('online')
        ->and($stock['rbf']['archivo'])->toBe('STOCK.DBF')
        ->and($stock['rbf']['hash'])->toBe('aabbccddeeff0011')
        ->and($stock['rbf']['hash_corto'])->toBe('f0011')
        ->and($stock['rbf']['fecha_modificacion'])->toBe('2026-10-01 09:00:00')
        ->and($stock['rebsamen']['archivo'])->toBe('STOCK.DBF')
        ->and($stock['rebsamen']['hash'])->toBe('aabbccddeeff0011')
        ->and($stock['estado'])->toBe('actualizado');
});

it('marks the row as desactualizado when the hash differs between rbf and rebsamen', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', '9999888877776666', '2026-10-01 10:17:41', '2026-10-01 10:20:00');

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));

    $row = $response->json('data.0');
    expect($row['estado'])->toBe('desactualizado')
        ->and($row['rbf']['hash'])->toBe('aabbccddeeff0011')
        ->and($row['rebsamen']['hash'])->toBe('9999888877776666')
        ->and($response->json('stock_stats.total_unmatched'))->toBe(1)
        ->and($response->json('stock_stats.total_matched'))->toBe(0);
});

it('marks the row as desactualizado when only one disparador has the file', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'NOHAY.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $row = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('data.0');

    expect($row['estado'])->toBe('desactualizado')
        ->and($row['rbf']['archivo'])->toBe('NOHAY.DBF')
        ->and($row['rbf']['hash'])->toBe('aabbccddeeff0011')
        ->and($row['rebsamen']['archivo'])->toBeNull()
        ->and($row['rebsamen']['hash'])->toBe('');
});

it('normalizes case variants of the same file name', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'PEDIDO1.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rbf', 'pedido1.dbf', 'ffffffffffffffff', '2026-09-01 09:00:00', '2026-09-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'PEDIDO1.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));

    $response->assertJsonCount(1, 'data');
    expect($response->json('data.0.archivo'))->toBe('PEDIDO1.DBF')
        ->and($response->json('data.0.rbf.hash'))->toBe('aabbccddeeff0011')
        ->and($response->json('data.0.estado'))->toBe('actualizado');
});

it('keeps the most recent record when the same file has several entries', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', '1111111111111111', '2026-09-01 09:00:00', '2026-09-01 10:00:00');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', '2222222222222222', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', '2222222222222222', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $row = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('data.0');

    expect($row['rbf']['hash'])->toBe('2222222222222222')
        ->and($row['rbf']['fecha_modificacion'])->toBe('2026-10-01 09:00:00')
        ->and($row['estado'])->toBe('actualizado');
});

it('reads the file weight from the latest lote payload of each disparador', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Requiere PostgreSQL para leer el payload en jsonb.');
    }

    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    // Lote viejo con un peso distinto: no debe ganar sobre el más reciente.
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => 'x', 'Peso' => 999999],
    ], 1);
    crearLoteStock('ptula', 'rbf', [
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => 'aabbccddeeff0011', 'Peso' => 2048],
    ], 2);
    crearLoteStock('ptula', 'rebsa', [
        ['Nombre' => 'STOCK.DBF', 'Existe' => true, 'Md5' => 'aabbccddeeff0011', 'Peso' => 4096],
    ], 3);

    $row = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('data.0');

    expect($row['rbf']['peso'])->toBe(2.0)
        ->and($row['rebsamen']['peso'])->toBe(4.0);
});

it('excludes files that are not part of the stock set', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'VENTA.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'VENTA.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data'))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('ignores records from other disparadores', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'quickbck', 'STOCK.DBF', 'ffffffffffffffff', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'cortefin', 'STOCK.DBF', 'ffffffffffffffff', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $row = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('data.0');

    expect($row['rbf']['hash'])->toBe('aabbccddeeff0011')
        ->and($row['rebsamen']['hash'])->toBe('')
        ->and($row['estado'])->toBe('desactualizado');
});

it('filters by plaza, estado, archivo and search', function () {
    crearComputadorStock('ptula', ['plaza' => 'BAJAC']);
    crearComputadorStock('chetu', ['plaza' => 'CHETU', 'group_id' => Group::factory()->create(['type' => 'almacen'])->id]);
    foreach (['ptula' => 'BAJAC', 'chetu' => 'CHETU'] as $sucursal => $plaza) {
        crearConciliacionStock($sucursal, 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock($sucursal, 'rebsa', 'STOCK.DBF', 'ffffffffffffffff', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock($sucursal, 'rbf', 'PEDIDO.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock($sucursal, 'rebsa', 'PEDIDO.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    }

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['plaza' => ['BAJAC']]))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.plaza', 'BAJAC');

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['search' => 'CHETU']))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.plaza', 'CHETU');

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['estado' => 'actualizado']))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.archivo', 'PEDIDO.DBF');

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['archivo' => ['stock.dbf']]))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.archivo', 'STOCK.DBF');
});

it('paginates and sorts the rows', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rbf', 'PEDIDO.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'PEDIDO.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $asc = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'sort' => 'archivo', 'direction' => 'asc', 'length' => 1, 'start' => 0,
    ]));
    $asc->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.archivo', 'PEDIDO.DBF');
    expect($asc->json('recordsTotal'))->toBe(2);

    $desc = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'sort' => 'archivo', 'direction' => 'desc', 'length' => 1, 'start' => 0,
    ]));
    $desc->assertJsonPath('data.0.archivo', 'STOCK.DBF');
});

it('filters by connection state', function () {
    crearComputadorStock('online1', ['last_seen' => now()->subMinutes(2)]);
    crearComputadorStock('offline1', ['last_seen' => now()->subHours(3)]);
    foreach (['online1', 'offline1'] as $sucursal) {
        crearConciliacionStock($sucursal, 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock($sucursal, 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    }

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['conexion' => 'online']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.short_key', 'online1')
        ->assertJsonPath('data.0.estado_equipo', 'online');

    $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['conexion' => 'offline']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.short_key', 'offline1')
        ->assertJsonPath('data.0.estado_equipo', 'offline');
});

it('groups the statistics per plaza', function () {
    crearComputadorStock('ptula', ['plaza' => 'BAJAC']);
    crearComputadorStock('chetu', ['plaza' => 'CHETU']);
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rbf', 'PEDIDO.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'PEDIDO.DBF', 'ffffffffffffffff', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('chetu', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('chetu', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $stats = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'))->json('stock_stats');

    expect($stats['total_archivos'])->toBe(3)
        ->and($stats['total_matched'])->toBe(2)
        ->and($stats['total_unmatched'])->toBe(1)
        ->and($stats['percent'])->toBe(66.7)
        ->and($stats['per_plaza'])->toHaveCount(2);

    $bajac = collect($stats['per_plaza'])->firstWhere('plaza', 'BAJAC');
    expect($bajac['total'])->toBe(2)
        ->and($bajac['matched'])->toBe(1)
        ->and($bajac['unmatched'])->toBe(1);
});

it('exports a csv with the rbf and rebsamen columns', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'ffffffffffffffff', '2026-10-01 10:17:41', '2026-10-01 10:20:00');

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
        'Estado',
    ]);

    expect($lineas)->toHaveCount(2)
        ->and($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain('aabbccddeeff0011')
        ->and($csv)->toContain('ffffffffffffffff')
        ->and($csv)->toContain('Desactualizado');
});

it('lists agents for the checkbox search filtered by plaza and term', function () {
    crearComputadorStock('ptula', ['plaza' => 'BAJAC', 'nombre_instalacion' => 'AGENTE UNO']);
    crearComputadorStock('chetu', ['plaza' => 'XALAP', 'nombre_instalacion' => 'AGENTE DOS']);

    $todos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.agentes'));
    $todos->assertOk()->assertJsonCount(2, 'data');

    $porPlaza = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.agentes', ['plaza' => ['BAJAC']]));
    $porPlaza->assertOk()->assertJsonCount(1, 'data');
    expect($porPlaza->json('data.0.short_key'))->toBe('ptula');

    $porTexto = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.agentes', ['q' => 'AGENTE DOS']));
    $porTexto->assertOk()->assertJsonCount(1, 'data');
    expect($porTexto->json('data.0.short_key'))->toBe('chetu');
});

it('filters the report to the agents selected with checkboxes', function () {
    $ptula = crearComputadorStock('ptula');
    crearComputadorStock('chetu');

    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('chetu', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('chetu', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $todos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $todos->assertOk();
    expect($todos->json('recordsTotal'))->toBe(2);

    $filtrado = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['agente' => [$ptula->id]]));

    $filtrado->assertOk();
    expect($filtrado->json('recordsTotal'))->toBe(1)
        ->and($filtrado->json('data.0.short_key'))->toBe('ptula');
});

it('filters by several stock files selected at once', function () {
    crearComputadorStock('ptula');
    foreach (['STOCK.DBF', 'PEDIDO.DBF', 'MOVSINV.DBF'] as $i => $archivo) {
        crearConciliacionStock('ptula', 'rbf', $archivo, "hash{$i}0000000000000", '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock('ptula', 'rebsa', $archivo, "hash{$i}0000000000000", '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    }

    $todos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    expect($todos->json('recordsTotal'))->toBe(3);

    $dos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'archivo' => ['STOCK.DBF', 'MOVSINV.DBF'],
    ]));

    $dos->assertOk();
    expect($dos->json('recordsTotal'))->toBe(2)
        ->and(collect($dos->json('data'))->pluck('archivo')->sort()->values()->all())
        ->toBe(['MOVSINV.DBF', 'STOCK.DBF']);
});

it('ignores file filters that are not part of the stock set', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data', [
        'archivo' => ['STOCK.DBF', 'LISTA.DBF'],
    ]));

    $response->assertOk();
    expect($response->json('recordsTotal'))->toBe(1)
        ->and($response->json('data.0.archivo'))->toBe('STOCK.DBF');
});

it('treats an empty file selection as no filter', function () {
    crearComputadorStock('ptula');
    foreach (['STOCK.DBF', 'PEDIDO.DBF'] as $i => $archivo) {
        crearConciliacionStock('ptula', 'rbf', $archivo, "hash{$i}0000000000000", '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock('ptula', 'rebsa', $archivo, "hash{$i}0000000000000", '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    }

    // La vista marca los 14 por defecto; desmarcarlos todos debe seguir
    // mostrando el reporte completo en vez de vaciarlo.
    $sinFiltro = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $ninguno = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['archivo' => []]));

    $ninguno->assertOk();
    expect($ninguno->json('recordsTotal'))->toBe($sinFiltro->json('recordsTotal'))
        ->and($ninguno->json('recordsTotal'))->toBe(2);
});

it('marks the row as vacio when both sides weigh less than 1 kb', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'NOHAY.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'NOHAY.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    // 512 bytes en cada lado: hashes iguales pero el archivo esta vacio.
    crearLoteStock('ptula', 'rbf', [['Nombre' => 'NOHAY.DBF', 'Peso' => 512]], 101);
    crearLoteStock('ptula', 'rebsa', [['Nombre' => 'NOHAY.DBF', 'Peso' => 512]], 102);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));

    $response->assertOk();
    expect($response->json('data.0.estado'))->toBe('vacio')
        ->and($response->json('data.0.rbf.peso'))->toBe(0.5)
        ->and($response->json('stock_stats.total_vacios'))->toBe(1)
        // No es una desincronizacion: no cuenta como desactualizado.
        ->and($response->json('stock_stats.total_unmatched'))->toBe(0);
});

it('keeps desactualizado when only one side weighs less than 1 kb', function () {
    crearComputadorStock('ptula');
    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'ffffffffffffffff', '2026-10-01 10:17:41', '2026-10-01 10:20:00');
    // Solo RBF esta vacio: Rebsamen si tiene contenido, luego es una desincronizacion
    // real y no debe ocultarse detrás de "Archivo vacío".
    crearLoteStock('ptula', 'rbf', [['Nombre' => 'STOCK.DBF', 'Peso' => 512]], 201);
    crearLoteStock('ptula', 'rebsa', [['Nombre' => 'STOCK.DBF', 'Peso' => 10485760]], 202);

    $response = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));

    expect($response->json('data.0.estado'))->toBe('desactualizado')
        ->and($response->json('stock_stats.total_vacios'))->toBe(0)
        ->and($response->json('data.0.rbf.peso_texto'))->toBe('0.5')
        ->and($response->json('data.0.rebsamen.peso_texto'))->toBe('10,240.0');
});

it('filters by the vacio state and formats the weight with thousand separators', function () {
    crearComputadorStock('ptula');
    foreach ([['NOHAY.DBF', 512, 512], ['STOCK.DBF', 15000000, 15000000]] as $i => [$archivo, $pesoR, $pesoRe]) {
        crearConciliacionStock('ptula', 'rbf', $archivo, "hash{$i}0000000000000", '2026-10-01 09:00:00', '2026-10-01 10:00:00');
        crearConciliacionStock('ptula', 'rebsa', $archivo, "hash{$i}0000000000000", '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    }
    crearLoteStock('ptula', 'rbf', [['Nombre' => 'NOHAY.DBF', 'Peso' => 512], ['Nombre' => 'STOCK.DBF', 'Peso' => 15000000]], 301);
    crearLoteStock('ptula', 'rebsa', [['Nombre' => 'NOHAY.DBF', 'Peso' => 512], ['Nombre' => 'STOCK.DBF', 'Peso' => 15000000]], 302);

    $filtrado = $this->actingAs($this->user)
        ->getJson(route('reportes.archivos-stock.data', ['estado' => 'vacio']));

    $filtrado->assertOk();
    expect($filtrado->json('recordsTotal'))->toBe(1)
        ->and($filtrado->json('data.0.archivo'))->toBe('NOHAY.DBF');

    // 15000000 bytes = 14648.4 KB, con separador de miles.
    $todos = $this->actingAs($this->user)->getJson(route('reportes.archivos-stock.data'));
    $stock = collect($todos->json('data'))->firstWhere('archivo', 'STOCK.DBF');

    expect($stock['rbf']['peso_texto'])->toBe('14,648.4')
        ->and($stock['rbf']['peso'])->toBe(14648.4);
});

it('exports only the agents selected with checkboxes', function () {
    $ptula = crearComputadorStock('ptula');
    crearComputadorStock('chetu');

    crearConciliacionStock('ptula', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('ptula', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('chetu', 'rbf', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');
    crearConciliacionStock('chetu', 'rebsa', 'STOCK.DBF', 'aabbccddeeff0011', '2026-10-01 09:00:00', '2026-10-01 10:00:00');

    $csv = $this->actingAs($this->user)
        ->get(route('reportes.archivos-stock.export', ['agente' => [$ptula->id]]))
        ->assertOk()
        ->streamedContent();

    expect($csv)->toContain('PTULA')
        ->and($csv)->not->toContain('CHETU');
});
