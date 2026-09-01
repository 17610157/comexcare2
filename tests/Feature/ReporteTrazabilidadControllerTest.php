<?php

use App\Models\Computer;
use App\Models\ConciliacionHashArchivo;
use App\Models\Group;
use App\Models\HashArchivoHistorial;
use App\Models\HashArchivoLote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'reportes.trazabilidad.ver', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('reportes.trazabilidad.ver');

    $this->group = Group::factory()->create(['name' => 'GrupoTest', 'type' => 'tienda']);
});

function createTrazabilidadData(): Computer
{
    $computer = Computer::factory()->create([
        'computer_name' => 'TiendaTest',
        'nombre_instalacion' => 'TiendaTest',
        'plaza' => 'CHETU',
        'short_key' => 'CALVO',
        'group_id' => test()->group->id,
        'status' => 'online',
        'ip_address' => '192.168.1.100',
        'agent_config' => [
            'dbf_files' => [
                ['name' => 'AJTFLU.DBF', 'hash_md5' => '231B7', 'path' => 'C:\\PVSI\\quickbck\\AJTFLU.DBF', 'modified' => '2026-03-18T15:19:21'],
                ['name' => 'AJTFLU.DBF', 'hash_md5' => 'A171E', 'path' => 'D:\\PVSI\\otra\\AJTFLU.DBF', 'modified' => '2026-03-18T15:19:21'],
            ],
        ],
    ]);

    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'AJTFLU.DBF',
        'md5' => '231B7',
        'disparador' => 'rbf',
        'fecha_modificacion' => now()->subDay(),
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'AJTFLU.DBF',
        'md5' => '372CF',
        'disparador' => 'pvsi',
        'fecha_modificacion' => now()->subDays(2),
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'AJTFLU.DBF',
        'md5' => '99999',
        'disparador' => 'cortefin',
        'fecha_modificacion' => now(),
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'AJTFLU.DBF',
        'md5' => 'ABCDE',
        'disparador' => 'quickbck',
        'fecha_modificacion' => now(),
    ]);

    return $computer;
}

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad'));

    $response->assertOk();
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reportes.trazabilidad'));

    $response->assertForbidden();
});

it('lists tiendas (computers) as rows in the data endpoint', function () {
    createTrazabilidadData();

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.data'));

    $response->assertOk();
    $json = $response->json('data');

    $row = collect($json)->first(fn ($r) => $r['short_key'] === 'CALVO');

    expect($row)->not->toBeNull();
    expect($row['nombre_instalacion'])->toBe('TiendaTest');
    expect($row['short_key'])->toBe('CALVO');
    expect($row['grupo'])->toBe('GrupoTest');
    expect($row['estado'])->toBe('online');
});

it('builds a dynamic trazabilidad table with disparadores as columns', function () {
    createTrazabilidadData();

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.archivos', ['short_key' => 'CALVO']));

    $response->assertOk();
    $json = $response->json();

    // First column combines cortefin/pvsi, then quickbck, then rbf
    expect($json['columnas'])->toBe(['cortefin/pvsi', 'quickbck', 'rbf']);
    expect($json['columnas'])->not->toContain('cortefin');
    expect($json['columnas'])->not->toContain('pvsi');

    $file = collect($json['files'])->first(fn ($f) => $f['archivo'] === 'ajtflu.dbf');
    expect($file)->not->toBeNull();
    expect($file['archivo'])->toEndWith('.dbf');

    // QuickBCK hashes come from the agent
    expect(count($file['quickbck']))->toBe(2);

    // First column uses cortefin hash when present (pvsi is fallback)
    $base = $file['disparadores']['cortefin/pvsi'];
    expect($base['hash'])->toBe('99999');
    expect($base['es_ancla'])->toBeTrue();
    expect($base['desactualizado'])->toBeFalse();

    $rbf = $file['disparadores']['rbf'];
    expect($rbf['hash'])->toBe('231B7');
    expect($rbf['es_ancla'])->toBeFalse();
    expect($rbf['desactualizado'])->toBeTrue();
});

