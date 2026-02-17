<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use App\Http\Controllers\Api\AgentController;
use App\Models\Computer;
use App\Models\Command;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use App\Models\AgentVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AgentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_register_creates_new_computer()
    {
        $data = [
            'computer_name' => 'Test Computer',
            'mac_address' => '00:11:22:33:44:55',
            'agent_version' => '1.0.0',
            'system_info' => ['os' => 'Windows 10', 'ram' => '8GB'],
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'message'])
            ->assertJson(['message' => 'Registered successfully']);

        $this->assertDatabaseHas('computers', [
            'computer_name' => 'Test Computer',
            'mac_address' => '00:11:22:33:44:55',
            'agent_version' => '1.0.0',
            'status' => 'online',
        ]);
    }

    public function test_register_updates_existing_computer()
    {
        $computer = Computer::factory()->create([
            'mac_address' => '00:11:22:33:44:55',
            'computer_name' => 'Old Name',
            'agent_version' => '1.0.0',
        ]);

        $data = [
            'computer_name' => 'Updated Computer',
            'mac_address' => '00:11:22:33:44:55',
            'agent_version' => '2.0.0',
            'system_info' => ['os' => 'Windows 11'],
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('computers', [
            'id' => $computer->id,
            'computer_name' => 'Updated Computer',
            'mac_address' => '00:11:22:33:44:55',
            'agent_version' => '2.0.0',
        ]);

        $this->assertEquals(1, Computer::where('mac_address', '00:11:22:33:44:55')->count());
    }

    public function test_register_validation_fails_with_invalid_data()
    {
        $data = [
            'computer_name' => '',
            'mac_address' => '',
            'agent_version' => '',
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['computer_name', 'mac_address', 'agent_version']);
    }

    public function test_register_handles_invalid_json()
    {
        $response = $this->withHeaders(['Content-Type' => 'application/json'])
            ->post('/api/register', 'invalid json');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid JSON']);
    }

    public function test_heartbeat_updates_computer_status()
    {
        $computer = Computer::factory()->create([
            'status' => 'offline',
            'last_seen' => now()->subHours(2),
        ]);

        $data = [
            'computer_id' => $computer->id,
            'agent_version' => '1.5.0',
            'system_info' => ['cpu' => 'Intel i7'],
        ];

        $response = $this->postJson('/api/heartbeat', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Heartbeat received']);

        $computer->refresh();
        $this->assertEquals('online', $computer->status);
        $this->assertEquals('1.5.0', $computer->agent_version);
        $this->assertEquals(['cpu' => 'Intel i7'], $computer->system_info);
    }

    public function test_heartbeat_validation_fails_with_invalid_computer_id()
    {
        $data = [
            'computer_id' => 999,
            'agent_version' => '1.0.0',
        ];

        $response = $this->postJson('/api/heartbeat', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['computer_id']);
    }

    public function test_get_commands_returns_pending_commands()
    {
        $computer = Computer::factory()->create();
        
        $pendingCommand = Command::factory()->create([
            'computer_id' => $computer->id,
            'status' => 'pending',
        ]);

        $sentCommand = Command::factory()->create([
            'computer_id' => $computer->id,
            'status' => 'sent',
        ]);

        $completedCommand = Command::factory()->create([
            'computer_id' => $computer->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson("/api/commands/{$computer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1);

        $response->assertJsonFragment([
            'id' => $pendingCommand->id,
            'status' => 'sent', // Should be updated to 'sent'
        ]);

        $pendingCommand->refresh();
        $this->assertEquals('sent', $pendingCommand->status);
        $this->assertNotNull($pendingCommand->sent_at);
    }

    public function test_get_commands_returns_empty_array_for_no_pending_commands()
    {
        $computer = Computer::factory()->create();

        $response = $this->getJson("/api/commands/{$computer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0);
    }

    public function test_report_updates_command_status()
    {
        $command = Command::factory()->create(['status' => 'sent']);
        
        $data = [
            'computer_id' => $command->computer_id,
            'command_id' => $command->id,
            'status' => 'completed',
            'response' => 'Command executed successfully',
        ];

        $response = $this->postJson('/api/report', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Report received']);

        $command->refresh();
        $this->assertEquals('completed', $command->status);
        $this->assertEquals('Command executed successfully', $command->response);
        $this->assertNotNull($command->completed_at);
    }

    public function test_report_updates_distribution_target_progress()
    {
        $distributionTarget = DistributionTarget::factory()->create();
        $command = Command::factory()->create([
            'type' => 'download',
            'computer_id' => $distributionTarget->computer_id,
            'data' => ['distribution_target_id' => $distributionTarget->id],
        ]);

        $data = [
            'computer_id' => $distributionTarget->computer_id,
            'command_id' => $command->id,
            'status' => 'completed',
            'progress' => 100,
        ];

        $response = $this->postJson('/api/report', $data);

        $response->assertStatus(200);

        $distributionTarget->refresh();
        $this->assertEquals(100, $distributionTarget->progress);
        $this->assertEquals('completed', $distributionTarget->status);
    }

    public function test_report_marks_distribution_target_as_failed()
    {
        $distributionTarget = DistributionTarget::factory()->create();
        $command = Command::factory()->create([
            'type' => 'download',
            'computer_id' => $distributionTarget->computer_id,
            'data' => ['distribution_target_id' => $distributionTarget->id],
        ]);

        $data = [
            'computer_id' => $distributionTarget->computer_id,
            'command_id' => $command->id,
            'status' => 'failed',
            'progress' => 45,
        ];

        $response = $this->postJson('/api/report', $data);

        $response->assertStatus(200);

        $distributionTarget->refresh();
        $this->assertEquals(45, $distributionTarget->progress);
        $this->assertEquals('failed', $distributionTarget->status);
    }

    public function test_download_returns_file_when_exists()
    {
        $fileContent = 'Test file content';
        $fileName = 'test-file.txt';
        $filePath = 'distributions/test-file.txt';
        
        Storage::put($filePath, $fileContent);

        $file = DistributionFile::factory()->create([
            'file_name' => $fileName,
            'file_path' => $filePath,
        ]);

        $response = $this->getJson("/api/download/{$file->id}");

        $response->assertStatus(200)
            ->assertHeader('content-disposition', "attachment; filename=\"{$fileName}\"");
    }

    public function test_download_returns_error_when_file_not_found()
    {
        $file = DistributionFile::factory()->create([
            'file_path' => 'non-existent/file.txt',
        ]);

        $response = $this->getJson("/api/download/{$file->id}");

        $response->assertStatus(404)
            ->assertJson(['error' => 'File not found']);
    }

    public function test_download_returns_error_for_invalid_file_id()
    {
        $response = $this->getJson('/api/download/999');

        $response->assertStatus(404);
    }

    public function test_check_update_returns_no_update_available()
    {
        $version = AgentVersion::factory()->create(['version' => '1.0.0', 'is_active' => true]);
        
        $response = $this->getJson('/api/update/1.0.0');

        $response->assertStatus(200)
            ->assertJson(['update_available' => false]);
    }

    public function test_check_update_returns_update_available()
    {
        $currentVersion = AgentVersion::factory()->create(['version' => '1.0.0']);
        $latestVersion = AgentVersion::factory()->create([
            'version' => '2.0.0',
            'is_active' => true,
            'channel' => 'stable',
            'checksum' => 'abc123',
            'changelog' => 'New features and bug fixes',
        ]);

        $response = $this->getJson('/api/update/1.0.0');

        $response->assertStatus(200)
            ->assertJson([
                'update_available' => true,
                'version' => '2.0.0',
                'channel' => 'stable',
                'checksum' => 'abc123',
                'changelog' => 'New features and bug fixes',
            ]);
    }

    public function test_check_update_when_no_active_versions()
    {
        $response = $this->getJson('/api/update/1.0.0');

        $response->assertStatus(200)
            ->assertJson(['update_available' => false]);
    }

    public function test_inventory_updates_computer_inventory()
    {
        $computer = Computer::factory()->create();
        
        $inventory = [
            'software' => ['Chrome', 'Office'],
            'hardware' => ['CPU: Intel i7', 'RAM: 16GB'],
        ];

        $data = [
            'computer_id' => $computer->id,
            'inventory' => $inventory,
        ];

        $response = $this->postJson('/api/inventory', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Inventory received']);

        $computer->refresh();
        $this->assertArrayHasKey('inventory', $computer->agent_config);
        $this->assertEquals($inventory, $computer->agent_config['inventory']);
    }

    public function test_inventory_merges_with_existing_config()
    {
        $computer = Computer::factory()->create([
            'agent_config' => ['existing_setting' => 'value'],
        ]);
        
        $inventory = ['software' => ['Firefox']];

        $data = [
            'computer_id' => $computer->id,
            'inventory' => $inventory,
        ];

        $this->postJson('/api/inventory', $data);

        $computer->refresh();
        $this->assertEquals('value', $computer->agent_config['existing_setting']);
        $this->assertEquals($inventory, $computer->agent_config['inventory']);
    }

    public function test_inventory_validation_fails_with_invalid_computer_id()
    {
        $data = [
            'computer_id' => 999,
            'inventory' => ['test' => 'data'],
        ];

        $response = $this->postJson('/api/inventory', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['computer_id']);
    }

    public function test_inventory_validation_fails_without_inventory_data()
    {
        $computer = Computer::factory()->create();

        $data = [
            'computer_id' => $computer->id,
        ];

        $response = $this->postJson('/api/inventory', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inventory']);
    }

    public function test_report_validation_fails_with_invalid_status()
    {
        $data = [
            'computer_id' => 1,
            'status' => 'invalid_status',
        ];

        $response = $this->postJson('/api/report', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_report_validation_fails_with_invalid_progress_range()
    {
        $data = [
            'computer_id' => 1,
            'status' => 'completed',
            'progress' => 150, // Invalid: > 100
        ];

        $response = $this->postJson('/api/report', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['progress']);
    }

    public function test_report_works_without_command_id()
    {
        $computer = Computer::factory()->create();

        $data = [
            'computer_id' => $computer->id,
            'status' => 'completed',
            'response' => 'General status report',
        ];

        $response = $this->postJson('/api/report', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Report received']);
    }
}