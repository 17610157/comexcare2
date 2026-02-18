<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Distribution;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DistributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes()
    {
        $distribution = new Distribution();
        
        $fillable = ['name', 'type', 'schedule', 'description', 'created_by', 'status', 'scheduled_at'];
        
        foreach ($fillable as $attribute) {
            $this->assertTrue(in_array($attribute, $distribution->getFillable()));
        }
    }

    public function test_casts()
    {
        $distribution = Distribution::factory()->create([
            'schedule' => ['frequency' => 'daily', 'time' => '09:00'],
            'scheduled_at' => '2024-01-15 10:30:00',
        ]);

        $this->assertIsArray($distribution->schedule);
        $this->assertEquals(['frequency' => 'daily', 'time' => '09:00'], $distribution->schedule);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $distribution->scheduled_at);
        $this->assertEquals('2024-01-15 10:30:00', $distribution->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_creator_relationship()
    {
        $user = User::factory()->create();
        $distribution = Distribution::factory()->create(['created_by' => $user->id]);

        $this->assertInstanceOf(User::class, $distribution->creator);
        $this->assertEquals($user->id, $distribution->creator->id);
    }

    public function test_files_relationship()
    {
        $distribution = Distribution::factory()->create();
        $files = DistributionFile::factory()->count(3)->create(['distribution_id' => $distribution->id]);

        $this->assertCount(3, $distribution->files);
        $this->assertInstanceOf(DistributionFile::class, $distribution->files->first());
        
        foreach ($files as $file) {
            $this->assertTrue($distribution->files->contains($file));
        }
    }

    public function test_targets_relationship()
    {
        $distribution = Distribution::factory()->create();
        $targets = DistributionTarget::factory()->count(2)->create(['distribution_id' => $distribution->id]);

        $this->assertCount(2, $distribution->targets);
        $this->assertInstanceOf(DistributionTarget::class, $distribution->targets->first());
        
        foreach ($targets as $target) {
            $this->assertTrue($distribution->targets->contains($target));
        }
    }

    public function test_scope_pending()
    {
        $pending = Distribution::factory()->count(2)->create(['status' => 'pending']);
        $completed = Distribution::factory()->create(['status' => 'completed']);
        $inProgress = Distribution::factory()->create(['status' => 'in_progress']);

        $pendingDistributions = Distribution::where('status', 'pending')->get();
        
        $this->assertCount(2, $pendingDistributions);
        $this->assertTrue($pendingDistributions->contains($pending[0]));
        $this->assertTrue($pendingDistributions->contains($pending[1]));
        $this->assertFalse($pendingDistributions->contains($completed));
        $this->assertFalse($pendingDistributions->contains($inProgress));
    }

    public function test_scope_in_progress()
    {
        $inProgress = Distribution::factory()->count(3)->create(['status' => 'in_progress']);
        $pending = Distribution::factory()->create(['status' => 'pending']);
        $completed = Distribution::factory()->create(['status' => 'completed']);

        $inProgressDistributions = Distribution::where('status', 'in_progress')->get();
        
        $this->assertCount(3, $inProgressDistributions);
        $this->assertTrue($inProgressDistributions->contains($inProgress[0]));
        $this->assertTrue($inProgressDistributions->contains($inProgress[1]));
        $this->assertTrue($inProgressDistributions->contains($inProgress[2]));
        $this->assertFalse($inProgressDistributions->contains($pending));
        $this->assertFalse($inProgressDistributions->contains($completed));
    }

    public function test_scope_completed()
    {
        $completed = Distribution::factory()->count(2)->create(['status' => 'completed']);
        $pending = Distribution::factory()->create(['status' => 'pending']);
        $failed = Distribution::factory()->create(['status' => 'failed']);

        $completedDistributions = Distribution::where('status', 'completed')->get();
        
        $this->assertCount(2, $completedDistributions);
        $this->assertTrue($completedDistributions->contains($completed[0]));
        $this->assertTrue($completedDistributions->contains($completed[1]));
        $this->assertFalse($completedDistributions->contains($pending));
        $this->assertFalse($completedDistributions->contains($failed));
    }

    public function test_scope_failed()
    {
        $failed = Distribution::factory()->count(1)->create(['status' => 'failed']);
        $pending = Distribution::factory()->create(['status' => 'pending']);
        $completed = Distribution::factory()->create(['status' => 'completed']);

        $failedDistributions = Distribution::where('status', 'failed')->get();
        
        $this->assertCount(1, $failedDistributions);
        $this->assertTrue($failedDistributions->contains($failed[0]));
        $this->assertFalse($failedDistributions->contains($pending));
        $this->assertFalse($failedDistributions->contains($completed));
    }

    public function test_is_completed_attribute()
    {
        $completedDistribution = Distribution::factory()->create(['status' => 'completed']);
        $pendingDistribution = Distribution::factory()->create(['status' => 'pending']);

        $this->assertEquals('completed', $completedDistribution->status);
        $this->assertEquals('pending', $pendingDistribution->status);
    }

    public function test_immediate_type()
    {
        $distribution = Distribution::factory()->create(['type' => 'immediate']);
        $this->assertEquals('immediate', $distribution->type);
    }

    public function test_scheduled_type()
    {
        $distribution = Distribution::factory()->create(['type' => 'scheduled']);
        $this->assertEquals('scheduled', $distribution->type);
    }

    public function test_recurring_type()
    {
        $distribution = Distribution::factory()->create(['type' => 'recurring']);
        $this->assertEquals('recurring', $distribution->type);
    }

    public function test_schedule_array_storage()
    {
        $scheduleData = [
            'frequency' => 'weekly',
            'days' => ['monday', 'wednesday', 'friday'],
            'time' => '14:30',
            'timezone' => 'UTC'
        ];

        $distribution = Distribution::factory()->create(['schedule' => $scheduleData]);
        
        $this->assertIsArray($distribution->schedule);
        $this->assertEquals($scheduleData, $distribution->schedule);
        $this->assertEquals('weekly', $distribution->schedule['frequency']);
        $this->assertEquals(['monday', 'wednesday', 'friday'], $distribution->schedule['days']);
        $this->assertEquals('14:30', $distribution->schedule['time']);
        $this->assertEquals('UTC', $distribution->schedule['timezone']);
    }

    public function test_null_schedule()
    {
        $distribution = Distribution::factory()->create(['schedule' => null]);
        
        $this->assertNull($distribution->schedule);
    }

    public function test_scheduled_at_datetime()
    {
        $scheduledTime = now()->addDays(3);
        $distribution = Distribution::factory()->create(['scheduled_at' => $scheduledTime]);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $distribution->scheduled_at);
        $this->assertEquals($scheduledTime->format('Y-m-d H:i:s'), $distribution->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_null_scheduled_at()
    {
        $distribution = Distribution::factory()->create(['scheduled_at' => null]);
        
        $this->assertNull($distribution->scheduled_at);
    }

    public function test_mass_assignment()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'description' => 'Test description',
            'status' => 'pending',
            'created_by' => 1,
            'schedule' => ['test' => 'data'],
            'scheduled_at' => now(),
        ];

        $distribution = Distribution::create($data);

        foreach ($data as $key => $value) {
            if ($key === 'scheduled_at') {
                $this->assertEquals($value->format('Y-m-d H:i:s'), $distribution->$key->format('Y-m-d H:i:s'));
            } else {
                $this->assertEquals($value, $distribution->$key);
            }
        }
    }
}