<?php

use App\Models\ConciliacionHashArchivo;
use App\Models\HashArchivoLote;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('services.conciliacion.hash_archivos_api_key', 'test-api-key');
    Config::set('services.conciliacion.hash_archivos_rate_limit', 1000);
    Config::set('services.conciliacion.hash_archivos_max_lote_bytes', 10 * 1024 * 1024);
});

function validTienda(array $overrides = []): array
{
    return array_merge([
        'NombreCarpeta' => 'centr',
        'RutaBase' => 'D:\\dbf\\cre\\centr',
        'FechaEnvio' => '2026-08-06T10:30:00Z',
        'Disparador' => 'serv',
        'Sucursal' => 'cre',
        'Archivos' => [
            [
                'Nombre' => 'VALES.DBF',
                'Existe' => true,
                'Md5' => 'd41d8cd98f00b204e9800998ecf8427e',
                'Peso' => 123456,
                'FechaModificacion' => '2026-08-05T14:22:11',
            ],
        ],
    ], $overrides);
}

it('requiere el header X-API-Key', function () {
    $this->postJson('/api/hash-archivos/registrar-lote', ['Tiendas' => [validTienda()]])
        ->assertStatus(401);
});

it('rechaza una X-API-Key inválida', function () {
    $this->postJson('/api/hash-archivos/registrar-lote', ['Tiendas' => [validTienda()]], ['X-API-Key' => 'wrong-key'])
        ->assertStatus(401);
});

it('registra un lote válido y puebla conciliacion_hash_archivos', function () {
    $payload = [
        'Tiendas' => [
            validTienda(),
            validTienda([
                'NombreCarpeta' => 'cforj',
                'RutaBase' => 'D:\\dbf\\cre\\cforj',
                'FechaEnvio' => '2026-08-06T10:30:01Z',
                'Sucursal' => 'cre',
                'Archivos' => [],
            ]),
        ],
    ];

    $response = $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'tiendas' => 2,
            'archivos' => 1,
        ]);

    $lote = HashArchivoLote::first();
    expect($lote)->not->toBeNull();
    expect($lote->estado)->toBe('exitoso');
    expect($lote->num_archivos)->toBe(1);
    expect($lote->peso_total)->toBe(123456);
    expect($lote->sucursal)->toBe('cre');
    expect($lote->cliente)->toBe('test***-key');

    $record = ConciliacionHashArchivo::where('archivo', 'VALES.DBF')->first();
    expect($record)->not->toBeNull();
    expect($record->sucursal)->toBe('cre');
    expect($record->disparador)->toBe('serv');
    expect($record->md5)->toBe('8427e');
    expect($record->md5_completo)->toBe('d41d8cd98f00b204e9800998ecf8427e');
    expect($record->fecha_modificacion->format('Y-m-d H:i:s'))->toBe('2026-08-05 14:22:11');
});

