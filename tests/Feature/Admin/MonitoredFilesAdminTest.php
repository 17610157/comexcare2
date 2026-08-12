<?php

use App\Models\Computer;
use App\Models\Group;
use App\Models\MonitoredFile;
use App\Models\User;
use Database\Seeders\DefaultMonitoredFilesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('admin.ver');

    $this->actingAs($this->admin);
});

function grantMonitoredFiles(User $user, array $perms = ['ver', 'crear', 'editar', 'eliminar']): void
{
    foreach ($perms as $perm) {
        Permission::firstOrCreate(['name' => "monitored-files.{$perm}", 'guard_name' => 'web']);
    }
    $user->givePermissionTo(array_map(fn ($p) => "monitored-files.{$p}", $perms));
}

it('displays the monitored files index page', function () {
    grantMonitoredFiles($this->admin);

    $response = $this->get(route('admin.monitored-files.index'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.monitored-files.index');
    $response->assertViewHas('records');
});

it('denies access without the monitored-files.ver permission', function () {
    $this->get(route('admin.monitored-files.index'))->assertForbidden();
});

it('creates a monitored file assigned to a group', function () {
    grantMonitoredFiles($this->admin, ['crear']);
    $group = Group::factory()->create();

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'group',
        'group_id' => $group->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => true,
        'sort_order' => 2,
    ])->assertRedirect(route('admin.monitored-files.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('monitored_files', [
        'group_id' => $group->id,
        'computer_id' => null,
        'path' => 'quickbck',
        'recursive' => 1,
        'sort_order' => 2,
    ]);

    expect(MonitoredFile::where('group_id', $group->id)->first()->file_names)->toBe(['*.DBF']);
});

it('creates a monitored file assigned to a computer with an absolute path', function () {
    grantMonitoredFiles($this->admin, ['crear']);
    $computer = Computer::factory()->create();

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'computer',
        'computer_id' => $computer->id,
        'path' => 'D:\\PVSI\\AJTFLU_RESUMEN',
        'file_names' => ['ConciliacionApp.exe'],
        'recursive' => false,
        'sort_order' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('monitored_files', [
        'group_id' => null,
        'computer_id' => $computer->id,
        'path' => 'D:\\PVSI\\AJTFLU_RESUMEN',
        'recursive' => 0,
    ]);

    expect(MonitoredFile::where('computer_id', $computer->id)->first()->file_names)->toBe(['ConciliacionApp.exe']);
});

it('creates a monitored file at the general level for all machines', function () {
    grantMonitoredFiles($this->admin, ['crear']);

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'general',
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => true,
        'sort_order' => 1,
    ])->assertRedirect(route('admin.monitored-files.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('monitored_files', [
        'general' => 1,
        'group_id' => null,
        'computer_id' => null,
        'path' => 'quickbck',
        'recursive' => 1,
        'sort_order' => 1,
    ]);

    expect(MonitoredFile::where('general', true)->first()->file_names)->toBe(['*.DBF']);
});

it('treats empty file_names as all files', function () {
    grantMonitoredFiles($this->admin, ['crear']);
    $group = Group::factory()->create();

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'group',
        'group_id' => $group->id,
        'path' => 'MODEM/ATM',
        'file_names' => [''],
    ])->assertRedirect();

    $this->assertDatabaseHas('monitored_files', [
        'group_id' => $group->id,
        'path' => 'MODEM/ATM',
    ]);

    expect(MonitoredFile::where('group_id', $group->id)->first()->file_names)->toBe([]);
});

it('creates a monitored file with multiple specific files per path', function () {
    grantMonitoredFiles($this->admin, ['crear']);
    $group = Group::factory()->create();

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'group',
        'group_id' => $group->id,
        'path' => '.',
        'file_names' => ['ConciliacionApp.exe', '*.DBF', ''],
        'recursive' => false,
    ])->assertRedirect(route('admin.monitored-files.index'))
        ->assertSessionHas('success');

    $record = MonitoredFile::where('group_id', $group->id)->first();
    expect($record->file_names)->toBe(['ConciliacionApp.exe', '*.DBF']);
});

it('validates that path is required', function () {
    grantMonitoredFiles($this->admin, ['crear']);
    $group = Group::factory()->create();

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'group',
        'group_id' => $group->id,
        'path' => '',
    ])->assertSessionHasErrors('path');

    $this->assertDatabaseCount('monitored_files', 0);
});

it('validates that a destination is required for the chosen scope', function () {
    grantMonitoredFiles($this->admin, ['crear']);

    $this->post(route('admin.monitored-files.store'), [
        'scope' => 'computer',
        'computer_id' => null,
        'path' => 'quickbck',
    ])->assertSessionHasErrors('computer_id');

    $this->assertDatabaseCount('monitored_files', 0);
});

it('updates a monitored file', function () {
    grantMonitoredFiles($this->admin, ['editar']);
    $group = Group::factory()->create();
    $record = MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => false,
        'sort_order' => 1,
    ]);

    $this->put(route('admin.monitored-files.update', $record), [
        'scope' => 'group',
        'group_id' => $group->id,
        'path' => 'quickbck',
        'file_names' => ['*.CDX'],
        'recursive' => true,
        'sort_order' => 5,
    ])->assertRedirect(route('admin.monitored-files.index'));

    $record->refresh();

    $this->assertDatabaseHas('monitored_files', [
        'id' => $record->id,
        'recursive' => 1,
        'sort_order' => 5,
    ]);

    expect($record->file_names)->toBe(['*.CDX']);
});

it('deletes a monitored file', function () {
    grantMonitoredFiles($this->admin, ['eliminar']);
    $record = MonitoredFile::create([
        'group_id' => Group::factory()->create()->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => false,
    ]);

    $this->delete(route('admin.monitored-files.destroy', $record))
        ->assertRedirect(route('admin.monitored-files.index'));

    $this->assertDatabaseMissing('monitored_files', ['id' => $record->id]);
});

it('assigns the default coverage list to a group', function () {
    grantMonitoredFiles($this->admin, ['crear']);
    $group = Group::factory()->create();

    $this->post(route('admin.monitored-files.seed-defaults'), [
        'group_id' => $group->id,
    ])->assertRedirect(route('admin.monitored-files.index'))
        ->assertSessionHas('success');

    $count = MonitoredFile::where('group_id', $group->id)->count();
    expect($count)->toBe(count(DefaultMonitoredFilesSeeder::defaultEntries()));
});

it('requires authentication to delete', function () {
    Auth::logout();

    $record = MonitoredFile::create([
        'path' => 'quickbck',
        'recursive' => false,
    ]);

    $this->delete(route('admin.monitored-files.destroy', $record))->assertForbidden();
});
