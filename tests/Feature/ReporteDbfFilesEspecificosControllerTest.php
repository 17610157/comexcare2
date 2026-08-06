<?php

use App\Models\Command;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Group;
use App\Models\RbfFileHash;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'dbf-files-especificos.ver', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('dbf-files-especificos.ver');

    $this->group = Group::factory()->create(['name' => 'GrupoTest']);
});

function createComputerEspecifico(array $overrides = []): Computer
{
    $name = $overrides['computer_name'] ?? fake()->unique()->word();
    $defaults = [
        'computer_name' => $name,
        'nombre_instalacion' => $name,
        'plaza' => 'BAJAC',
        'group_id' => test()->group->id,
        'agent_config' => [
            'dbf_files' => [
                ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'C:\\PCOMB.DBF', 'checksum' => 'sha256hash', 'modified' => '2026-07-01 10:00:00 AM'],
            ],
        ],
        'last_seen' => now()->subMinutes(2),
    ];

    return Computer::factory()->create(array_merge($defaults, $overrides));
}

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.dbf-files-especificos'));

    $response->assertOk();
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reportes.dbf-files-especificos'));

    $response->assertForbidden();
});

it('returns flat rows with one row per file', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-001',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024],
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'AA1111', 'size' => 512],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data'));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    expect($data[0])->toHaveKeys(['id', 'nombre_instalacion', 'plaza', 'archivo', 'ruta', 'tamano', 'modificacion', 'md5', 'rbf_path', 'rbf_hash', 'rbf_matched']);
});

it('only includes specific dbf files', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-MIXED',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024],
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'AA1111', 'size' => 512],
            ['name' => 'OTRO.DBF', 'hash_md5' => 'CC3333', 'size' => 256],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data'));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    $names = array_column($data, 'archivo');
    expect($names)->toContain('PCOMB.DBF');
    expect($names)->toContain('ARCERO.DBF');
    expect($names)->not->toContain('OTRO.DBF');
});

it('excludes computers with no specific files', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-NO-MATCH',
        'agent_config' => ['dbf_files' => [
            ['name' => 'OTRO.DBF', 'hash_md5' => 'CC3333', 'size' => 256],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data'));
    $response->assertOk();

    $json = $response->json();
    expect($json['data'])->toHaveCount(0);
    expect($json['recordsTotal'])->toBe(0);
});

it('filters by plaza', function () {
    createComputerEspecifico(['computer_name' => 'PC-A', 'plaza' => 'BAJAC']);
    createComputerEspecifico(['computer_name' => 'PC-B', 'plaza' => 'MTY']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['plaza' => ['BAJAC']]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['plaza'])->toBe('BAJAC');
});

it('filters by group', function () {
    $group2 = Group::factory()->create(['name' => 'GrupoB']);
    createComputerEspecifico(['computer_name' => 'PC-A', 'group_id' => $this->group->id]);
    createComputerEspecifico(['computer_name' => 'PC-B', 'group_id' => $group2->id]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['group_id' => [$this->group->id]]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-A');
});

it('filters by archivo showing only that file rows', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-001',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024],
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'AA1111', 'size' => 512],
        ]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-002',
        'plaza' => 'MTY',
        'agent_config' => ['dbf_files' => [
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'BB2222', 'size' => 256],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['archivo' => 'ARCERO.DBF']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
    foreach ($data as $row) {
        expect($row['archivo'])->toBe('ARCERO.DBF');
    }
});

it('filters by search term', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('ILIKE requires PostgreSQL');
    }

    createComputerEspecifico(['computer_name' => 'TIENDA-ALPHA', 'ip_address' => '10.0.0.1']);
    createComputerEspecifico(['computer_name' => 'TIENDA-BETA', 'ip_address' => '10.0.0.2']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['search' => 'ALPHA']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('TIENDA-ALPHA');
});

