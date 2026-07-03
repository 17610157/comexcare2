<?php

use App\Models\Computer;
use App\Models\Group;
use App\Models\GroupShortKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! is_writable(storage_path('framework/views'))) {
        $this->markTestSkipped('Storage views directory is not writable');
    }

    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->givePermissionTo('admin.ver');
    $this->actingAs($admin);
});

it('displays the groups index page', function () {
    Group::factory()->count(3)->create();

    $response = $this->get(route('admin.groups.index'));

    $response->assertStatus(200);
    $response->assertViewIs('admin.groups.index');
    $response->assertViewHas('groups');
});

it('deletes a group with its short keys', function () {
    $group = Group::factory()->create();
    GroupShortKey::create(['group_id' => $group->id, 'short_key' => 'KEY01']);
    GroupShortKey::create(['group_id' => $group->id, 'short_key' => 'KEY02']);

    $response = $this->delete(route('admin.groups.destroy', $group));

    $response->assertRedirect(route('admin.groups.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    $this->assertDatabaseMissing('group_short_keys', ['group_id' => $group->id]);
});

it('sets computer group_id to null when group is deleted', function () {
    $group = Group::factory()->create();
    $computer = Computer::factory()->create(['group_id' => $group->id]);

    $this->delete(route('admin.groups.destroy', $group));

    $this->assertDatabaseHas('computers', [
        'id' => $computer->id,
        'group_id' => null,
    ]);
});

it('returns 404 when deleting non-existent group', function () {
    $response = $this->delete(route('admin.groups.destroy', 99999));

    $response->assertNotFound();
});

it('requires authentication to delete', function () {
    Auth::logout();

    $group = Group::factory()->create();

    $response = $this->delete(route('admin.groups.destroy', $group));

    $response->assertForbidden();
});
