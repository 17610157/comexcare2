<?php

use App\Models\AgentVersion;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    Storage::fake('local');
});

it('shows deploy button on index for active versions', function () {
    $version = AgentVersion::factory()->active()->create(['version' => '2.0.0']);

    $response = $this->get(route('admin.agent-versions.index'));

    $response->assertSuccessful();
    $response->assertSee('Deploy');
    $response->assertSee($version->version);
});

it('shows deploy button for inactive versions too', function () {
    $version = AgentVersion::factory()->inactive()->create(['version' => '1.0.0']);

    $response = $this->get(route('admin.agent-versions.index'));

    $response->assertSuccessful();
    $response->assertSee('Deploy');
    $response->assertSee('Activar');
});

it('activates a deactivated version', function () {
    $version = AgentVersion::factory()->inactive()->create(['version' => '1.0.0']);

    $response = $this->post(route('admin.agent-versions.activate', $version));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('agent_versions', [
        'id' => $version->id,
        'is_active' => true,
    ]);
});

it('can deploy an inactive version', function () {
    $version = AgentVersion::factory()->inactive()->create(['version' => '1.0.0']);
    $computer = Computer::factory()->create(['agent_version' => '0.9.0']);

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'all',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Actualización desplegada a 1 computadora(s).');

    $this->assertDatabaseHas('commands', [
        'computer_id' => $computer->id,
        'type' => 'update',
    ]);
});

it('deactivates a version', function () {
    $version = AgentVersion::factory()->active()->create(['version' => '2.0.0']);

    $response = $this->delete(route('admin.agent-versions.destroy', $version));

    $response->assertRedirect();

    $this->assertDatabaseHas('agent_versions', [
        'id' => $version->id,
        'is_active' => false,
    ]);
});

it('deploys update to all computers', function () {
    $version = AgentVersion::factory()->active()->create(['version' => '3.0.0']);
    $computers = Computer::factory()->count(3)->create(['agent_version' => '1.0.0']);

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'all',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Actualización desplegada a 3 computadora(s).');

    foreach ($computers as $computer) {
        $this->assertDatabaseHas('commands', [
            'computer_id' => $computer->id,
            'type' => 'update',
            'status' => 'pending',
        ]);

        $command = Command::where('computer_id', $computer->id)
            ->where('type', 'update')
            ->latest()
            ->first();

        $this->assertNotNull($command);
        $this->assertEquals('agent_update', $command->data['subfolder']);
        $this->assertEquals($version->version, $command->data['version']);
    }
});

it('deploys update to a specific group', function () {
    $group = Group::factory()->create(['name' => 'Grupo A']);
    $version = AgentVersion::factory()->active()->create(['version' => '3.0.0']);
    $groupComputers = Computer::factory()->count(2)->create(['group_id' => $group->id, 'agent_version' => '1.0.0']);
    $otherComputer = Computer::factory()->create(['agent_version' => '1.0.0']);

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'group',
        'group_id' => $group->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Actualización desplegada a 2 computadora(s).');

    foreach ($groupComputers as $computer) {
        $this->assertDatabaseHas('commands', [
            'computer_id' => $computer->id,
            'type' => 'update',
        ]);
    }

    $this->assertDatabaseMissing('commands', [
        'computer_id' => $otherComputer->id,
        'type' => 'update',
    ]);
});

it('deploys update to a specific store (plaza)', function () {
    $version = AgentVersion::factory()->active()->create(['version' => '3.0.0']);
    $storeComputers = Computer::factory()->count(2)->create(['plaza' => 'MTY', 'agent_version' => '1.0.0']);
    $otherComputer = Computer::factory()->create(['plaza' => 'CDMX', 'agent_version' => '1.0.0']);

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'store',
        'plaza' => 'MTY',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Actualización desplegada a 2 computadora(s).');

    foreach ($storeComputers as $computer) {
        $this->assertDatabaseHas('commands', [
            'computer_id' => $computer->id,
            'type' => 'update',
        ]);
    }

    $this->assertDatabaseMissing('commands', [
        'computer_id' => $otherComputer->id,
        'type' => 'update',
    ]);
});

it('returns error when no computers match the target', function () {
    $version = AgentVersion::factory()->active()->create(['version' => '3.0.0']);
    Group::factory()->create(['name' => 'Empty Group']);

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'group',
        'group_id' => Group::first()->id,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'No se encontraron computadoras para el destino seleccionado.');
});

it('validates deploy_target is required', function () {
    $version = AgentVersion::factory()->active()->create();

    $response = $this->post(route('admin.agent-versions.deploy', $version), []);

    $response->assertSessionHasErrors('deploy_target');
});

it('validates group_id is required when deploy_target is group', function () {
    $version = AgentVersion::factory()->active()->create();

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'group',
    ]);

    $response->assertSessionHasErrors('group_id');
});

it('validates plaza is required when deploy_target is store', function () {
    $version = AgentVersion::factory()->active()->create();

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'store',
    ]);

    $response->assertSessionHasErrors('plaza');
});

it('deploys update to specific computers', function () {
    $version = AgentVersion::factory()->active()->create(['version' => '3.0.0']);
    $targetComputers = Computer::factory()->count(2)->create(['agent_version' => '1.0.0']);
    $otherComputer = Computer::factory()->create(['agent_version' => '1.0.0']);

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'computers',
        'computer_ids' => $targetComputers->pluck('id')->toArray(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Actualización desplegada a 2 computadora(s).');

    foreach ($targetComputers as $computer) {
        $this->assertDatabaseHas('commands', [
            'computer_id' => $computer->id,
            'type' => 'update',
        ]);
    }

    $this->assertDatabaseMissing('commands', [
        'computer_id' => $otherComputer->id,
        'type' => 'update',
    ]);
});

it('validates computer_ids is required when deploy_target is computers', function () {
    $version = AgentVersion::factory()->active()->create();

    $response = $this->post(route('admin.agent-versions.deploy', $version), [
        'deploy_target' => 'computers',
    ]);

    $response->assertSessionHasErrors('computer_ids');
});