it('filters by estado actualizado', function () {
    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060',
    ]);

    createComputerEspecifico([
        'computer_name' => 'PC-MATCHED',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024]]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-NO-MATCH',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024]]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['estado' => 'actualizado']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-MATCHED');
});

it('filters by estado desactualizado', function () {
    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060',
    ]);

    createComputerEspecifico([
        'computer_name' => 'PC-MATCHED',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024]]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-NO-MATCH',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024]]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['estado' => 'desactualizado']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-NO-MATCH');
});

it('paginates flat rows correctly', function () {
    foreach (range(1, 26) as $i) {
        createComputerEspecifico([
            'computer_name' => "PC-{$i}",
            'agent_config' => ['dbf_files' => [
                ['name' => 'PCOMB.DBF', 'hash_md5' => "hash{$i}", 'size' => 1024],
            ]],
        ]);
    }

    $page1 = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['start' => 0, 'length' => 20]));
    $page1->assertOk();
    expect($page1->json('data'))->toHaveCount(20);

    $page2 = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['start' => 20, 'length' => 20]));
    $page2->assertOk();
    expect($page2->json('data'))->toHaveCount(6);
});

it('sorts by nombre_instalacion ascending', function () {
    createComputerEspecifico(['computer_name' => 'ZEBRA', 'nombre_instalacion' => 'ZEBRA']);
    createComputerEspecifico(['computer_name' => 'ALPHA', 'nombre_instalacion' => 'ALPHA']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['sort' => 'nombre_instalacion', 'direction' => 'asc']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['nombre_instalacion'])->toBe('ALPHA');
    expect($data[1]['nombre_instalacion'])->toBe('ZEBRA');
});

it('sorts by archivo ascending', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-001',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PROMARTS.DBF', 'hash_md5' => 'AA', 'size' => 100],
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'BB', 'size' => 200],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data', ['sort' => 'archivo', 'direction' => 'asc']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['archivo'])->toBe('ARCERO.DBF');
    expect($data[1]['archivo'])->toBe('PROMARTS.DBF');
});

it('returns empty data when no computers have specific files', function () {
    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data'));
    $response->assertOk();

    $json = $response->json();
    expect($json['data'])->toHaveCount(0);
    expect($json['recordsTotal'])->toBe(0);
});

it('includes rbf_stats with expected keys', function () {
    createComputerEspecifico(['computer_name' => 'PC-001', 'plaza' => 'BAJAC']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data'));
    $response->assertOk();

    $stats = $response->json('rbf_stats');
    expect($stats)->toHaveKeys(['total_files', 'total_matched', 'total_unmatched', 'percent', 'per_plaza', 'per_file']);
});

it('creates commands for desactualizado computers via ejecutar', function () {
    Permission::firstOrCreate(['name' => 'dbf-files-especificos.ejecutar', 'guard_name' => 'web']);
    $this->user->givePermissionTo('dbf-files-especificos.ejecutar');

    createComputerEspecifico([
        'computer_name' => 'PC-DESACT',
        'nombre_instalacion' => 'PC-DESACT',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024],
        ]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-ACT',
        'nombre_instalacion' => 'PC-ACT',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024],
        ]],
    ]);

    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => 'combo']));
    $response->assertOk();

    $json = $response->json();
    expect($json['success'])->toBeTrue();
    expect($json['count'])->toBe(1);

    $commands = Command::where('type', 'execute')->get();
    expect($commands)->toHaveCount(1);
    expect($commands[0]->computer_id)->toBe(Computer::where('nombre_instalacion', 'PC-DESACT')->first()->id);
    expect($commands[0]->data['command'])->toBe('DACOMBO.BAT');
});

it('ejecutar returns 403 without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => 'lista']));
    $response->assertForbidden();
});

it('ejecutar returns 400 for invalid tipo', function () {
    Permission::firstOrCreate(['name' => 'dbf-files-especificos.ejecutar', 'guard_name' => 'web']);
    $this->user->givePermissionTo('dbf-files-especificos.ejecutar');

    $response = $this->actingAs($this->user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => 'invalido']));
    $response->assertStatus(400);
});

