<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RetryDistribution;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Distribution;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

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

        $this->assertTrue(method_exists($job, 'delete'));
        $this->assertTrue(method_exists($job, 'release'));
        $this->assertTrue(method_exists($job, 'fail'));
    }

    public function test_job_handles_retry_by_sending_download_command()
    {
        $distribution = Distribution::factory()->create();
        $file = DistributionFile::factory()->create(['distribution_id' => $distribution->id]);

        $computer = Computer::factory()->create();
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $job = new RetryDistribution($target);
        $job->handle();

        $this->assertDatabaseHas('commands', [
            'computer_id' => $computer->id,
            'type' => 'download',
        ]);
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

        $this->assertEquals($target->id, $job->target->id);
        $this->assertEquals('failed', $job->target->status);
        $this->assertEquals(2, $job->target->attempts);
    }

    public function test_job_handles_target_with_distribution_and_files()
    {
        $distribution = Distribution::factory()->create();
        $files = DistributionFile::factory()->count(3)->create(['distribution_id' => $distribution->id]);

        $computer = Computer::factory()->create();
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $job = new RetryDistribution($target);
        $job->handle();

        $this->assertCount(3, $target->fresh()->distribution->files);
        $this->assertEquals($computer->id, $target->fresh()->computer->id);
    }

    public function test_job_creates_commands_for_each_file()
    {
        $distribution = Distribution::factory()->create();
        $files = DistributionFile::factory()->count(2)->create(['distribution_id' => $distribution->id]);

        $computer = Computer::factory()->create();
        $target = DistributionTarget::factory()->create([
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
        ]);

        $job = new RetryDistribution($target);
        $job->handle();

        $commandsCount = Command::where('computer_id', $computer->id)
            ->where('type', 'download')
            ->count();

        $this->assertEquals(2, $commandsCount);
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

        $jobId = spl_object_hash($job);
        $this->assertIsString($jobId);
    }

    public function test_job_with_high_priority_queue()
    {
        Queue::fake();

        $target = DistributionTarget::factory()->create();

        RetryDistribution::dispatch($target)->onConnection('redis');

        Queue::assertPushed(RetryDistribution::class);
    }
}
