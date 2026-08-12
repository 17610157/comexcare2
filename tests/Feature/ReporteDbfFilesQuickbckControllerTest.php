<?php

use App\Models\Computer;
use App\Models\ConciliacionHashArchivo;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'dbf-files-quickbck.ver', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('dbf-files-quickbck.ver');

    $this->group = Group::factory()->create(['name' => 'GrupoTest']);
});

function createComputerQuickbck(array $overrides = []): Computer
{
    $name = $overrides['computer_name'] ?? fake()->unique()->word();
    $defaults = [
        'computer_name' => $name,
        'nombre_instalacion' => $name,
        'plaza' => 'CHETU',
        'short_key' => 'BAJAC',
        'group_id' => test()->group->id,
        'agent_config' => [
            'dbf_files' => [
                ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
            ],
        ],
        'last_seen' => now()->subMinutes(2),
    ];

    return Computer::factory()->create(array_merge($defaults, $overrides));
}

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.dbf-files-quickbck'));

    $response->assertOk();
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reportes.dbf-files-quickbck'));

    $response->assertForbidden();
});

it('only includes quickbck files', function () {
    createComputerQuickbck([
        'computer_name' => 'PC-MIXED',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
            ['name' => 'OTRO.DBF', 'hash_md5' => 'CC3333', 'size' => 256, 'path' => 'D:\\pvsi\\OTRO.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['archivo'])->toBe('PCOMB.DBF');
});

it('excludes computers with no quickbck files', function () {
    createComputerQuickbck([
        'computer_name' => 'PC-NO-QBCK',
        'agent_config' => ['dbf_files' => [
            ['name' => 'OTRO.DBF', 'hash_md5' => 'CC3333', 'size' => 256, 'path' => 'D:\\pvsi\\OTRO.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $json = $response->json();
    expect($json['data'])->toHaveCount(0);
    expect($json['recordsTotal'])->toBe(0);
});

it('marks pvsi partial error when date differs more than 1 day', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-06-30 09:00:00', 'disparador' => 'pvsi',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['pvsi_md5'])->toBe('B7060');
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('parcial_error');
    expect($row['desactualizado'])->toBeTrue();
});

it('marks pvsi partial ok when all columns have data and date differs within 5 minutes', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 10:02:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'AAAAA',
        'fecha_modificacion' => '2026-07-01 10:01:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('parcial_ok');
    expect($row['desactualizado'])->toBeFalse();
});

it('marks pvsi partial error when a column is missing even if date is fresh', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 10:02:00', 'disparador' => 'pvsi',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['rbf_md5'])->toBeNull();
    expect($row['status_conciliacion'])->toBe('parcial_error');
    expect($row['desactualizado'])->toBeTrue();
});

it('marks rbf partial error when date differs more than 1 day', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-06-30 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['rbf_matched'])->toBeTrue();
    expect($row['rbf_md5'])->toBe('B7060');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('parcial_error');
    expect($row['desactualizado'])->toBeTrue();
});

it('marks conciliado when both pvsi and rbf match', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['rbf_matched'])->toBeTrue();
    expect($row['status_conciliacion'])->toBe('conciliado');
});

it('marks conciliado when the 3 hashes match regardless of how old the date is', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-06-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-06-01 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-06-01 07:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['rbf_matched'])->toBeTrue();
    expect($row['status_conciliacion'])->toBe('conciliado');
    expect($row['desactualizado'])->toBeFalse();
});

it('marks conciliado when pvsi and rbf hashes match each other regardless of quickbck', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('conciliado');
    expect($row['desactualizado'])->toBeFalse();
});

it('marks conciliado when pvsi and rbf hashes match each other regardless of date', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-06-30 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-06-30 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('conciliado');
    expect($row['desactualizado'])->toBeFalse();
});

it('marks quick partial ok when quick and rbf hashes match but pvsi differs within 5 minutes', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'AAAAA',
        'fecha_modificacion' => '2026-07-01 10:01:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 10:02:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['rbf_matched'])->toBeTrue();
    expect($row['status_conciliacion'])->toBe('parcial_ok');
    expect($row['desactualizado'])->toBeFalse();
});

it('marks quick partial error when quick and rbf hashes match but pvsi differs more than 5 minutes', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'AAAAA',
        'fecha_modificacion' => '2026-06-30 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 10:02:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['rbf_matched'])->toBeTrue();
    expect($row['status_conciliacion'])->toBe('parcial_error');
    expect($row['desactualizado'])->toBeTrue();
});

