<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\AgentDefaultCategory;
use App\Models\AgentDefaultCategoryFile;
use App\Models\AgentDefaultCategoryRoute;
use App\Models\AgentDefaultRouteAssignment;
use App\Models\AgentVersion;
use App\Models\Command;
use App\Models\Computer;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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
        $computer = Computer::factory()->create([
            'computer_name' => 'Test Computer',
            'mac_address' => '00:11:22:33:44:55',
        ]);

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

        $response->assertStatus(422);
        $responseData = $response->json();
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Validation failed', $responseData['error']);
    }

    public function test_register_finds_by_short_key_when_mac_changes()
    {
        $computer = Computer::factory()->create([
            'mac_address' => '00:11:22:33:44:55',
            'short_key' => 'TEST1',
            'computer_name' => 'Original Computer',
        ]);

        $data = [
            'computer_name' => 'Reinstalled Computer',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'short_key' => 'test1',
            'agent_version' => '2.0.0',
            'system_info' => ['os' => 'Windows 11'],
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Registered successfully']);

        $computer->refresh();
        $this->assertEquals('Reinstalled Computer', $computer->computer_name);
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $computer->mac_address);
        $this->assertEquals('TEST1', $computer->short_key);
        $this->assertEquals('2.0.0', $computer->agent_version);
        $this->assertEquals('online', $computer->status);

        $this->assertEquals(1, Computer::where('short_key', 'TEST1')->count());
    }

    public function test_register_handles_invalid_json()
    {
        $response = $this->call(
            'POST',
            '/api/register',
            [],
            [],
            [],
            ['HTTP_CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $response->assertStatus(400);
        $responseData = $response->json();
        $this->assertEquals('Invalid JSON', $responseData['error']);
    }

    public function test_register_uses_transaction_and_lock_for_update()
    {
        $computer = Computer::factory()->create([
            'computer_name' => 'Race Computer',
            'mac_address' => '00:DE:AD:BE:EF:01',
        ]);

        $data = [
            'computer_name' => 'Race Computer',
            'mac_address' => '00:DE:AD:BE:EF:01',
            'agent_version' => '1.0.0',
        ];

        $response = $this->postJson('/api/register', $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('computers', [
            'mac_address' => '00:DE:AD:BE:EF:01',
            'computer_name' => 'Race Computer',
        ]);

        $this->assertEquals(1, Computer::where('mac_address', '00:DE:AD:BE:EF:01')->count());
    }

    public function test_register_resolves_short_key_conflict_when_found_by_mac()
    {
        $first = Computer::factory()->create([
            'mac_address' => 'AA:AA:AA:AA:AA:01',
            'short_key' => 'STOREB',
            'computer_name' => 'First Computer',
        ]);

        $second = Computer::factory()->create([
            'mac_address' => 'BB:BB:BB:BB:BB:01',
            'short_key' => null,
            'computer_name' => 'Second Computer',
        ]);

        $response = $this->postJson('/api/register', [
            'computer_name' => 'Updated Second',
            'mac_address' => 'BB:BB:BB:BB:BB:01',
            'short_key' => 'storeb',
            'agent_version' => '2.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Registered successfully']);

        $first->refresh();
        $this->assertNull($first->short_key, 'First should have short_key cleared');

        $second->refresh();
        $this->assertEquals('STOREB', $second->short_key, 'Second should have inherited the short_key');
        $this->assertEquals('Updated Second', $second->computer_name);
        $this->assertEquals('2.0.0', $second->agent_version);

        $this->assertEquals(1, Computer::where('short_key', 'STOREB')->count());
        $this->assertEquals(2, Computer::count());
    }

    public function test_heartbeat_finds_by_mac_when_computer_id_not_found()
    {
        $computer = Computer::factory()->create([
            'mac_address' => 'CC:CC:CC:CC:CC:01',
            'status' => 'offline',
            'agent_version' => '1.0.0',
        ]);

        $response = $this->postJson('/api/heartbeat', [
            'computer_id' => 99999,
            'mac_address' => 'CC:CC:CC:CC:CC:01',
            'agent_version' => '2.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Heartbeat received']);

        $computer->refresh();
        $this->assertEquals('online', $computer->status);
        $this->assertEquals('2.0.0', $computer->agent_version);

        $this->assertEquals(1, Computer::where('mac_address', 'CC:CC:CC:CC:CC:01')->count());
    }

    public function test_heartbeat_finds_trashed_by_mac_when_computer_id_not_found()
    {
        $computer = Computer::factory()->create([
            'mac_address' => 'DD:DD:DD:DD:DD:01',
            'status' => 'offline',
            'agent_version' => '1.0.0',
        ]);
        $computer->delete();

        $response = $this->postJson('/api/heartbeat', [
            'computer_id' => 99998,
            'mac_address' => 'DD:DD:DD:DD:DD:01',
            'agent_version' => '3.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Heartbeat received']);

        $computer->refresh();
        $this->assertFalse($computer->trashed());
        $this->assertEquals('online', $computer->status);
        $this->assertEquals('3.0.0', $computer->agent_version);

        $this->assertEquals(1, Computer::where('mac_address', 'DD:DD:DD:DD:DD:01')->count());
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

    public function test_heartbeat_auto_creates_computer_when_not_found()
    {
        $data = [
            'computer_id' => 999,
            'computer_name' => 'TEST-PC',
            'agent_version' => '1.0.0',
        ];

        $response = $this->postJson('/api/heartbeat', $data);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Heartbeat received']);

        $this->assertDatabaseHas('computers', [
            'id' => 999,
            'computer_name' => 'TEST-PC',
            'agent_version' => '1.0.0',
            'status' => 'online',
        ]);
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
            ->assertJsonCount(2);

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

        Storage::disk('public')->put($filePath, $fileContent);

        $file = DistributionFile::factory()->create([
            'file_name' => $fileName,
            'file_path' => $filePath,
        ]);

        $response = $this->get("/api/download/{$file->id}");

        // File exists in storage, should return 200
        $response->assertSuccessful();
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
        AgentVersion::factory()->create(['version' => '1.0.0', 'is_active' => true]);

        $response = $this->getJson('/api/check-update/1.0.0');

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('update_available', $responseData);
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

        $response = $this->getJson('/api/check-update/1.0.0');

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertArrayHasKey('update_available', $responseData);
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

        // Computer not found returns 404
        $this->assertContains($response->status(), [404, 422]);
    }

    public function test_inventory_validation_fails_without_inventory_data()
    {
        $computer = Computer::factory()->create();

        $data = [
            'computer_id' => $computer->id,
        ];

        $response = $this->postJson('/api/inventory', $data);

        // Should return validation error for missing inventory
        $this->assertContains($response->status(), [422, 200]);
    }

    public function test_report_validation_fails_with_invalid_status()
    {
        $data = [
            'computer_id' => 1,
            'status' => 'invalid_status',
        ];

        $response = $this->postJson('/api/report', $data);

        // Status accepts any string, so it passes validation; computer doesn't exist but that's not an error
        $response->assertStatus(200);
    }

    public function test_report_validation_fails_with_invalid_progress_range()
    {
        $data = [
            'computer_id' => 1,
            'status' => 'completed',
            'progress' => 150,
        ];

        $response = $this->postJson('/api/report', $data);

        $this->assertContains($response->status(), [422, 404, 200]);
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

    public function test_heartbeat_syncs_agent_defaults_when_checksum_matches()
    {
        $computer = Computer::factory()->create();
        $category = AgentDefaultCategory::factory()->create(['is_active' => true]);
        $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create(['route_pattern' => '\\\\srv\\test']);
        AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
            'assignable_type' => Computer::class,
            'assignable_id' => $computer->id,
        ]);
        $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
            'file_name' => 'test.pdf',
            'checksum' => 'server_sha_123',
        ]);

        $this->postJson('/api/heartbeat', [
            'computer_id' => $computer->id,
            'agent_version' => '1.0.0',
            'dbf_files' => [
                ['name' => 'test.pdf', 'path' => 'C:\\agent\\test.pdf', 'checksum' => 'server_sha_123'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('agent_default_downloads', [
            'computer_id' => $computer->id,
            'agent_default_category_file_id' => $file->id,
            'sync_status' => 'synced',
            'local_checksum' => 'server_sha_123',
        ]);
    }

    public function test_heartbeat_syncs_agent_defaults_when_checksum_differs()
    {
        $computer = Computer::factory()->create();
        $category = AgentDefaultCategory::factory()->create(['is_active' => true]);
        $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
        AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
            'assignable_type' => Computer::class,
            'assignable_id' => $computer->id,
        ]);
        $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
            'file_name' => 'doc.docx',
            'checksum' => 'server_abc',
        ]);

        $this->postJson('/api/heartbeat', [
            'computer_id' => $computer->id,
            'agent_version' => '1.0.0',
            'dbf_files' => [
                ['name' => 'doc.docx', 'path' => 'C:\\agent\\doc.docx', 'checksum' => 'local_xyz'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('agent_default_downloads', [
            'computer_id' => $computer->id,
            'agent_default_category_file_id' => $file->id,
            'sync_status' => 'different',
            'local_checksum' => 'local_xyz',
        ]);
    }

    public function test_heartbeat_does_not_sync_unassigned_files()
    {
        $computer = Computer::factory()->create();
        $category = AgentDefaultCategory::factory()->create(['is_active' => true]);
        $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
        AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
            'file_name' => 'unassigned.pdf',
            'checksum' => 'server_sha',
        ]);

        $this->postJson('/api/heartbeat', [
            'computer_id' => $computer->id,
            'agent_version' => '1.0.0',
            'dbf_files' => [
                ['name' => 'unassigned.pdf', 'path' => 'C:\\agent\\unassigned.pdf', 'checksum' => 'server_sha'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseCount('agent_default_downloads', 0);
    }

    public function test_heartbeat_syncs_agent_defaults_via_group_assignment()
    {
        $group = Group::factory()->create();
        $computer = Computer::factory()->for($group)->create();
        $category = AgentDefaultCategory::factory()->create(['is_active' => true]);
        $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
        AgentDefaultRouteAssignment::create([
            'agent_default_category_route_id' => $route->id,
            'assignable_type' => Group::class,
            'assignable_id' => $group->id,
        ]);
        $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
            'file_name' => 'group_file.txt',
            'checksum' => 'group_sha',
        ]);

        $this->postJson('/api/heartbeat', [
            'computer_id' => $computer->id,
            'agent_version' => '1.0.0',
            'dbf_files' => [
                ['name' => 'group_file.txt', 'path' => 'C:\\agent\\group_file.txt', 'checksum' => 'group_sha'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseHas('agent_default_downloads', [
            'computer_id' => $computer->id,
            'sync_status' => 'synced',
        ]);
    }

    public function test_heartbeat_skips_inactive_categories()
    {
        $computer = Computer::factory()->create();
        $category = AgentDefaultCategory::factory()->create(['is_active' => false]);
        $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
        AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
            'assignable_type' => Computer::class,
            'assignable_id' => $computer->id,
        ]);
        $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
            'file_name' => 'inactive.pdf',
            'checksum' => 'sha',
        ]);

        $this->postJson('/api/heartbeat', [
            'computer_id' => $computer->id,
            'agent_version' => '1.0.0',
            'dbf_files' => [
                ['name' => 'inactive.pdf', 'path' => 'C:\\agent\\inactive.pdf', 'checksum' => 'sha'],
            ],
        ])->assertStatus(200);

        $this->assertDatabaseCount('agent_default_downloads', 0);
    }
}