it('acepta un lote con Tiendas vacío', function () {
    $response = $this->postJson('/api/hash-archivos/registrar-lote', ['Tiendas' => []], ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(200);
    expect(HashArchivoLote::count())->toBe(1);
    expect(HashArchivoLote::first()->estado)->toBe('exitoso');
});

it('rechaza JSON inválido con 422 y guarda el trazo como error', function () {
    $this->post('/api/hash-archivos/registrar-lote', [], [
        'X-API-Key' => 'test-api-key',
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ])->assertStatus(422);

    expect(HashArchivoLote::first()->estado)->toBe('error_validacion');
});

it('rechaza un Md5 que no es hexadecimal de 32', function () {
    $payload = [
        'Tiendas' => [
            validTienda(['Archivos' => [
                [
                    'Nombre' => 'VALES.DBF',
                    'Existe' => true,
                    'Md5' => 'zzz',
                    'Peso' => 10,
                    'FechaModificacion' => '2026-08-05T14:22:11',
                ],
            ]]),
        ],
    ];

    $response = $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(422);
    expect(array_key_exists('0.Archivos.0.Md5', $response->json('errors')))->toBeTrue();
    expect(ConciliacionHashArchivo::count())->toBe(0);
    expect(HashArchivoLote::first()->estado)->toBe('error_validacion');
});

it('rechaza un Md5 faltante cuando Existe es true', function () {
    $payload = [
        'Tiendas' => [
            validTienda(['Archivos' => [
                [
                    'Nombre' => 'VALES.DBF',
                    'Existe' => true,
                    'Peso' => 10,
                    'FechaModificacion' => '2026-08-05T14:22:11',
                ],
            ]]),
        ],
    ];

    $response = $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(422);
    expect(array_key_exists('0.Archivos.0.Md5', $response->json('errors')))->toBeTrue();
});

it('acepta archivos con Existe false, sin Md5, y no los guarda', function () {
    $payload = [
        'Tiendas' => [
            validTienda([
                'Archivos' => [
                    [
                        'Nombre' => 'VALES.DBF',
                        'Existe' => true,
                        'Md5' => 'd41d8cd98f00b204e9800998ecf8427e',
                        'Peso' => 123456,
                        'FechaModificacion' => '2026-08-05T14:22:11',
                    ],
                    [
                        'Nombre' => 'CANOTA.DBT',
                        'Existe' => false,
                        'Peso' => 0,
                        'FechaModificacion' => '0001-01-01T00:00:00',
                    ],
                ],
            ]),
        ],
    ];

    $response = $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(200);
    expect($response->json('archivos'))->toBe(2);
    expect(ConciliacionHashArchivo::count())->toBe(1);
    expect(ConciliacionHashArchivo::where('archivo', 'CANOTA.DBT')->count())->toBe(0);
});

it('acepta FechaModificacion con offset de zona horaria y decimales', function () {
    $payload = [
        'Tiendas' => [
            validTienda(['Archivos' => [
                [
                    'Nombre' => 'VALES.DBF',
                    'Existe' => true,
                    'Md5' => 'd41d8cd98f00b204e9800998ecf8427e',
                    'Peso' => 123456,
                    'FechaModificacion' => '2026-07-14T13:28:23.3065014-06:00',
                ],
            ]]),
        ],
    ];

    $response = $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(200);
    $record = ConciliacionHashArchivo::where('archivo', 'VALES.DBF')->first();
    expect($record->fecha_modificacion->format('Y-m-d H:i:s'))->toBe('2026-07-14 13:28:23');
});

it('rechaza campos obligatorios faltantes', function () {
    $payload = ['Tiendas' => [['NombreCarpeta' => 'centr']]];

    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])
        ->assertStatus(422);
});

it('rechaza un cuerpo mayor al tamaño máximo', function () {
    Config::set('services.conciliacion.hash_archivos_max_lote_bytes', 1024);

    $payload = ['Tiendas' => [validTienda(['RutaBase' => str_repeat('x', 2048)])]];

    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])
        ->assertStatus(413);
});

it('aplica rate limit con mensaje Reintente en X segundos', function () {
    Config::set('services.conciliacion.hash_archivos_rate_limit', 3);

    $payload = ['Tiendas' => [validTienda()]];

    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])->assertStatus(200);
    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])->assertStatus(200);
    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])->assertStatus(200);

    $response = $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(429);
    expect($response->getContent())->toContain('Reintente en');
});

it('acepta una tienda suelta en registrar', function () {
    $response = $this->postJson('/api/hash-archivos/registrar', validTienda(), ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(200);
    expect(ConciliacionHashArchivo::count())->toBe(1);
});

it('acepta un wrapper Tiendas en registrar', function () {
    $response = $this->postJson('/api/hash-archivos/registrar', ['Tiendas' => [validTienda()]], ['X-API-Key' => 'test-api-key']);

    $response->assertStatus(200);
    expect(ConciliacionHashArchivo::count())->toBe(1);
});

it('actualiza un registro existente en vez de duplicarlo', function () {
    $payload = ['Tiendas' => [validTienda()]];

    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])->assertStatus(200);
    $this->postJson('/api/hash-archivos/registrar-lote', $payload, ['X-API-Key' => 'test-api-key'])->assertStatus(200);

    expect(ConciliacionHashArchivo::count())->toBe(1);
    expect(HashArchivoLote::count())->toBe(2);
});
