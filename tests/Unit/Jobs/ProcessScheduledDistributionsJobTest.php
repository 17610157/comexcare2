<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\ProcessScheduledDistributions;
use App\Models\Distribution;
use App\Models\User;
use App\Services\DistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class ProcessScheduledDistributionsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated()
    {
        $job = new ProcessScheduledDistributions();
        
        $this->assertInstanceOf(ProcessScheduledDistributions::class, $job);
    }

    public function test_job_uses_queueable_traits()
    {
        $job = new ProcessScheduledDistributions();
        
        // Test that the job uses the required traits
        $this->assertTrue(method_exists($job, 'delete'));
        $this->assertTrue(method_exists($job, 'release'));
        $this->assertTrue(method_exists($job, 'fail'));
    }

    public function test_job_processes_due_scheduled_distributions()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('startDistribution')
                ->times(2);
        });

        $user = User::factory()->create();
        $now = now();
        
        // Create scheduled distributions that are due
        $dueDistribution1 = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subMinutes(5), // 5 minutes ago
            'created_by' => $user->id,
        ]);

        $dueDistribution2 = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subHour(), // 1 hour ago
            'created_by' => $user->id,
        ]);

        // Create a scheduled distribution that is not due yet
        $futureDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->addHour(), // 1 hour in the future
            'created_by' => $user->id,
        ]);

        // Create other types of distributions
        $immediateDistribution = Distribution::factory()->create([
            'type' => 'immediate',
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $completedDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'completed',
            'scheduled_at' => $now->subMinutes(10),
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        $job->handle();
    }

    public function test_job_only_processes_pending_scheduled_distributions()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('startDistribution')
                ->once();
        });

        $user = User::factory()->create();
        $now = now();
        
        // Create the correct distribution that should be processed
        $correctDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subMinutes(5),
            'created_by' => $user->id,
        ]);

        // Create distributions that should NOT be processed
        $inProgressDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'in_progress',
            'scheduled_at' => $now->subMinutes(10),
            'created_by' => $user->id,
        ]);

        $failedDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'failed',
            'scheduled_at' => $now->subMinutes(15),
            'created_by' => $user->id,
        ]);

        $immediateDistribution = Distribution::factory()->create([
            'type' => 'immediate',
            'status' => 'pending',
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        $job->handle();
    }

    public function test_job_does_not_process_future_distributions()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldNotReceive('startDistribution');
        });

        $user = User::factory()->create();
        
        $futureDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now()->addHour(), // Future
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        $job->handle();
    }

    public function test_job_handles_no_due_distributions()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldNotReceive('startDistribution');
        });

        $user = User::factory()->create();
        
        // Create only future distributions
        Distribution::factory()->count(3)->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now()->addHours(rand(1, 24)),
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        $job->handle();
    }

    public function test_job_can_be_queued()
    {
        Queue::fake();
        
        ProcessScheduledDistributions::dispatch();
        
        Queue::assertPushed(ProcessScheduledDistributions::class);
    }

    public function test_job_can_be_dispatched_on_specific_queue()
    {
        Queue::fake();
        
        ProcessScheduledDistributions::dispatch()->onQueue('distributions');
        
        Queue::assertPushedOn('distributions', ProcessScheduledDistributions::class);
    }

    public function test_job_can_be_scheduled()
    {
        Queue::fake();
        
        $scheduleTime = now()->addMinutes(10);
        ProcessScheduledDistributions::dispatch()->delay($scheduleTime);
        
        Queue::assertPushed(ProcessScheduledDistributions::class);
    }

    public function test_job_processes_distributions_with_exact_scheduled_time()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('startDistribution')
                ->once();
        });

        $user = User::factory()->create();
        
        $exactTimeDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now(), // Exactly now
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        $job->handle();
    }

    public function test_job_with_database_transactions()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('startDistribution')
                ->once()
                ->andThrow(new \Exception('Test exception'));
        });

        $user = User::factory()->create();
        
        $distribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now()->subMinutes(5),
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        
        $this->expectException(\Exception::class);
        $job->handle();
    }

    public function test_job_query_filters_correctly()
    {
        // Create test data
        $user = User::factory()->create();
        $now = now();
        
        Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subMinutes(5),
            'created_by' => $user->id,
        ]);

        // Test the query directly
        $distributions = Distribution::where('type', 'scheduled')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', $now)
            ->get();

        $this->assertCount(1, $distributions);
        $this->assertEquals('scheduled', $distributions->first()->type);
        $this->assertEquals('pending', $distributions->first()->status);
    }

    public function test_job_with_multiple_due_distributions_across_different_times()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('startDistribution')
                ->times(4);
        });

        $user = User::factory()->create();
        $now = now();
        
        // Create distributions with various past times
        $times = [
            $now->subMinutes(1),
            $now->subMinutes(30),
            $now->subHours(1),
            $now->subDays(1),
        ];

        foreach ($times as $time) {
            Distribution::factory()->create([
                'type' => 'scheduled',
                'status' => 'pending',
                'scheduled_at' => $time,
                'created_by' => $user->id,
            ]);
        }

        $job = new ProcessScheduledDistributions();
        $job->handle();
    }

    public function test_job_with_carbon_time_comparison()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('startDistribution')
                ->once();
        });

        $user = User::factory()->create();
        
        // Test with Carbon instance
        $scheduledTime = new Carbon('2024-01-15 10:00:00');
        $currentTime = new Carbon('2024-01-15 10:05:00');
        
        // Mock now() to return specific time
        Carbon::setTestNow($currentTime);
        
        $distribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $scheduledTime,
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions();
        $job->handle();
        
        // Reset Carbon
        Carbon::setTestNow();
    }
}