it('ejecutar filters by plaza', function () {
    Permission::firstOrCreate(['name' => 'dbf-files-especificos.ejecutar', 'guard_name' => 'web']);
    $this->user->givePermissionTo('dbf-files-especificos.ejecutar');

    createComputerEspecifico([
        'computer_name' => 'PC-BAJAC',
        'nombre_instalacion' => 'PC-BAJAC',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'LISTA.DBF', 'hash_md5' => 'XXXX', 'size' => 1024],
        ]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-MTY',
        'nombre_instalacion' => 'PC-MTY',
        'plaza' => 'MTY',
        'agent_config' => ['dbf_files' => [
            ['name' => 'LISTA.DBF', 'hash_md5' => 'YYYY', 'size' => 1024],
        ]],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => 'lista']), [
        'plaza' => ['BAJAC'],
    ]);
    $response->assertOk();

    $json = $response->json();
    expect($json['success'])->toBeTrue();
    expect($json['count'])->toBe(1);

    $computer = Computer::where('nombre_instalacion', 'PC-BAJAC')->first();
    expect(Command::where('computer_id', $computer->id)->where('type', 'execute')->exists())->toBeTrue();
});

it('ejecutar only targets desactualizado for the specific dbf file', function () {
    Permission::firstOrCreate(['name' => 'dbf-files-especificos.ejecutar', 'guard_name' => 'web']);
    $this->user->givePermissionTo('dbf-files-especificos.ejecutar');

    createComputerEspecifico([
        'computer_name' => 'PC-LISTA',
        'nombre_instalacion' => 'PC-LISTA',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'LISTA.DBF', 'hash_md5' => 'XXXX', 'size' => 1024],
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024],
        ]],
    ]);

    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060',
    ]);

    $response = $this->actingAs($this->user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => 'lista']));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);

    $responsePromo = $this->actingAs($this->user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => 'combo']));
    $responsePromo->assertOk();

    expect($responsePromo->json('count'))->toBe(0);
});

it('ejecutar for all four tipos creates correct commands', function () {
    Permission::firstOrCreate(['name' => 'dbf-files-especificos.ejecutar', 'guard_name' => 'web']);
    $this->user->givePermissionTo('dbf-files-especificos.ejecutar');

    createComputerEspecifico([
        'computer_name' => 'PC-ALL',
        'nombre_instalacion' => 'PC-ALL',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'LISTA.DBF', 'hash_md5' => 'AA', 'size' => 1024],
            ['name' => 'PROMARTS.DBF', 'hash_md5' => 'BB', 'size' => 1024],
            ['name' => 'OFERTAS.DBF', 'hash_md5' => 'CC', 'size' => 1024],
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'DD', 'size' => 1024],
        ]],
    ]);

    $map = ['lista' => 'DALISTA.BAT', 'promocion' => 'DAPROMO.BAT', 'oferta' => 'DAOFERTA.BAT', 'combo' => 'DACOMBO.BAT'];

    foreach ($map as $tipo => $bat) {
        $response = $this->actingAs($this->user)->postJson(route('reportes.dbf-files-especificos.ejecutar', ['tipo' => $tipo]));
        $response->assertOk();
        expect($response->json('count'))->toBe(1);

        $cmd = Command::where('computer_id', Computer::first()->id)
            ->where('type', 'execute')
            ->where('data->command', $bat)
            ->first();
        expect($cmd)->not->toBeNull();
    }
});

it('marks file as actualizado when rbf hash matches', function () {
    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060',
    ]);

    createComputerEspecifico([
        'computer_name' => 'PC-001',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024]]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['rbf_matched'])->toBeTrue();
});

it('ids returns unique computer ids for a multi-file computer', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-MULTI',
        'nombre_instalacion' => 'PC-MULTI',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'A1', 'size' => 1024],
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'B2', 'size' => 512],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids'));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);
    expect($response->json('ids'))->toHaveCount(1);
});