it('only includes .dbf files in the trazabilidad', function () {
    createTrazabilidadData();
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'AJTFLU.BAT',
        'md5' => '11111',
        'disparador' => 'cortefin',
        'fecha_modificacion' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.archivos', ['short_key' => 'CALVO']));

    $archivos = collect($response->json('files'))->pluck('archivo')->all();
    expect($archivos)->toContain('ajtflu.dbf');
    expect($archivos)->not->toContain('ajtflu.bat');
});

it('falls back to pvsi in the first column when cortefin is absent', function () {
    createTrazabilidadData();
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'ASISTE.DBF',
        'md5' => '77E11',
        'disparador' => 'pvsi',
        'fecha_modificacion' => now(),
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'ASISTE.DBF',
        'md5' => '33F22',
        'disparador' => 'rbf',
        'fecha_modificacion' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.archivos', ['short_key' => 'CALVO']));

    $file = collect($response->json('files'))->first(fn ($f) => $f['archivo'] === 'asiste.dbf');
    expect($file)->not->toBeNull();
    $base = $file['disparadores']['cortefin/pvsi'];
    expect($base['hash'])->toBe('77E11');
    expect($base['es_ancla'])->toBeTrue();
    expect($file['disparadores']['rbf']['desactualizado'])->toBeTrue();
});

it('builds each disparador route from its own lote ruta_base', function () {
    createTrazabilidadData();
    HashArchivoLote::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'nombre_carpeta' => 'PVSI',
        'ruta_base' => 'D:\\PVSI',
        'fecha_envio' => now(),
        'disparador' => 'cortefin',
        'num_archivos' => 1,
        'peso_total' => 0,
        'estado' => 'exitoso',
        'payload' => '{}',
    ]);
    HashArchivoLote::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'nombre_carpeta' => 'calvo',
        'ruta_base' => 'D:\\_raiz_qbck\\cre\\chetu\\calvo',
        'fecha_envio' => now(),
        'disparador' => 'rbf',
        'num_archivos' => 1,
        'peso_total' => 0,
        'estado' => 'exitoso',
        'payload' => '{}',
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.archivos', ['short_key' => 'CALVO']));

    $file = collect($response->json('files'))->first(fn ($f) => $f['archivo'] === 'ajtflu.dbf');
    expect($file)->not->toBeNull();

    // Same base file, but each disparador resolves to its own route
    expect($file['disparadores']['cortefin/pvsi']['path'])->toBe('D:\\PVSI\\AJTFLU.DBF');
    expect($file['disparadores']['rbf']['path'])->toBe('D:\\_raiz_qbck\\cre\\chetu\\calvo\\AJTFLU.DBF');
});

it('returns the list of available .dbf files for the dropdown', function () {
    createTrazabilidadData();
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'ASISTE.DBF',
        'md5' => '77E11',
        'disparador' => 'cortefin',
        'fecha_modificacion' => now(),
    ]);
    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'archivo' => 'AJTFLU.BAT',
        'md5' => '11111',
        'disparador' => 'cortefin',
        'fecha_modificacion' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.archivos-disponibles'));

    $response->assertOk();
    $archivos = $response->json('archivos');
    expect($archivos)->toContain('ajtflu.dbf');
    expect($archivos)->toContain('asiste.dbf');
    expect($archivos)->not->toContain('ajtflu.bat');
});

