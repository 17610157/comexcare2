<?php

use App\Jobs\SyncRbfFileHashesJob;
use App\Models\RbfPlazaTimeConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin.ver', 'rbf-plaza-time.ver', 'rbf-plaza-time.crear', 'rbf-plaza-time.editar', 'rbf-plaza-time.eliminar', 'rbf-plaza-time.sincronizar'] as $permiso) {
        Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo(['admin.ver', 'rbf-plaza-time.ver', 'rbf-plaza-time.crear', 'rbf-plaza-time.editar', 'rbf-plaza-time.eliminar', 'rbf-plaza-time.sincronizar']);
});

it('returns the index page for authenticated user with permission', function () {
    $response = $this->actingAs($this->user)->get(route('admin.rbf-plaza-time-configs.index'));

    $response->assertOk();
    $response->assertSee('Horarios por Plaza RBF');
});

it('returns 403 for user without permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.rbf-plaza-time-configs.index'));

    $response->assertForbidden();
});

it('stores a new configuration normalizing plaza to lowercase', function () {
    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'MATRO',
        'meridiano' => 6,
        'zona_horaria' => -1,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    expect(RbfPlazaTimeConfig::count())->toBe(1);

    $config = RbfPlazaTimeConfig::first();
    expect($config->plaza)->toBe('matro');
    expect($config->meridiano)->toBe(6);
    expect($config->zona_horaria)->toBe(-1);
});

it('rejects creating a configuration for an existing plaza', function () {
    RbfPlazaTimeConfig::create(['plaza' => 'matro', 'meridiano' => 7, 'zona_horaria' => 0]);

    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'matro',
        'meridiano' => 6,
        'zona_horaria' => 1,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('plaza');

    expect(RbfPlazaTimeConfig::count())->toBe(1);
    expect(RbfPlazaTimeConfig::first()->meridiano)->toBe(7);
});

it('rejects duplicate plazas with different case', function () {
    RbfPlazaTimeConfig::create(['plaza' => 'matro', 'meridiano' => 6, 'zona_horaria' => 0]);

    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'MATRO',
        'meridiano' => 7,
        'zona_horaria' => 1,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('plaza');
});

it('validates meridiano and zona horaria ranges', function () {
    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'matro',
        'meridiano' => 30,
        'zona_horaria' => -50,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['meridiano', 'zona_horaria']);
});

it('requires all fields', function () {
    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['plaza', 'meridiano', 'zona_horaria']);
});

it('updates a configuration via put', function () {
    $config = RbfPlazaTimeConfig::create(['plaza' => 'matro', 'meridiano' => 6, 'zona_horaria' => 0]);

    $response = $this->actingAs($this->user)->putJson(route('admin.rbf-plaza-time-configs.update', $config), [
        'plaza' => 'matro',
        'meridiano' => 7,
        'zona_horaria' => -1,
    ]);

    $response->assertOk();

    $config->refresh();
    expect($config->meridiano)->toBe(7);
    expect($config->zona_horaria)->toBe(-1);
});

it('returns data for the datatable with computed total hours', function () {
    RbfPlazaTimeConfig::create(['plaza' => 'matro', 'meridiano' => 6, 'zona_horaria' => -1]);
    RbfPlazaTimeConfig::create(['plaza' => 'bajac', 'meridiano' => 5, 'zona_horaria' => 1]);

    $response = $this->actingAs($this->user)->getJson(route('admin.rbf-plaza-time-configs.data'));

    $response->assertOk();
    $json = $response->json();

    expect($json['recordsTotal'])->toBe(2);
    expect($json['data'])->toHaveCount(2);
    expect($json['data'][0]['total_horas'])->toBe('-7 h');
    expect($json['data'][1]['total_horas'])->toBe('-4 h');
});

it('destroys a configuration', function () {
    $config = RbfPlazaTimeConfig::create(['plaza' => 'matro', 'meridiano' => 6, 'zona_horaria' => 0]);

    $response = $this->actingAs($this->user)->deleteJson(route('admin.rbf-plaza-time-configs.destroy', $config));

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect(RbfPlazaTimeConfig::count())->toBe(0);
});

it('blocks store without create permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.ver', 'rbf-plaza-time.ver']);

    $response = $this->actingAs($user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'matro',
        'meridiano' => 6,
        'zona_horaria' => 0,
    ]);

    $response->assertForbidden();
});

it('dispatches sync job when forzar_sync is sent on store', function () {
    Queue::fake();

    $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'matro',
        'meridiano' => 6,
        'zona_horaria' => -1,
        'forzar_sync' => 1,
    ]);

    Queue::assertPushed(SyncRbfFileHashesJob::class, fn (SyncRbfFileHashesJob $job) => true);
});

it('does not dispatch sync job without forzar_sync on store', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.store'), [
        'plaza' => 'matro',
        'meridiano' => 6,
        'zona_horaria' => -1,
    ]);

    $response->assertOk();
    Queue::assertNotPushed(SyncRbfFileHashesJob::class);
});

it('dispatches sync job when forzar_sync is sent on update', function () {
    Queue::fake();
    $config = RbfPlazaTimeConfig::create(['plaza' => 'matro', 'meridiano' => 6, 'zona_horaria' => 0]);

    $this->actingAs($this->user)->putJson(route('admin.rbf-plaza-time-configs.update', $config), [
        'plaza' => 'matro',
        'meridiano' => 7,
        'zona_horaria' => -1,
        'forzar_sync' => 1,
    ]);

    Queue::assertPushed(SyncRbfFileHashesJob::class);
});

it('queues a forced sync from the dedicated endpoint', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('admin.rbf-plaza-time-configs.sincronizar'));

    $response->assertOk();
    $response->assertJson(['success' => true]);
    Queue::assertPushed(SyncRbfFileHashesJob::class);
});

it('blocks forced sync endpoint without permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['admin.ver', 'rbf-plaza-time.ver']);

    $response = $this->actingAs($user)->postJson(route('admin.rbf-plaza-time-configs.sincronizar'));

    $response->assertForbidden();
});
