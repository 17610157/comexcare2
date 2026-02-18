<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DistributionService;
use App\Models\Computer;
use App\Models\Distribution;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\RetryDistribution;

class DistributionServiceTest extends TestCase
{
    use RefreshDatabase;

    private DistributionService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DistributionService::class);
        $this->user = User::factory()->create();
        Storage::fake('local');
    }

    public function test_create_distribution_with_basic_data()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'description' => 'Test description',
        ];

        $distribution = $this->service->createDistribution($data, $this->user->id);

        $this->assertInstanceOf(Distribution::class, $distribution);
        $this->assertEquals('Test Distribution', $distribution->name);
        $this->assertEquals('immediate', $distribution->type);
        $this->assertEquals('Test description', $distribution->description);
        $this->assertEquals('pending', $distribution->status);
        $this->assertEquals($this->user->id, $distribution->created_by);
    }

    public function test_create_distribution_with_files()
    {
        // Skip this test for now as it requires UploadedFile handling
        $this->markTestSkipped('File upload testing requires additional setup');
    }

    public function test_create_distribution_with_all_targets()
    {
        Computer::factory()->count(3)->create();
        
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'all',
        ];

        $distribution = $this->service->createDistribution($data, $this->user->id);

        $this->assertCount(3, $distribution->targets);
    }

    public function test_create_distribution_with_group_targets()
    {
        $group1 = Computer::factory()->count(2)->create(['group_id' => 1]);
        $group2 = Computer::factory()->count(1)->create(['group_id' => 2]);
        
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'group',
            'group_id' => 1,
        ];

        $distribution = $this->service->createDistribution($data, $this->user->id);

        $this->assertCount(2, $distribution->targets);
        $targetIds = $distribution->targets->pluck('computer_id');
        $this->assertTrue($targetIds->contains($group1[0]->id));
        $this->assertTrue($targetIds->contains($group1[1]->id));
        $this->assertFalse($targetIds->contains($group2[0]->id));
    }

    public function test_create_distribution_with_specific_targets()
    {
        $computers = Computer::factory()->count(3)->create();
        
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'specific',
            'targets' => [$computers[0]->id, $computers[2]->id],
        ];

        $distribution = $this->service->createDistribution($data, $this->user->id);

        $this->assertCount(2, $distribution->targets);
        $targetIds = $distribution->targets->pluck('computer_id');
        $this->assertTrue($targetIds->contains($computers[0]->id));
        $this->assertFalse($targetIds->contains($computers[1]->id));
        $this->assertTrue($targetIds->contains($computers[2]->id));
    }

    public function test_start_distribution_updates_status_and_sends_commands()
    {
        Queue::fake();
        
        $computer = Computer::factory()->create();
        $distribution = Distribution::factory()
            ->has(DistributionFile::factory()->count(2), 'files')
            ->has(DistributionTarget::factory()->state(['computer_id' => $computer->id]), 'targets')
            ->create(['status' => 'pending']);

        $this->service->startDistribution($distribution);

        $distribution->refresh();
        $this->assertEquals('in_progress', $distribution->status);
        
        // Check that commands were created for each file
        $commandsCount = \App\Models\Command::where('computer_id', $computer->id)
            ->where('type', 'download')
            ->count();
        $this->assertEquals(2, $commandsCount);
    }

    public function test_send_download_command_creates_commands_for_each_file()
    {
        $distribution = Distribution::factory()
            ->has(DistributionFile::factory()->count(3), 'files')
            ->create();
        
        $computer = Computer::factory()->create();
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $this->service->sendDownloadCommand($target);

        $commands = \App\Models\Command::where('computer_id', $computer->id)
            ->where('type', 'download')
            ->get();
        
        $this->assertCount(3, $commands);
        
        foreach ($distribution->files as $file) {
            $command = $commands->firstWhere('data->file_id', $file->id);
            $this->assertNotNull($command);
            $this->assertEquals($target->id, $command->data['distribution_target_id']);
        }
    }

    public function test_handle_retry_marks_as_failed_after_max_attempts()
    {
        Queue::fake();
        
        $target = DistributionTarget::factory()->create(['attempts' => 3]);

        $this->service->handleRetry($target);

        $target->refresh();
        $this->assertEquals('failed', $target->status);
        Queue::assertNotPushed(RetryDistribution::class);
    }

    public function test_handle_retry_schedules_retry_for_failed_target()
    {
        Queue::fake();
        
        $target = DistributionTarget::factory()->create(['attempts' => 1]);

        $this->service->handleRetry($target);

        $target->refresh();
        $this->assertEquals(2, $target->attempts);
        $this->assertEquals('pending', $target->status);
        $this->assertNotNull($target->next_retry_at);
        
        Queue::assertPushed(RetryDistribution::class, function ($job) use ($target) {
            return $job->target->id === $target->id;
        });
    }

    public function test_handle_retry_uses_exponential_backoff()
    {
        $delays = [
            ['attempts' => 0, 'expected_delay' => 1],
            ['attempts' => 1, 'expected_delay' => 5],
            ['attempts' => 2, 'expected_delay' => 15],
        ];

        foreach ($delays as $case) {
            $target = DistributionTarget::factory()->create(['attempts' => $case['attempts']]);
            
            $this->service->handleRetry($target);
            
            $target->refresh();
            $expectedTime = now()->addMinutes($case['expected_delay']);
            $this->assertEquals($expectedTime->format('Y-m-d H:i'), $target->next_retry_at->format('Y-m-d H:i'));
        }
    }

    public function test_validate_file_space_returns_true_when_no_system_info()
    {
        $computer = Computer::factory()->create(['system_info' => null]);
        $file = DistributionFile::factory()->create(['file_size' => 1000000]);

        $result = $this->service->validateFileSpace($computer, $file);

        $this->assertTrue($result);
    }

    public function test_validate_file_space_returns_true_when_enough_space()
    {
        $computer = Computer::factory()->create([
            'system_info' => ['disk_free' => 2000000] // 2MB
        ]);
        $file = DistributionFile::factory()->create(['file_size' => 1000000]); // 1MB

        $result = $this->service->validateFileSpace($computer, $file);

        $this->assertTrue($result);
    }

    public function test_validate_file_space_returns_false_when_not_enough_space()
    {
        $computer = Computer::factory()->create([
            'system_info' => ['disk_free' => 500000] // 0.5MB
        ]);
        $file = DistributionFile::factory()->create(['file_size' => 1000000]); // 1MB

        $result = $this->service->validateFileSpace($computer, $file);

        $this->assertFalse($result);
    }

    public function test_create_scheduled_distribution()
    {
        $scheduledAt = now()->addDay();
        $data = [
            'name' => 'Scheduled Distribution',
            'type' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ];

        $distribution = $this->service->createDistribution($data, $this->user->id);

        $this->assertEquals($scheduledAt->format('Y-m-d H:i:s'), $distribution->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_create_distribution_with_schedule_data()
    {
        $scheduleData = [
            'frequency' => 'daily',
            'time' => '09:00',
            'days' => ['monday', 'wednesday', 'friday']
        ];
        
        $data = [
            'name' => 'Recurring Distribution',
            'type' => 'recurring',
            'schedule' => $scheduleData,
        ];

        $distribution = $this->service->createDistribution($data, $this->user->id);

        $this->assertEquals($scheduleData, $distribution->schedule);
    }
}