it('ids respects estado filter desactualizado', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-DESACT',
        'nombre_instalacion' => 'PC-DESACT',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'DESAC', 'size' => 1024],
        ]],
    ]);

    createComputerEspecifico([
        'computer_name' => 'PC-ACT',
        'nombre_instalacion' => 'PC-ACT',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024],
        ]],
    ]);

    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.DBF', 'name' => 'PCOMB.DBF',
        'hash' => '8B7060',
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids', ['estado' => 'desactualizado']));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);
    expect($response->json('ids'))->toHaveCount(1);
});

it('ids respects plaza filter', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-BAJAC',
        'nombre_instalacion' => 'PC-BAJAC',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'A1', 'size' => 1024],
        ]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-MTY',
        'nombre_instalacion' => 'PC-MTY',
        'plaza' => 'MTY',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'B2', 'size' => 1024],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids', ['plaza' => ['BAJAC']]));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);
    expect($response->json('ids'))->toHaveCount(1);
});

it('ids respects group filter', function () {
    $group2 = Group::factory()->create(['name' => 'GrupoB']);
    createComputerEspecifico([
        'computer_name' => 'PC-A',
        'nombre_instalacion' => 'PC-A',
        'group_id' => $this->group->id,
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'A1', 'size' => 1024],
        ]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'PC-B',
        'nombre_instalacion' => 'PC-B',
        'group_id' => $group2->id,
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'B2', 'size' => 1024],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids', ['group_id' => [$this->group->id]]));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);
    $pcA = Computer::where('nombre_instalacion', 'PC-A')->first();
    expect($response->json('ids'))->toHaveCount(1);
    expect((int) $response->json('ids')[0])->toBe($pcA->id);
});

it('ids respects archivo filter', function () {
    createComputerEspecifico([
        'computer_name' => 'PC-MIXED',
        'nombre_instalacion' => 'PC-MIXED',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'A1', 'size' => 1024],
            ['name' => 'ARCERO.DBF', 'hash_md5' => 'B2', 'size' => 512],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids', ['archivo' => 'PCOMB.DBF']));
    $response->assertOk();
    expect($response->json('count'))->toBe(1);

    $response2 = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids', ['archivo' => 'ARCERO.DBF']));
    $response2->assertOk();
    expect($response2->json('count'))->toBe(1);
});

it('ids respects search filter', function () {
    createComputerEspecifico([
        'computer_name' => 'ALPHA-PC',
        'nombre_instalacion' => 'ALPHA-PC',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'A1', 'size' => 1024],
        ]],
    ]);
    createComputerEspecifico([
        'computer_name' => 'BETA-PC',
        'nombre_instalacion' => 'BETA-PC',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'B2', 'size' => 1024],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.ids', ['search' => 'ALPHA']));
    $response->assertOk();

    expect($response->json('count'))->toBe(1);
    expect($response->json('ids'))->toHaveCount(1);
});

it('ids returns 403 without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('reportes.dbf-files-especificos.ids'));

    $response->assertForbidden();
});

function createHeartbeatLog(Computer $computer, array $files, $createdAt): void
{
    $payload = json_encode([
        'dbf_files' => $files,
        'filler' => str_repeat('x', 1500),
    ]);

    $log = new ComputerLog;
    $log->computer_id = $computer->id;
    $log->level = 'INFO';
    $log->message = '[2026-08-05 10:00:00 UTC:PC:'.($computer->computer_name ?? 'TEST').'] INFO: Heartbeat JSON payload: '.$payload;
    $log->created_at = $createdAt;
    $log->save();
}

