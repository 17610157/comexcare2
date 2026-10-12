<?php

use App\Mail\CortesFaltantesPorPlaza;
use App\Models\Computer;
use App\Models\CortePlazaEmailConfig;
use App\Models\User;
use App\Models\XcorteApi;
use App\Services\CortesFaltantesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function crearCorteNotif(array $overrides = []): XcorteApi
{
    return XcorteApi::create(array_merge([
        'fecha_corte' => now()->subDays(5)->toDateString(),
        'clave_tienda' => 'ST1',
        'monto_contado' => 100,
        'monto_credito' => 50,
        'plaza' => 'BAJAC',
        'fecha_registro' => now()->subDays(5),
    ], $overrides));
}

beforeEach(function () {
    foreach ([
        'admin.ver',
        'cortes.ver', 'cortes.enviar',
        'cortes-notificaciones.ver', 'cortes-notificaciones.crear',
        'cortes-notificaciones.editar', 'cortes-notificaciones.eliminar',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo([
        'admin.ver',
        'cortes.ver', 'cortes.enviar',
        'cortes-notificaciones.ver', 'cortes-notificaciones.crear',
        'cortes-notificaciones.editar', 'cortes-notificaciones.eliminar',
    ]);

    $this->dia = now()->startOfDay();
});

it('clasifica tiendas sin corte, de la regla y agentes apagados por plaza', function () {
    Computer::factory()->create(['short_key' => 'ST1', 'plaza' => 'BAJAC', 'last_seen' => now()]);
    Computer::factory()->create(['short_key' => 'ST2', 'plaza' => 'BAJAC', 'last_seen' => now()]);
    Computer::factory()->create(['short_key' => 'ST3', 'plaza' => 'CHETU', 'last_seen' => now()->subHour()]);

    // ST1: sin corte del día (agente en línea).
    crearCorteNotif(['clave_tienda' => 'ST1', 'plaza' => 'BAJAC']);

    // ST2: tiene corte del día pero registrado otro día (regla).
    crearCorteNotif(['clave_tienda' => 'ST2', 'plaza' => 'BAJAC', 'fecha_corte' => $this->dia->toDateString()]);
    XcorteApi::query()
        ->where('clave_tienda', 'ST2')
        ->whereDate('fecha_corte', $this->dia->toDateString())
        ->update(['fecha_registro' => $this->dia->copy()->addDay()->setTime(9, 0)]);

    // ST3: sin corte y agente apagado (CHETU).
    crearCorteNotif(['clave_tienda' => 'ST3', 'plaza' => 'CHETU']);

    $data = app(CortesFaltantesService::class)->porPlaza($this->dia);

    expect($data->keys()->all())->toContain('BAJAC', 'CHETU')
        ->and(collect($data['BAJAC']['sin_corte'])->pluck('clave_tienda'))->toContain('ST1')
        ->and(collect($data['BAJAC']['sin_corte'])->firstWhere('clave_tienda', 'ST1')['motivo'])->toBe('Sin corte')
        ->and(collect($data['BAJAC']['regla'])->pluck('clave_tienda'))->toContain('ST2')
        ->and(collect($data['CHETU']['sin_corte'])->firstWhere('clave_tienda', 'ST3')['motivo'])->toBe('Agente apagado');
});

it('omite las tiendas sin plaza', function () {
    crearCorteNotif(['clave_tienda' => 'SINP', 'plaza' => '']);
    crearCorteNotif(['clave_tienda' => 'CONP', 'plaza' => 'BAJAC']);

    $plazas = app(CortesFaltantesService::class)->plazasDisponibles();

    expect($plazas->all())->not->toContain('')
        ->and($plazas->all())->toContain('BAJAC');
});

it('envía el correo manual incluyendo destinatarios fijos y de la plaza', function () {
    Mail::fake();

    Computer::factory()->create(['short_key' => 'ST1', 'plaza' => 'BAJAC', 'last_seen' => now()]);
    crearCorteNotif(['clave_tienda' => 'ST1', 'plaza' => 'BAJAC']);
    CortePlazaEmailConfig::create([
        'plaza' => 'BAJAC',
        'emails' => ['extra@ejemplo.com'],
        'envio_activo' => false,
        'hora_envio' => 7,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson(route('reportes.cortes.notificaciones.enviar'), [
            'plaza' => 'BAJAC',
            'fecha' => $this->dia->toDateString(),
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('result.sin_corte'))->toBe(1);

    Mail::assertQueued(CortesFaltantesPorPlaza::class, function (CortesFaltantesPorPlaza $mail) {
        return $mail->plaza === 'BAJAC'
            && in_array('jorgerivera@camposreyeros.com', $mail->destinatarios, true)
            && in_array('bryan.vazquez@camposreyeros.com', $mail->destinatarios, true)
            && in_array('extra@ejemplo.com', $mail->destinatarios, true);
    });

    expect(CortePlazaEmailConfig::where('plaza', 'BAJAC')->value('ultimo_envio_at'))->not->toBeNull();
});

it('ejecuta el envío automático una sola vez al día', function () {
    Mail::fake();

    Computer::factory()->create(['short_key' => 'ST1', 'plaza' => 'BAJAC', 'last_seen' => now()]);
    crearCorteNotif(['clave_tienda' => 'ST1', 'plaza' => 'BAJAC']);
    CortePlazaEmailConfig::create([
        'plaza' => 'BAJAC',
        'emails' => [],
        'envio_activo' => true,
        'hora_envio' => now()->hour,
    ]);

    $this->artisan('cortes:enviar-faltantes')->assertSuccessful();
    Mail::assertQueued(CortesFaltantesPorPlaza::class, 1);

    $this->artisan('cortes:enviar-faltantes')->assertSuccessful();
    Mail::assertQueued(CortesFaltantesPorPlaza::class, 1);
});

it('niega el envío manual sin el permiso cortes.enviar', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cortes.ver');

    $this->actingAs($user)
        ->postJson(route('reportes.cortes.notificaciones.enviar'), ['plaza' => 'BAJAC'])
        ->assertForbidden();
});

it('guarda configuración de notificación con correos separados por coma', function () {
    $this->actingAs($this->user)
        ->postJson(route('admin.cortes-notificaciones.store'), [
            'plaza' => 'BAJAC',
            'emails' => 'uno@ejemplo.com, dos@ejemplo.com',
            'hora_envio' => 8,
            'envio_activo' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $config = CortePlazaEmailConfig::where('plaza', 'BAJAC')->first();

    expect($config->emails)->toBe(['uno@ejemplo.com', 'dos@ejemplo.com'])
        ->and($config->hora_envio)->toBe(8)
        ->and($config->envio_activo)->toBeTrue();
});

it('activa y programa el envío automático desde el reporte', function () {
    $this->actingAs($this->user)
        ->postJson(route('reportes.cortes.notificaciones.automatico'), [
            'plaza' => 'BAJAC',
            'envio_activo' => 1,
            'hora_envio' => 9,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $config = CortePlazaEmailConfig::where('plaza', 'BAJAC')->first();

    expect($config->envio_activo)->toBeTrue()
        ->and($config->hora_envio)->toBe(9);
});

it('no marca como faltante el corte del domingo registrado el sábado con venta 0', function () {
    crearCorteNotif([
        'clave_tienda' => 'S1',
        'plaza' => 'BAJAC',
        'fecha_corte' => '2026-10-11',
        'monto_contado' => 0,
        'monto_credito' => 0,
        'fecha_registro' => Carbon::parse('2026-10-10 22:00'),
    ]);

    $data = app(CortesFaltantesService::class)->porPlaza(Carbon::parse('2026-10-11'));

    expect(collect($data['BAJAC']['cero'])->pluck('clave_tienda'))->toContain('S1')
        ->and(collect($data['BAJAC']['regla'])->pluck('clave_tienda'))->not->toContain('S1');
});

it('sí marca como regla el corte del domingo registrado el sábado con venta', function () {
    crearCorteNotif([
        'clave_tienda' => 'S2',
        'plaza' => 'BAJAC',
        'fecha_corte' => '2026-10-11',
        'monto_contado' => 100,
        'monto_credito' => 0,
        'fecha_registro' => Carbon::parse('2026-10-10 22:00'),
    ]);

    $data = app(CortesFaltantesService::class)->porPlaza(Carbon::parse('2026-10-11'));

    expect(collect($data['BAJAC']['regla'])->pluck('clave_tienda'))->toContain('S2')
        ->and(collect($data['BAJAC']['cero'])->pluck('clave_tienda'))->not->toContain('S2');
});
