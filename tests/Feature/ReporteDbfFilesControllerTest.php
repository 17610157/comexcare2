<?php

use App\Models\Computer;
use App\Models\Group;
use App\Models\RbfFileHash;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'dbf-files.ver', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('dbf-files.ver');

    $this->group = Group::factory()->create(['name' => 'GrupoTest']);
});

function createComputer(array $overrides = []): Computer
{
    $name = $overrides['computer_name'] ?? fake()->unique()->word();
    $defaults = [
        'computer_name' => $name,
        'nombre_instalacion' => $name,
        'plaza' => 'BAJAC',
        'group_id' => test()->group->id,
        'agent_config' => [
            'dbf_files' => [
                ['name' => 'PCOMB.EXE', 'hash_md5' => '8B7060', 'size' => 1024, 'path' => 'C:\\PCOMB.EXE', 'checksum' => 'sha256hash', 'modified' => '2026-07-01 10:00:00 AM'],
            ],
        ],
        'last_seen' => now()->subMinutes(2),
    ];

    return Computer::factory()->create(array_merge($defaults, $overrides));
}

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.dbf-files'));

    $response->assertOk();
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reportes.dbf-files'));

    $response->assertForbidden();
});

it('returns data with computers and stats', function () {
    createComputer(['computer_name' => 'PC-001']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data'));

    $response->assertOk();
    $json = $response->json();
    expect($json['data'])->toHaveCount(1);
    expect($json['recordsTotal'])->toBe(1);
    expect($json['rbf_stats']['total_files'])->toBeGreaterThan(0);
});

it('paginates correctly on page 2', function () {
    foreach (range(1, 55) as $i) {
        createComputer(['computer_name' => "PC-{$i}"]);
    }

    $page1 = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['start' => 0, 'length' => 50]));
    $page1->assertOk();
    expect($page1->json('data'))->toHaveCount(50);

    $page2 = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['start' => 50, 'length' => 50]));
    $page2->assertOk();
    expect($page2->json('data'))->toHaveCount(5);
});

it('filters by search term', function () {
    if (config('database.default') !== 'pgsql') {
        $this->markTestSkipped('ILIKE requires PostgreSQL');
    }

    createComputer(['computer_name' => 'TIENDA-ALPHA', 'ip_address' => '10.0.0.1']);
    createComputer(['computer_name' => 'TIENDA-BETA', 'ip_address' => '10.0.0.2']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['search' => 'ALPHA']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('TIENDA-ALPHA');
});

it('filters by plaza', function () {
    createComputer(['computer_name' => 'PC-A', 'plaza' => 'BAJAC']);
    createComputer(['computer_name' => 'PC-B', 'plaza' => 'MTY']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['plaza' => ['BAJAC']]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['plaza'])->toBe('BAJAC');
});

it('filters by group', function () {
    $group2 = Group::factory()->create(['name' => 'GrupoB']);
    createComputer(['computer_name' => 'PC-A', 'group_id' => $this->group->id]);
    createComputer(['computer_name' => 'PC-B', 'group_id' => $group2->id]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['group_id' => [$this->group->id]]));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['group_name'])->toBe('GrupoTest');
});

it('filters by estado actualizado', function () {
    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.EXE', 'name' => 'PCOMB.EXE',
        'hash' => 'B7060',
    ]);

    createComputer([
        'computer_name' => 'PC-MATCHED',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.EXE', 'hash_md5' => '8B7060', 'size' => 1024]]],
    ]);
    createComputer([
        'computer_name' => 'PC-NO-MATCH',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.EXE', 'hash_md5' => 'FFFFFF', 'size' => 1024]]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['estado' => 'actualizado']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-MATCHED');
});