it('historial returns distinct hashes with last modification for a file', function () {
    $computer = createComputerEspecifico(['computer_name' => 'PC-HIST']);
    $archivo = 'ARCERO.DBF';

    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'AAAA1', 'modified' => '2026-08-03 09:00:00']], now()->subDays(2));
    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'BBBB2', 'modified' => '2026-08-05 11:00:00']], now()->subHour());
    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'BBBB2', 'modified' => '2026-08-05 11:30:00']], now());

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => $computer->id,
        'archivo' => $archivo,
    ]));

    $response->assertOk();
    $json = $response->json();
    expect($json['success'])->toBeTrue();
    expect($json['historial'])->toHaveCount(2);
    $hashes = array_column($json['historial'], 'hash');
    expect($hashes)->toContain('AAAA1');
    expect($hashes)->toContain('BBBB2');

    $b = collect($json['historial'])->firstWhere('hash', 'BBBB2');
    expect($b['modified'])->toBe('2026-08-05 11:30 AM');
});

it('historial shows single entry when hash does not change', function () {
    $computer = createComputerEspecifico(['computer_name' => 'PC-UNO']);
    $archivo = 'LISTA.DBF';

    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'CCCC3', 'modified' => '2026-08-04 08:00:00 AM']], now()->subDays(2));
    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'CCCC3', 'modified' => '2026-08-05 09:00:00 AM']], now()->subHour());

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => $computer->id,
        'archivo' => $archivo,
    ]));

    $response->assertOk();
    expect($response->json('historial'))->toHaveCount(1);
    expect($response->json('historial')[0]['hash'])->toBe('CCCC3');
});

it('historial ignores logs older than three days', function () {
    $computer = createComputerEspecifico(['computer_name' => 'PC-VIEJO']);
    $archivo = 'OFERTAS.DBF';

    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'OLD99', 'modified' => '2026-07-20 09:00:00 AM']], now()->subDays(10));
    createHeartbeatLog($computer, [['name' => $archivo, 'hash_md5' => 'NEW88', 'modified' => '2026-08-05 10:00:00 AM']], now()->subHour());

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => $computer->id,
        'archivo' => $archivo,
    ]));

    $response->assertOk();
    $hashes = array_column($response->json('historial'), 'hash');
    expect($hashes)->toContain('NEW88');
    expect($hashes)->not->toContain('OLD99');
});

it('historial ignores non-heartbeat logs', function () {
    $computer = createComputerEspecifico(['computer_name' => 'PC-LOG']);
    $archivo = 'PCOMB.DBF';

    ComputerLog::create([
        'computer_id' => $computer->id,
        'level' => 'ERROR',
        'message' => 'Something went wrong',
        'created_at' => now()->subMinutes(5),
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => $computer->id,
        'archivo' => $archivo,
    ]));

    $response->assertOk();
    expect($response->json('historial'))->toHaveCount(0);
});

it('historial returns empty when file not present in heartbeats', function () {
    $computer = createComputerEspecifico(['computer_name' => 'PC-OTRO']);

    createHeartbeatLog($computer, [['name' => 'LISTA.DBF', 'hash_md5' => 'DD44', 'modified' => '2026-08-05 09:00:00 AM']], now()->subHour());

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => $computer->id,
        'archivo' => 'ARCERO.DBF',
    ]));

    $response->assertOk();
    expect($response->json('historial'))->toHaveCount(0);
});

it('historial validates required params', function () {
    $computer = createComputerEspecifico(['computer_name' => 'PC-VALID']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', ['archivo' => 'ARCERO.DBF']));
    $response->assertStatus(422);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', ['computer_id' => $computer->id]));
    $response->assertStatus(422);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', ['computer_id' => $computer->id, 'archivo' => 'INVALIDO.DBF']));
    $response->assertStatus(422);
});

it('historial returns 404 for unknown computer', function () {
    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => 999999,
        'archivo' => 'ARCERO.DBF',
    ]));

    $response->assertNotFound();
});

it('historial returns 403 without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('reportes.dbf-files-especificos.historial', [
        'computer_id' => 1,
        'archivo' => 'ARCERO.DBF',
    ]));

    $response->assertForbidden();
});