it('marks pvsi partial error when dates differ more than 5 minutes', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'AAAAA',
        'fecha_modificacion' => '2026-07-01 10:02:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF', 'modified' => '2026-07-01 10:00:00 AM'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('parcial_error');
    expect($row['desactualizado'])->toBeTrue();
});

it('marks sin_conciliar when no match', function () {
    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('sin_conciliar');
});

it('does not include agente column', function () {
    createComputerQuickbck(['computer_name' => 'PC-001']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row)->not->toHaveKey('agente');
});

it('includes conciliacion_stats with expected keys', function () {
    createComputerQuickbck(['computer_name' => 'PC-001']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $stats = $response->json('conciliacion_stats');
    expect($stats)->toHaveKeys([
        'total', 'pvsi_matched', 'rbf_matched', 'both_matched', 'none_matched',
        'conciliado', 'parcial_ok', 'parcial_error', 'sin_conciliar',
    ]);
});

it('filters by estado conciliado', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-CONCILIADO',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);
    createComputerQuickbck([
        'computer_name' => 'PC-SIN',
        'short_key' => 'SIN01',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data', ['estado' => 'conciliado']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-CONCILIADO');
});

it('filters by multiple conciliacion estados as array', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-CONCILIADO',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);
    createComputerQuickbck([
        'computer_name' => 'PC-SIN',
        'short_key' => 'SIN01',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'FFFFFF', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data', ['estado' => ['conciliado', 'parcial_ok']]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-CONCILIADO');
});

it('filters by plaza', function () {
    createComputerQuickbck(['computer_name' => 'PC-A', 'short_key' => 'CALVO']);
    createComputerQuickbck(['computer_name' => 'PC-B', 'short_key' => 'CANDR']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data', ['plaza' => ['CHETU']]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2);
});

it('filters by group', function () {
    $group2 = Group::factory()->create(['name' => 'GrupoB']);
    createComputerQuickbck(['computer_name' => 'PC-A', 'group_id' => $this->group->id, 'short_key' => 'GRPA']);
    createComputerQuickbck(['computer_name' => 'PC-B', 'group_id' => $group2->id, 'short_key' => 'GRPB']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data', ['group_id' => [$this->group->id]]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-A');
});

it('shows md5 quick truncated to last 5 characters', function () {
    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => 'ABCDEF12345', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['md5'])->toBe('12345');
});

it('does not match when md5 last5 differs but api data is shown', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => '12345',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['pvsi_md5'])->toBe('12345');
    expect($row['status_conciliacion'])->toBe('sin_conciliar');
});

it('returns empty data when no computers have quickbck files', function () {
    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $json = $response->json();
    expect($json['data'])->toHaveCount(0);
    expect($json['recordsTotal'])->toBe(0);
});

it('shows rbf api data in columns even when rbf does not match agent hash', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'bajac', 'archivo' => 'PCOMB.DBF', 'md5' => 'AAAAA',
        'fecha_modificacion' => '2026-07-01 08:00:00', 'disparador' => 'rbf',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeTrue();
    expect($row['pvsi_md5'])->toBe('B7060');
    expect($row['rbf_matched'])->toBeFalse();
    expect($row['rbf_md5'])->toBe('AAAAA');
    expect($row['rbf_fecha'])->not->toBeNull();
    expect($row['status_conciliacion'])->toBe('parcial_error');
});

it('does not match when short_key differs', function () {
    ConciliacionHashArchivo::create([
        'sucursal' => 'mty', 'archivo' => 'PCOMB.DBF', 'md5' => 'B7060',
        'fecha_modificacion' => '2026-07-01 09:00:00', 'disparador' => 'pvsi',
    ]);

    createComputerQuickbck([
        'computer_name' => 'PC-001',
        'short_key' => 'BAJAC',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.DBF', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'D:\\pvsi\\quickbck\\PCOMB.DBF'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row['pvsi_matched'])->toBeFalse();
    expect($row['status_conciliacion'])->toBe('sin_conciliar');
});

it('includes data row with expected keys', function () {
    createComputerQuickbck(['computer_name' => 'PC-001']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files-quickbck.data'));
    $response->assertOk();

    $row = $response->json('data.0');
    expect($row)->toHaveKeys([
        'nombre_instalacion', 'plaza', 'status', 'last_seen', 'archivo',
        'tamano', 'modificacion', 'md5',
        'pvsi_md5', 'pvsi_fecha', 'rbf_md5', 'rbf_fecha',
        'pvsi_matched', 'rbf_matched', 'status_conciliacion', 'desactualizado',
    ]);
});
