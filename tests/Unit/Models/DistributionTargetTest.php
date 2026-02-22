<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DistributionTarget;
use App\Models\Distribution;
use App\Models\Computer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DistributionTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes()
    {
        $distributionTarget = new DistributionTarget();
        
        $fillable = [
            'distribution_id', 
            'computer_id', 
            'status', 
            'progress', 
            'attempts', 
            'next_retry_at', 
            'error_message'
        ];
        
        foreach ($fillable as $attribute) {
            $this->assertTrue(in_array($attribute, $distributionTarget->getFillable()));
        }
    }

    public function test_casts()
    {
        $distributionTarget = DistributionTarget::factory()->create([
            'next_retry_at' => '2024-01-15 14:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $distributionTarget->next_retry_at);
        $this->assertEquals('2024-01-15 14:30:00', $distributionTarget->next_retry_at->format('Y-m-d H:i:s'));
    }

    public function test_distribution_relationship()
    {
        $distribution = Distribution::factory()->create();
        $distributionTarget = DistributionTarget::factory()->create(['distribution_id' => $distribution->id]);

        $this->assertInstanceOf(Distribution::class, $distributionTarget->distribution);
        $this->assertEquals($distribution->id, $distributionTarget->distribution->id);
    }

    public function test_computer_relationship()
    {
        $computer = Computer::factory()->create();
        $distributionTarget = DistributionTarget::factory()->create(['computer_id' => $computer->id]);

        $this->assertInstanceOf(Computer::class, $distributionTarget->computer);
        $this->assertEquals($computer->id, $distributionTarget->computer->id);
    }

    public function test_mass_assignment()
    {
        $distribution = Distribution::factory()->create();
        $computer = Computer::factory()->create();
        
        $data = [
            'distribution_id' => $distribution->id,
            'computer_id' => $computer->id,
            'status' => 'in_progress',
            'progress' => 75,
            'attempts' => 2,
            'next_retry_at' => now()->addMinutes(15),
            'error_message' => 'Network timeout occurred',
        ];

        $distributionTarget = DistributionTarget::create($data);

        $this->assertEquals($distribution->id, $distributionTarget->distribution_id);
        $this->assertEquals($computer->id, $distributionTarget->computer_id);
        $this->assertEquals('in_progress', $distributionTarget->status);
        $this->assertEquals(75, $distributionTarget->progress);
        $this->assertEquals(2, $distributionTarget->attempts);
        $this->assertEquals($data['next_retry_at']->format('Y-m-d H:i:s'), $distributionTarget->next_retry_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Network timeout occurred', $distributionTarget->error_message);
    }

    public function test_progress_range_validation()
    {
        $distributionTarget = DistributionTarget::factory()->create(['progress' => 0]);
        $this->assertEquals(0, $distributionTarget->progress);

        $distributionTarget->update(['progress' => 100]);
        $this->assertEquals(100, $distributionTarget->progress);

        $distributionTarget->update(['progress' => 50]);
        $this->assertEquals(50, $distributionTarget->progress);
    }

    public function test_status_values()
    {
        $statuses = ['pending', 'in_progress', 'completed', 'failed'];

        foreach ($statuses as $status) {
            $distributionTarget = DistributionTarget::factory()->create(['status' => $status]);
            $this->assertEquals($status, $distributionTarget->status);
        }
    }

    public function test_attempts_counter()
    {
        $distributionTarget = DistributionTarget::factory()->create(['attempts' => 0]);
        $this->assertEquals(0, $distributionTarget->attempts);

        $distributionTarget->update(['attempts' => 3]);
        $this->assertEquals(3, $distributionTarget->attempts);
    }

    public function test_null_next_retry_at()
    {
        $distributionTarget = DistributionTarget::factory()->create(['next_retry_at' => null]);
        
        $this->assertNull($distributionTarget->next_retry_at);
    }

    public function test_null_error_message()
    {
        $distributionTarget = DistributionTarget::factory()->create(['error_message' => null]);
        
        $this->assertNull($distributionTarget->error_message);
    }

    public function test_belongs_to_distribution_with_multiple_targets()
    {
        $distribution = Distribution::factory()->create();
        $targets = DistributionTarget::factory()->count(5)->create(['distribution_id' => $distribution->id]);

        $this->assertCount(5, $distribution->targets);
        
        foreach ($targets as $target) {
            $this->assertEquals($distribution->id, $target->distribution_id);
            $this->assertEquals($distribution->id, $target->distribution->id);
        }
    }

    public function test_belongs_to_computer_with_multiple_targets()
    {
        $computer = Computer::factory()->create();
        $targets = DistributionTarget::factory()->count(3)->create(['computer_id' => $computer->id]);

        $this->assertCount(3, $computer->distributionTargets);
        
        foreach ($targets as $target) {
            $this->assertEquals($computer->id, $target->computer_id);
            $this->assertEquals($computer->id, $target->computer->id);
        }
    }

    public function test_delete_distribution_cascade_to_targets()
    {
        $distribution = Distribution::factory()->create();
        $targets = DistributionTarget::factory()->count(3)->create(['distribution_id' => $distribution->id]);

        $this->assertDatabaseCount('distribution_targets', 3);

        $distribution->delete();

        $this->assertDatabaseCount('distribution_targets', 0);
        $this->assertDatabaseMissing('distribution_targets', ['distribution_id' => $distribution->id]);
    }

    public function test_delete_computer_cascade_to_targets()
    {
        $computer = Computer::factory()->create();
        $targets = DistributionTarget::factory()->count(2)->create(['computer_id' => $computer->id]);

        $this->assertDatabaseCount('distribution_targets', 2);

        $computer->delete();

        $this->assertDatabaseCount('distribution_targets', 0);
        $this->assertDatabaseMissing('distribution_targets', ['computer_id' => $computer->id]);
    }

    public function test_factory_creates_valid_distribution_target()
    {
        $distributionTarget = DistributionTarget::factory()->create();

        $this->assertNotNull($distributionTarget->distribution_id);
        $this->assertNotNull($distributionTarget->computer_id);
        $this->assertNotNull($distributionTarget->status);
        $this->assertIsInt($distributionTarget->progress);
        $this->assertIsInt($distributionTarget->attempts);
        $this->assertIsString($distributionTarget->status);
    }

    public function test_progress_update_workflow()
    {
        $distributionTarget = DistributionTarget::factory()->create(['status' => 'pending', 'progress' => 0]);

        // Start distribution
        $distributionTarget->update(['status' => 'in_progress', 'progress' => 25]);
        $this->assertEquals('in_progress', $distributionTarget->status);
        $this->assertEquals(25, $distributionTarget->progress);

        // Update progress
        $distributionTarget->update(['progress' => 50]);
        $this->assertEquals(50, $distributionTarget->progress);

        // Complete distribution
        $distributionTarget->update(['status' => 'completed', 'progress' => 100]);
        $this->assertEquals('completed', $distributionTarget->status);
        $this->assertEquals(100, $distributionTarget->progress);
    }

    public function test_retry_workflow()
    {
        $distributionTarget = DistributionTarget::factory()->create([
            'status' => 'failed',
            'attempts' => 1,
            'error_message' => 'Connection timeout'
        ]);

        // Schedule retry
        $retryTime = now()->addMinutes(5);
        $distributionTarget->update([
            'status' => 'pending',
            'attempts' => 2,
            'next_retry_at' => $retryTime
        ]);

        $this->assertEquals('pending', $distributionTarget->status);
        $this->assertEquals(2, $distributionTarget->attempts);
        $this->assertEquals($retryTime->format('Y-m-d H:i:s'), $distributionTarget->next_retry_at->format('Y-m-d H:i:s'));
        $this->assertEquals('Connection timeout', $distributionTarget->error_message);
    }

    public function test_long_error_message()
    {
        $longErrorMessage = 'The file transfer failed due to network connectivity issues. The agent was unable to establish a stable connection to the server within the specified timeout period. Please check network connectivity and firewall settings.';
        
        $distributionTarget = DistributionTarget::factory()->create(['error_message' => $longErrorMessage]);
        
        $this->assertEquals($longErrorMessage, $distributionTarget->error_message);
    }

    public function test_future_retry_time()
    {
        $futureTime = now()->addHours(2)->addMinutes(30);
        $distributionTarget = DistributionTarget::factory()->create(['next_retry_at' => $futureTime]);
        
        $this->assertTrue($distributionTarget->next_retry_at->isFuture());
        $this->assertEquals($futureTime->format('Y-m-d H:i:s'), $distributionTarget->next_retry_at->format('Y-m-d H:i:s'));
    }
}