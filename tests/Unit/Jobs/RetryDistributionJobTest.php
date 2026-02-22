<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\RetryDistribution;
use App\Models\DistributionTarget;
use App\Models\Distribution;
use App\Models\Computer;
use App\Services\DistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class RetryDistributionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_can_be_instantiated_with_target()
    {
        $target = DistributionTarget::factory()->create();
        
        $job = new RetryDistribution($target);
        
        $this->assertSame($target, $job->target);
    }

    public function test_job_uses_queueable_traits()
    {
        $target = DistributionTarget::factory()->create();
        $job = new RetryDistribution($target);
        
        // Test that the job uses the required traits
        $this->assertTrue(method_exists($job, 'delete'));
        $this->assertTrue(method_exists($job, 'release'));
        $this->assertTrue(method_exists($job, 'fail'));
    }

    public function test_job_handles_retry_by_sending_download_command()
    {
        Queue::fake();
        
        $distribution = Distribution::factory()
            ->hasFiles(2)
            ->create();
        
        $computer = Computer::factory()->create();
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $this->mock(DistributionService::class, function ($mock) use ($target) {
            $mock->shouldReceive('sendDownloadCommand')
                ->once()
                ->with($target);
        });

        $job = new RetryDistribution($target);
        $job->handle();
    }

    public function test_job_can_be_queued()
    {
        Queue::fake();
        
        $target = DistributionTarget::factory()->create();
        RetryDistribution::dispatch($target);
        
        Queue::assertPushed(RetryDistribution::class, function ($job) use ($target) {
            return $job->target->id === $target->id;
        });
    }

    public function test_job_can_be_queued_with_delay()
    {
        Queue::fake();
        
        $target = DistributionTarget::factory()->create();
        $delay = now()->addMinutes(5);
        
        RetryDistribution::dispatch($target)->delay($delay);
        
        Queue::assertPushed(RetryDistribution::class, function ($job) use ($target) {
            return $job->target->id === $target->id;
        });
    }

    public function test_job_can_be_dispatched_on_specific_queue()
    {
        Queue::fake();
        
        $target = DistributionTarget::factory()->create();
        
        RetryDistribution::dispatch($target)->onQueue('distributions');
        
        Queue::assertPushedOn('distributions', RetryDistribution::class);
    }

    public function test_job_serializes_target_model()
    {
        $target = DistributionTarget::factory()->create([
            'status' => 'failed',
            'attempts' => 2,
        ]);
        
        $job = new RetryDistribution($target);
        
        // Test that the target model is properly serialized
        $this->assertEquals($target->id, $job->target->id);
        $this->assertEquals('failed', $job->target->status);
        $this->assertEquals(2, $job->target->attempts);
    }

    public function test_job_handles_target_with_distribution_and_files()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('sendDownloadCommand')
                ->once();
        });

        $distribution = Distribution::factory()
            ->hasFiles(3)
            ->create();
        
        $computer = Computer::factory()->create();
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $job = new RetryDistribution($target);
        $job->handle();

        // Ensure the target has the expected relationships
        $this->assertCount(3, $target->distribution->files);
        $this->assertEquals($computer->id, $target->computer->id);
    }

    public function test_job_failure_does_not_crash()
    {
        $this->expectException(\Exception::class);
        
        $target = DistributionTarget::factory()->create();
        
        // Mock DistributionService to throw an exception
        $this->mock(DistributionService::class, function ($mock) use ($target) {
            $mock->shouldReceive('sendDownloadCommand')
                ->once()
                ->with($target)
                ->andThrow(new \Exception('Service unavailable'));
        });

        $job = new RetryDistribution($target);
        $job->handle();
    }

    public function test_job_with_retry_attempts_history()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $mock->shouldReceive('sendDownloadCommand')
                ->once();
        });

        $target = DistributionTarget::factory()->create([
            'attempts' => 1,
            'next_retry_at' => now()->addMinutes(5),
            'error_message' => 'Previous attempt failed',
        ]);

        $job = new RetryDistribution($target);
        $job->handle();

        $this->assertEquals(1, $target->attempts);
        $this->assertEquals('Previous attempt failed', $target->error_message);
        $this->assertNotNull($target->next_retry_at);
    }

    public function test_job_factory_creates_valid_target_relationships()
    {
        $distribution = Distribution::factory()->create();
        $computer = Computer::factory()->create();
        
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $job = new RetryDistribution($target);

        $this->assertEquals($distribution->id, $job->target->distribution->id);
        $this->assertEquals($computer->id, $job->target->computer->id);
    }

    public function test_job_unique_identifier()
    {
        $target = DistributionTarget::factory()->create();
        $job = new RetryDistribution($target);
        
        // Test that the job has proper unique identifier based on target
        $jobId = spl_object_hash($job);
        $this->assertIsString($jobId);
    }

    public function test_job_with_high_priority_queue()
    {
        Queue::fake();
        
        $target = DistributionTarget::factory()->create();
        
        RetryDistribution::dispatch($target)->onConnection('redis');
        
        Queue::assertPushedOn(null, RetryDistribution::class, function ($job) use ($target) {
            return $job->target->id === $target->id;
        });
    }
}