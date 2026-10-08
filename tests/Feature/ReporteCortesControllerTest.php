<?php

use App\Models\User;
use App\Models\XcorteApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'cortes.ver', 'guard_name' => 'web']);

    $this->user = User::factory()->create();
    $this->user->givePermissionTo('cortes.ver');
});

function crearCorte(array $overrides = []): XcorteApi
{
    return XcorteApi::query()->create(array_merge([
        'fecha_corte' => '2026-10-07',
        'clave_tienda' => '00021',
        'monto_contado' => 1000,
        'monto_credito' => 500,
        'plaza' => 'BAJAC',
    ], $overrides));
}

it('niega el acceso sin el permiso', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('reportes.cortes'))->assertForbidden();
    $this->actingAs($user)->getJson(route('reportes.cortes.data'))->assertForbidden();
});

it('muestra el modulo con el permiso', function () {
    $this->actingAs($this->user)
        ->get(route('reportes.cortes'))
        ->assertOk()
        ->assertSee('Cortes');
});

it('lista los cortes con la estructura de datatables', function () {
    crearCorte();

    $response = $this->actingAs($this->user)
        ->getJson(route('reportes.cortes.data'))
        ->assertOk()
        ->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                ['id', 'fecha_corte', 'clave_tienda', 'plaza', 'monto_contado', 'monto_credito', 'total', 'fecha_registro'],
            ],
        ]);

    expect($response->json('recordsTotal'))->toBe(1)
        ->and($response->json('data.0.clave_tienda'))->toBe('00021')
        ->and($response->json('data.0.total'))->toBe('1500.00');
});

it('filtra por plaza', function () {
    crearCorte(['clave_tienda' => '00021', 'plaza' => 'BAJAC']);
    crearCorte(['clave_tienda' => '00022', 'plaza' => 'CHETU']);

    $response = $this->actingAs($this->user)
        ->getJson(route('reportes.cortes.data', ['plaza' => ['BAJAC']]));

    expect($response->json('recordsTotal'))->toBe(1)
        ->and($response->json('data.0.plaza'))->toBe('BAJAC');
});

it('filtra por tienda', function () {
    crearCorte(['clave_tienda' => '00021']);
    crearCorte(['clave_tienda' => '00022']);

    $response = $this->actingAs($this->user)
        ->getJson(route('reportes.cortes.data', ['tienda' => ['00022']]));

    expect($response->json('recordsTotal'))->toBe(1)
        ->and($response->json('data.0.clave_tienda'))->toBe('00022');
});

it('filtra por rango de fechas', function () {
    crearCorte(['fecha_corte' => '2026-10-01', 'clave_tienda' => '00021']);
    crearCorte(['fecha_corte' => '2026-10-05', 'clave_tienda' => '00022']);
    crearCorte(['fecha_corte' => '2026-10-10', 'clave_tienda' => '00023']);

    $response = $this->actingAs($this->user)
        ->getJson(route('reportes.cortes.data', [
            'fecha_desde' => '2026-10-02',
            'fecha_hasta' => '2026-10-06',
        ]));

    expect($response->json('recordsTotal'))->toBe(1)
        ->and($response->json('data.0.clave_tienda'))->toBe('00022');
});

it('pagina los resultados', function () {
    crearCorte(['clave_tienda' => '00021', 'fecha_corte' => '2026-10-01']);
    crearCorte(['clave_tienda' => '00022', 'fecha_corte' => '2026-10-02']);
    crearCorte(['clave_tienda' => '00023', 'fecha_corte' => '2026-10-03']);

    $response = $this->actingAs($this->user)
        ->getJson(route('reportes.cortes.data', ['length' => 1, 'start' => 1]));

    expect($response->json('recordsTotal'))->toBe(3)
        ->and($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.clave_tienda'))->toBe('00022');
});