it('filters by estado desactualizado', function () {
    RbfFileHash::create([
        'servicio' => 'combo', 'plaza' => 'bajac', 'zona' => 'norte',
        'path' => '/combo/bajac/norte/PCOMB.EXE', 'name' => 'PCOMB.EXE',
        'hash' => 'B7060',
    ]);

    createComputer([
        'computer_name' => 'PC-MATCHED',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.EXE', 'hash_md5' => '8B7060', 'size' => 1024]]],
    ]);
    createComputer([
        'computer_name' => 'PC-NO-MATCH',
        'plaza' => 'BAJAC',
        'agent_config' => ['dbf_files' => [['name' => 'PCOMB.EXE', 'hash_md5' => 'FFFFFF', 'size' => 1024]]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['estado' => 'desactualizado']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1);
    expect($data[0]['nombre_instalacion'])->toBe('PC-NO-MATCH');
});

it('sorts by nombre_instalacion ascending', function () {
    createComputer(['computer_name' => 'ZEBRA', 'nombre_instalacion' => 'ZEBRA']);
    createComputer(['computer_name' => 'ALPHA', 'nombre_instalacion' => 'ALPHA']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['sort' => 'nombre_instalacion', 'direction' => 'asc']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['nombre_instalacion'])->toBe('ALPHA');
    expect($data[1]['nombre_instalacion'])->toBe('ZEBRA');
});

it('sorts by nombre_instalacion descending', function () {
    createComputer(['computer_name' => 'ALPHA', 'nombre_instalacion' => 'ALPHA']);
    createComputer(['computer_name' => 'ZEBRA', 'nombre_instalacion' => 'ZEBRA']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['sort' => 'nombre_instalacion', 'direction' => 'desc']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['nombre_instalacion'])->toBe('ZEBRA');
    expect($data[1]['nombre_instalacion'])->toBe('ALPHA');
});

it('returns empty data when no computers exist', function () {
    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data'));
    $response->assertOk();

    $json = $response->json();
    expect($json['data'])->toHaveCount(0);
    expect($json['recordsTotal'])->toBe(0);
});

it('includes per_category stats in rbf_stats', function () {
    createComputer(['computer_name' => 'PC-001', 'plaza' => 'BAJAC']);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data'));
    $response->assertOk();

    $stats = $response->json('rbf_stats');
    expect($stats)->toHaveKeys(['total_files', 'total_matched', 'total_unmatched', 'percent', 'per_category', 'per_plaza', 'per_file', 'per_group', 'top_outdated']);
    expect($stats['per_category'])->toHaveKeys(['exe', 'bat', 'other']);
    expect($stats['per_category']['exe'])->toHaveKeys(['total', 'matched', 'unmatched', 'percent']);
    expect($stats['per_category']['bat'])->toHaveKeys(['total', 'matched', 'unmatched', 'percent']);
    expect($stats['per_category']['other'])->toHaveKeys(['total', 'matched', 'unmatched', 'percent']);
});

it('computes per-category stats for computers with mixed file types', function () {
    createComputer([
        'computer_name' => 'PC-MIXED',
        'agent_config' => ['dbf_files' => [
            ['name' => 'POS32.EXE', 'hash_md5' => 'AA1111', 'size' => 1024, 'path' => 'D:\\pvsi\\POS32.exe'],
            ['name' => 'BACKUP.BAT', 'hash_md5' => 'BB2222', 'size' => 512, 'path' => 'D:\\pvsi\\BACKUP.BAT'],
            ['name' => 'ARTICULOS.EXE', 'hash_md5' => 'CC3333', 'size' => 256, 'path' => 'D:\\pvsi\\ARTICULOS.EXE'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data'));
    $response->assertOk();

    $stats = $response->json('rbf_stats');
    expect($stats['per_category']['exe']['total'])->toBe(2);
    expect($stats['per_category']['bat']['total'])->toBe(1);
    expect($stats['per_category']['other']['total'])->toBe(0);
});

it('filters detail files by file_category exe', function () {
    createComputer([
        'computer_name' => 'PC-CAT',
        'agent_config' => ['dbf_files' => [
            ['name' => 'POS32.EXE', 'hash_md5' => 'AA1111', 'size' => 1024, 'path' => 'D:\\pvsi\\POS32.exe'],
            ['name' => 'BACKUP.BAT', 'hash_md5' => 'BB2222', 'size' => 512, 'path' => 'D:\\pvsi\\BACKUP.BAT'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['file_category' => 'exe']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['dbf_files'])->toHaveCount(1);
    expect($data[0]['dbf_files'][0]['name'])->toBe('POS32.EXE');
});

it('filters detail files by file_category bat', function () {
    createComputer([
        'computer_name' => 'PC-CAT',
        'agent_config' => ['dbf_files' => [
            ['name' => 'POS32.EXE', 'hash_md5' => 'AA1111', 'size' => 1024, 'path' => 'D:\\pvsi\\POS32.exe'],
            ['name' => 'BACKUP.BAT', 'hash_md5' => 'BB2222', 'size' => 512, 'path' => 'D:\\pvsi\\BACKUP.BAT'],
            ['name' => 'ARTICULOS.EXE', 'hash_md5' => 'CC3333', 'size' => 256, 'path' => 'D:\\pvsi\\ARTICULOS.EXE'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['file_category' => 'bat']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['dbf_files'])->toHaveCount(1);
    expect($data[0]['dbf_files'][0]['name'])->toBe('BACKUP.BAT');
});

it('filters detail files by file_category other is ignored', function () {
    createComputer([
        'computer_name' => 'PC-CAT',
        'agent_config' => ['dbf_files' => [
            ['name' => 'POS32.EXE', 'hash_md5' => 'AA1111', 'size' => 1024, 'path' => 'D:\\pvsi\\POS32.exe'],
            ['name' => 'BACKUP.BAT', 'hash_md5' => 'BB2222', 'size' => 512, 'path' => 'D:\\pvsi\\BACKUP.BAT'],
            ['name' => 'ARTICULOS.EXE', 'hash_md5' => 'CC3333', 'size' => 256, 'path' => 'D:\\pvsi\\ARTICULOS.EXE'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data', ['file_category' => 'other']));
    $response->assertOk();

    $data = $response->json('data');
    expect($data[0]['dbf_files'])->toHaveCount(3);
});

it('does not include checksum in file data', function () {
    createComputer([
        'computer_name' => 'PC-CHECK',
        'agent_config' => ['dbf_files' => [
            ['name' => 'PCOMB.EXE', 'hash_md5' => '8B7060', 'size' => 1024, 'checksum' => 'abc123'],
        ]],
    ]);

    $response = $this->actingAs($this->user)->getJson(route('reportes.dbf-files.data'));
    $response->assertOk();

    $file = $response->json('data.0.dbf_files.0');
    expect($file)->not->toHaveKey('checksum');
});
