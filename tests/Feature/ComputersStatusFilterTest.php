<?php

use App\Models\Computer;
use App\Models\User;
use Spatie\Permission\Models\Permission;

uses()->beforeEach(function () {
    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.ver');
    $this->actingAs($this->admin);
});

it('filters online computers by last_seen excluding updating status', function () {
    // status=offline but last_seen is recent -> should appear as online
    Computer::factory()->create([
        'status' => 'offline',
        'last_seen' => now()->subMinutes(2),
        'plaza' => 'TEST',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status_type' => 'online',
    ]));

    $response->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonCount(1, 'data');
});

it('does not include updating computers in online filter', function () {
    Computer::factory()->create([
        'status' => 'updating',
        'last_seen' => now()->subMinutes(1),
        'plaza' => 'TEST',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status_type' => 'online',
    ]));

    $response->assertOk()
        ->assertJsonPath('recordsFiltered', 0);
});

it('filters offline computers by last_seen instead of status column', function () {
    // status=online but last_seen is old -> should appear as offline
    Computer::factory()->create([
        'status' => 'online',
        'last_seen' => now()->subMinutes(10),
        'plaza' => 'TEST',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status_type' => 'offline',
    ]));

    $response->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonCount(1, 'data');
});

it('filters updating computers with recent last_seen', function () {
    Computer::factory()->create([
        'status' => 'updating',
        'last_seen' => now()->subMinutes(1),
        'plaza' => 'TEST',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status_type' => 'updating',
    ]));

    $response->assertOk()
        ->assertJsonPath('recordsFiltered', 1)
        ->assertJsonCount(1, 'data');
});

it('does not include old last_seen computers in updating filter', function () {
    Computer::factory()->create([
        'status' => 'updating',
        'last_seen' => now()->subMinutes(10),
        'plaza' => 'TEST',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status_type' => 'updating',
    ]));

    $response->assertOk()
        ->assertJsonPath('recordsFiltered', 0);
});

it('returns updating status in response data when status column is updating and last_seen is recent', function () {
    Computer::factory()->create([
        'status' => 'updating',
        'last_seen' => now()->subMinutes(1),
        'plaza' => 'TEST',
        'short_key' => 'TST001',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
    ]));

    $response->assertOk()
        ->assertJsonPath('data.0.status', 'updating');
});

it('returns offline status for updating computer with old last_seen', function () {
    Computer::factory()->create([
        'status' => 'updating',
        'last_seen' => now()->subMinutes(10),
        'plaza' => 'TEST',
        'short_key' => 'TST002',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
    ]));

    $response->assertOk()
        ->assertJsonPath('data.0.status', 'offline');
});

it('returns online status in data when last_seen is recent regardless of status column', function () {
    Computer::factory()->create([
        'status' => 'online',
        'last_seen' => now()->subMinutes(1),
        'plaza' => 'TEST',
        'short_key' => 'TST003',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
    ]));

    $response->assertOk()
        ->assertJsonPath('data.0.status', 'online');
});

it('returns offline status for null last_seen', function () {
    Computer::factory()->create([
        'status' => 'online',
        'last_seen' => null,
        'plaza' => 'TEST',
        'short_key' => 'TST004',
    ]);

    $response = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 25,
        'status_type' => 'offline',
    ]));

    $response->assertOk()
        ->assertJsonPath('recordsFiltered', 1);
});

it('matches dashboard counting logic for plaza summary', function () {
    $threshold = now()->subMinutes(5);

    // Create computers with mixed statuses in TEST plaza
    Computer::factory()->create(['status' => 'online', 'last_seen' => now(), 'plaza' => 'TEST']);
    Computer::factory()->create(['status' => 'offline', 'last_seen' => now()->subMinutes(1), 'plaza' => 'TEST']);
    Computer::factory()->create(['status' => 'updating', 'last_seen' => now()->subMinutes(10), 'plaza' => 'TEST']);
    Computer::factory()->create(['status' => 'online', 'last_seen' => now()->subMinutes(8), 'plaza' => 'TEST']);

    // Dashboard logic: online = last_seen >= 5 min ago
    $expectedOnline = Computer::where('plaza', 'TEST')
        ->where('last_seen', '>=', $threshold)
        ->count();
    $expectedOffline = Computer::where('plaza', 'TEST')
        ->where(function ($q) use ($threshold) {
            $q->where('last_seen', '<', $threshold)->orWhereNull('last_seen');
        })
        ->count();

    // Table online filter
    $onlineResponse = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 100,
        'status_type' => 'online',
        'plaza' => 'TEST',
    ]));
    $onlineResponse->assertOk()
        ->assertJsonPath('recordsFiltered', $expectedOnline);

    // Table offline filter
    $offlineResponse = $this->getJson(route('admin.computers.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 100,
        'status_type' => 'offline',
        'plaza' => 'TEST',
    ]));
    $offlineResponse->assertOk()
        ->assertJsonPath('recordsFiltered', $expectedOffline);

    // Total should equal online + offline
    expect($expectedOnline + $expectedOffline)->toBe(4);
});