it('excludes orphan conciliation records whose ip does not belong to the tienda', function () {
    $computer = createTrazabilidadData();

    ConciliacionHashArchivo::create([
        'sucursal' => 'CALVO',
        'ip' => '10.0.0.99',
        'archivo' => 'CANOTA.DBF',
        'md5' => 'AAAA1',
        'disparador' => 'cortefin',
        'fecha_modificacion' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.archivos', ['short_key' => 'CALVO']));

    $this->assertSame($computer->ip_address, '192.168.1.100');

    $archivos = collect($response->json('files'))->pluck('archivo')->all();
    expect($archivos)->toContain('ajtflu.dbf');
    expect($archivos)->not->toContain('canota.dbf');
});

it('exports a CSV with filter by file and status, one row per file x tienda', function () {
    createTrazabilidadData();
    HashArchivoLote::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'nombre_carpeta' => 'PVSI',
        'ruta_base' => 'D:\\PVSI',
        'fecha_envio' => now(),
        'disparador' => 'cortefin',
        'num_archivos' => 1,
        'peso_total' => 0,
        'estado' => 'exitoso',
        'payload' => '{}',
    ]);
    HashArchivoLote::create([
        'sucursal' => 'CALVO',
        'ip' => '192.168.1.100',
        'nombre_carpeta' => 'calvo',
        'ruta_base' => 'D:\\_raiz_qbck\\cre\\chetu\\calvo',
        'fecha_envio' => now(),
        'disparador' => 'rbf',
        'num_archivos' => 1,
        'peso_total' => 0,
        'estado' => 'exitoso',
        'payload' => '{}',
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.export', [
        'archivo' => 'AJTFLU.DBF',
        'estado' => 'online',
    ]));

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('Tienda;Clave;Plaza;Estado;Archivo;"Hash CORTEFIN/PVSI";"Fecha CORTEFIN/PVSI";"Hash QuickBCK";"Fecha QuickBCK";"Hash RBF";"Fecha RBF"');

    // cortefin (now) is more recent than pvsi (now-2d), so cortefin hash 99999 is the anchor
    expect($content)->toContain('AJTFLU.DBF;99999;');
    // routes are no longer exported
    expect($content)->not->toContain('D:\\PVSI\\AJTFLU');
    expect($content)->not->toContain('D:\\_raiz_qbck');
});

it('data endpoint returns per-file hashes and columnas', function () {
    createTrazabilidadData();
    HashArchivoLote::create([
        'sucursal' => 'CALVO', 'ip' => '192.168.1.100', 'nombre_carpeta' => 'PVSI',
        'ruta_base' => 'D:\\PVSI', 'fecha_envio' => now(),
        'disparador' => 'cortefin', 'num_archivos' => 1, 'peso_total' => 0,
        'estado' => 'exitoso', 'payload' => '{}',
    ]);
    HashArchivoLote::create([
        'sucursal' => 'CALVO', 'ip' => '192.168.1.100', 'nombre_carpeta' => 'calvo',
        'ruta_base' => 'D:\\_raiz_qbck\\cre\\chetu\\calvo', 'fecha_envio' => now(),
        'disparador' => 'rbf', 'num_archivos' => 1, 'peso_total' => 0,
        'estado' => 'exitoso', 'payload' => '{}',
    ]);

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.data'));

    $response->assertOk();
    $json = $response->json();

    expect($json['columnas'])->toBe(['cortefin/pvsi', 'quickbck', 'rbf']);

    $row = collect($json['data'])->first(fn ($r) => $r['short_key'] === 'CALVO');
    expect($row)->not->toBeNull();
    expect($row['estado'])->toBe('online');
    expect($row['archivo'])->toBe('AJTFLU.DBF');
    expect($row['hashes'])->toHaveKeys(['cortefin/pvsi', 'quickbck', 'rbf']);

    expect($row['hashes']['cortefin/pvsi']['hash'])->toBe('99999');
    expect($row['hashes']['cortefin/pvsi']['path'])->toBe('D:\\PVSI\\AJTFLU.DBF');
    expect($row['hashes']['rbf']['hash'])->toBe('231B7');
    expect($row['hashes']['rbf']['path'])->toBe('D:\\_raiz_qbck\\cre\\chetu\\calvo\\AJTFLU.DBF');

    expect($row['fecha_modificacion'])->not->toBeNull();
    expect($row['rutas'])->toBeArray();
    $dispLabels = collect($row['rutas'])->pluck('disparador')->all();
    expect($dispLabels)->toContain('cortefin/pvsi');
    expect($dispLabels)->toContain('rbf');
    $rbfRuta = collect($row['rutas'])->first(fn ($r) => $r['disparador'] === 'rbf');
    expect($rbfRuta['ruta'])->toBe('D:\\_raiz_qbck\\cre\\chetu\\calvo\\AJTFLU.DBF');
    expect($rbfRuta['fecha_modificacion'])->not->toBeNull();
});

