<?php

use App\Models\Computer;
use App\Models\XcorteApi;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.cortes.api_key_agente', 'test-agente-key');
    Config::set('services.cortes.api_key_app', 'test-app-key');
    Config::set('services.cortes.rate_limit', 1000);
});

function cortePayload(array $overrides = []): array
{
    return array_merge([
        'fecha_corte' => '2026-10-07',
        'clave_tienda' => '00021',
        'monto_contado' => 1234.56,
        'monto_credito' => 789.10,
    ], $overrides);
}

it('rechaza una peticion sin api key', function () {
    $this->postJson('/api/cortes', cortePayload())->assertUnauthorized();
});

it('rechaza una api key invalida', function () {
    $this->withHeader('X-API-Key', 'clave-mala')
        ->postJson('/api/cortes', cortePayload())
        ->assertUnauthorized();
});

it('registra un corte individual desde el agente y deriva la plaza', function () {
    $computer = Computer::factory()->create([
        'short_key' => '00021',
        'plaza' => 'BAJAC',
    ]);

    $response = $this->withHeader('X-API-Key', 'test-agente-key')
        ->postJson('/api/cortes', cortePayload());

    $response->assertCreated()
        ->assertJsonPath('accion', 'creado')
        ->assertJsonPath('corte.plaza', 'BAJAC')
        ->assertJsonPath('corte.computer_id', $computer->id);

    $this->assertDatabaseHas('xcorte_api', [
        'fecha_corte' => '2026-10-07',
        'clave_tienda' => '00021',
        'plaza' => 'BAJAC',
        'computer_id' => $computer->id,
    ]);
});

it('registra un corte desde la aplicacion sin plaza', function () {
    $response = $this->withHeader('X-API-Key', 'test-app-key')
        ->postJson('/api/cortes', cortePayload());

    $response->assertCreated()
        ->assertJsonPath('corte.plaza', null)
        ->assertJsonPath('corte.computer_id', null);

    $this->assertDatabaseHas('xcorte_api', [
        'clave_tienda' => '00021',
        'plaza' => null,
    ]);
});

it('actualiza un corte existente con upsert', function () {
    $this->withHeader('X-API-Key', 'test-agente-key')
        ->postJson('/api/cortes', cortePayload())
        ->assertCreated();

    $response = $this->withHeader('X-API-Key', 'test-agente-key')
        ->postJson('/api/cortes', cortePayload(['monto_contado' => 5000.00]));

    $response->assertOk()
        ->assertJsonPath('accion', 'actualizado');

    expect(XcorteApi::query()->count())->toBe(1);
    $this->assertDatabaseHas('xcorte_api', [
        'clave_tienda' => '00021',
        'monto_contado' => 5000.00000,
    ]);
});

it('no sobrescribe la plaza del agente cuando registra la aplicacion', function () {
    Computer::factory()->create(['short_key' => '00021', 'plaza' => 'BAJAC']);

    $this->withHeader('X-API-Key', 'test-agente-key')
        ->postJson('/api/cortes', cortePayload())
        ->assertCreated();

    $this->withHeader('X-API-Key', 'test-app-key')
        ->postJson('/api/cortes', cortePayload(['monto_credito' => 999.99]))
        ->assertOk();

    $this->assertDatabaseHas('xcorte_api', [
        'clave_tienda' => '00021',
        'plaza' => 'BAJAC',
        'monto_credito' => 999.99000,
    ]);
});

it('registra un lote contando creados y actualizados', function () {
    XcorteApi::query()->create([
        'fecha_corte' => '2026-10-07',
        'clave_tienda' => '00021',
        'monto_contado' => 1,
        'monto_credito' => 1,
    ]);

    $response = $this->withHeader('X-API-Key', 'test-agente-key')
        ->postJson('/api/cortes/lote', [
            'cortes' => [
                cortePayload(),
                cortePayload(['clave_tienda' => '00022']),
            ],
        ]);

    $response->assertCreated()
        ->assertJsonPath('created_count', 1)
        ->assertJsonPath('updated_count', 1)
        ->assertJsonPath('error_count', 0);

    expect(XcorteApi::query()->count())->toBe(2);
});

it('valida los campos requeridos', function () {
    $response = $this->withHeader('X-API-Key', 'test-agente-key')
        ->postJson('/api/cortes', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['fecha_corte', 'clave_tienda', 'monto_contado', 'monto_credito']);
});

it('lista los cortes con filtros por fecha y tienda', function () {
    XcorteApi::query()->create([
        'fecha_corte' => '2026-10-06',
        'clave_tienda' => '00021',
        'monto_contado' => 10,
        'monto_credito' => 10,
    ]);
    XcorteApi::query()->create([
        'fecha_corte' => '2026-10-07',
        'clave_tienda' => '00022',
        'monto_contado' => 20,
        'monto_credito' => 20,
    ]);

    $this->withHeader('X-API-Key', 'test-agente-key')
        ->getJson('/api/cortes?clave_tienda=00022')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.clave_tienda', '00022');

    $this->withHeader('X-API-Key', 'test-agente-key')
        ->getJson('/api/cortes?fecha_inicio=2026-10-06&fecha_fin=2026-10-06')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.fecha_corte', '2026-10-06');
});
