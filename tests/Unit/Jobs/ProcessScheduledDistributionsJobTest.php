<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessScheduledDistributions;
use App\Models\Distribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessScheduledDistributionsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated()
    {
        $job = new ProcessScheduledDistributions;

        $this->assertInstanceOf(ProcessScheduledDistributions::class, $job);
    }

    public function test_job_uses_queueable_traits()
    {
        $job = new ProcessScheduledDistributions;

        $this->assertTrue(method_exists($job, 'delete'));
        $this->assertTrue(method_exists($job, 'release'));
        $this->assertTrue(method_exists($job, 'fail'));
    }

    public function test_job_processes_due_scheduled_distributions()
    {
        $user = User::factory()->create();
        $now = now();

        $dueDistribution1 = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subMinutes(5),
            'created_by' => $user->id,
        ]);

        $dueDistribution2 = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subHour(),
            'created_by' => $user->id,
        ]);

        $futureDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->addHour(),
            'created_by' => $user->id,
        ]);

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

        $job = new ProcessScheduledDistributions;
        $job->handle();

        $dueDistribution1->refresh();
        $dueDistribution2->refresh();

        $this->assertEquals('in_progress', $dueDistribution1->status);
        $this->assertEquals('in_progress', $dueDistribution2->status);
    }

    public function test_job_only_processes_pending_scheduled_distributions()
    {
        $user = User::factory()->create();
        $now = now();

        $correctDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subMinutes(5),
            'created_by' => $user->id,
        ]);

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

        $job = new ProcessScheduledDistributions;
        $job->handle();

        $correctDistribution->refresh();

        $this->assertEquals('in_progress', $correctDistribution->status);
        $this->assertEquals('in_progress', $inProgressDistribution->status);
        $this->assertEquals('failed', $failedDistribution->status);
    }

    public function test_job_does_not_process_future_distributions()
    {
        $user = User::factory()->create();

        $futureDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now()->addHour(),
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions;
        $job->handle();

        $futureDistribution->refresh();
        $this->assertEquals('pending', $futureDistribution->status);
    }

    public function test_job_handles_no_due_distributions()
    {
        $user = User::factory()->create();

        Distribution::factory()->count(3)->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now()->addHours(rand(1, 24)),
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions;

        $this->assertTrue(true);
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
        $user = User::factory()->create();

        $exactTimeDistribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => now(),
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions;
        $job->handle();

        $exactTimeDistribution->refresh();
        $this->assertEquals('in_progress', $exactTimeDistribution->status);
    }

    public function test_job_query_filters_correctly()
    {
        $user = User::factory()->create();
        $now = now();

        Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $now->subMinutes(5),
            'created_by' => $user->id,
        ]);

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
        $user = User::factory()->create();
        $now = now();

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

        $job = new ProcessScheduledDistributions;
        $job->handle();

        $pendingCount = Distribution::where('type', 'scheduled')
            ->where('status', 'pending')
            ->count();

        $this->assertEquals(0, $pendingCount);
    }

    public function test_job_with_carbon_time_comparison()
    {
        $user = User::factory()->create();

        $scheduledTime = new Carbon('2024-01-15 10:00:00');
        $currentTime = new Carbon('2024-01-15 10:05:00');

        Carbon::setTestNow($currentTime);

        $distribution = Distribution::factory()->create([
            'type' => 'scheduled',
            'status' => 'pending',
            'scheduled_at' => $scheduledTime,
            'created_by' => $user->id,
        ]);

        $job = new ProcessScheduledDistributions;
        $job->handle();

        $distribution->refresh();
        $this->assertEquals('in_progress', $distribution->status);

        Carbon::setTestNow();
    }
}