it('data endpoint filters by archivo and estado', function () {
    createTrazabilidadData();

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.data', [
        'estado' => 'offline',
    ]));
    expect(collect($response->json('data'))->first(fn ($r) => $r['short_key'] === 'CALVO'))->toBeNull();

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.data', [
        'estado' => 'online',
        'archivo' => 'AJTFLU.DBF',
    ]));
    $row = collect($response->json('data'))->first(fn ($r) => $r['short_key'] === 'CALVO');
    expect($row)->not->toBeNull();
    expect($row['archivo'])->toBe('AJTFLU.DBF');
});

it('data endpoint returns the last 5 hashes per point from the materialized historial', function () {
    createTrazabilidadData();

    // 7 registros historicos para 'rbf' (>= 5 para probar el corte) y 2 para 'pvsi'
    $historico = ['11111111111111111111111111111111', '22222222222222222222222222222222', '33333333333333333333333333333333', '44444444444444444444444444444444', '55555555555555555555555555555555', '66666666666666666666666666666666', '77777777777777777777777777777777'];
    foreach ($historico as $i => $md5) {
        HashArchivoHistorial::create([
            'sucursal' => 'CALVO', 'ip' => '192.168.1.100', 'archivo' => 'AJTFLU.DBF',
            'md5' => substr($md5, -5), 'md5_completo' => $md5, 'disparador' => 'rbf',
            'fecha_modificacion' => "2026-08-0{$i}T10:00:00Z", 'fecha_consulta_api' => "2026-08-0{$i}T10:00:00Z",
        ]);
    }

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.data'));

    $response->assertOk();
    $row = collect($response->json('data'))->first(fn ($r) => $r['short_key'] === 'CALVO');
    $rbfRuta = collect($row['rutas'])->first(fn ($r) => $r['disparador'] === 'rbf');

    expect($rbfRuta['historial'])->toHaveCount(5);
    // Ordenado por instante desc: los 5 mas recientes del historial
    expect(collect($rbfRuta['historial'])->pluck('hash')->all())
        ->toBe(array_slice(array_map(fn ($h) => substr($h, -5), array_reverse($historico)), 0, 5));
    expect($rbfRuta['historial'][0]['disparador'])->toBe('rbf');
    expect($rbfRuta['historial'][0]['fecha_consulta_api'])->toBe('2026-08-06T10:00:00Z');
    expect($rbfRuta['historial'][0]['fecha_modificacion'])->toBe('2026-08-06T10:00:00Z');
});

it('data endpoint dedupes repeated hashes in the historial per point', function () {
    createTrazabilidadData();

    // 7 registros, pero todos con el mismo md5 repetido -> debe devolver solo 1
    foreach ([1, 2, 3, 4, 5, 6, 7] as $i) {
        HashArchivoHistorial::create([
            'sucursal' => 'CALVO', 'ip' => '192.168.1.100', 'archivo' => 'AJTFLU.DBF',
            'md5' => 'ABCDE', 'md5_completo' => 'aaaa1111aaaa1111aaaa1111aaaa1111', 'disparador' => 'rbf',
            'fecha_modificacion' => "2026-08-0{$i}T10:00:00Z", 'fecha_consulta_api' => "2026-08-0{$i}T10:00:00Z",
        ]);
    }

    $response = $this->actingAs($this->user)->get(route('reportes.trazabilidad.data'));

    $response->assertOk();
    $row = collect($response->json('data'))->first(fn ($r) => $r['short_key'] === 'CALVO');
    $rbfRuta = collect($row['rutas'])->first(fn ($r) => $r['disparador'] === 'rbf');

    expect($rbfRuta['historial'])->toHaveCount(1);
    expect($rbfRuta['historial'][0]['hash'])->toBe('a1111');
});
