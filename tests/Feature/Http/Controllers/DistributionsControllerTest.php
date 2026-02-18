<?php

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\DistributionsController;
use App\Models\Distribution;
use App\Models\Group;
use App\Models\User;
use App\Services\DistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DistributionsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Storage::fake('local');
        Auth::login($this->admin);
    }

    public function test_index_displays_distributions()
    {
        Distribution::factory()->count(5)->create(['created_by' => $this->admin->id]);
        
        $response = $this->get(route('distributions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.distributions.index');
        $response->assertViewHas('distributions');
    }

    public function test_index_paginates_distributions()
    {
        Distribution::factory()->count(25)->create(['created_by' => $this->admin->id]);
        
        $response = $this->get(route('distributions.index'));

        $response->assertStatus(200);
        $distributions = $response->viewData('distributions');
        $this->assertCount(20, $distributions);
    }

    public function test_create_displays_form_with_groups()
    {
        Group::factory()->count(3)->create();
        
        $response = $this->get(route('distributions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.distributions.create');
        $response->assertViewHas('groups');
        $groups = $response->viewData('groups');
        $this->assertCount(3, $groups);
    }

    public function test_store_creates_immediate_distribution()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $distribution = Distribution::factory()->make();
            $mock->shouldReceive('createDistribution')
                ->once()
                ->andReturn($distribution);
            $mock->shouldReceive('startDistribution')
                ->once()
                ->with($distribution);
        });

        $data = [
            'name' => 'Test Immediate Distribution',
            'type' => 'immediate',
            'target_type' => 'all',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertRedirect(route('distributions.index'));
        $response->assertSessionHas('success', 'Distribution created successfully');
    }

    public function test_store_creates_scheduled_distribution()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $distribution = Distribution::factory()->make();
            $mock->shouldReceive('createDistribution')
                ->once()
                ->andReturn($distribution);
            $mock->shouldNotReceive('startDistribution');
        });

        $data = [
            'name' => 'Test Scheduled Distribution',
            'type' => 'scheduled',
            'target_type' => 'all',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertRedirect(route('distributions.index'));
        $response->assertSessionHas('success', 'Distribution created successfully');
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->post(route('distributions.store'), []);

        $response->assertSessionHasErrors(['name', 'type', 'target_type']);
    }

    public function test_store_validates_name_is_required()
    {
        $data = [
            'type' => 'immediate',
            'target_type' => 'all',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_type_is_valid()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'invalid_type',
            'target_type' => 'all',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_validates_target_type_is_valid()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'invalid_target',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['target_type']);
    }

    public function test_store_validates_group_id_exists_when_target_type_is_group()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'group',
            'group_id' => 999, // Non-existent group
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['group_id']);
    }

    public function test_store_validates_scheduled_at_is_date_when_provided()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'scheduled',
            'target_type' => 'all',
            'scheduled_at' => 'invalid-date',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['scheduled_at']);
    }

    public function test_store_validates_files_are_array_when_provided()
    {
        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'all',
            'files' => 'not-an-array',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['files']);
    }

    public function test_store_validates_each_file_is_valid()
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.txt', 300000); // 300MB exceeds 200MB limit

        $data = [
            'name' => 'Test Distribution',
            'type' => 'immediate',
            'target_type' => 'all',
            'files' => [$file],
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertSessionHasErrors(['files.0']);
    }

    public function test_store_with_valid_files()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $distribution = Distribution::factory()->make();
            $mock->shouldReceive('createDistribution')
                ->once()
                ->andReturn($distribution);
            $mock->shouldReceive('startDistribution')
                ->once();
        });

        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.txt', 1000); // 1KB file

        $data = [
            'name' => 'Test Distribution with Files',
            'type' => 'immediate',
            'target_type' => 'all',
            'files' => [$file],
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertRedirect(route('distributions.index'));
        $response->assertSessionHas('success', 'Distribution created successfully');
    }

    public function test_show_displays_distribution_details()
    {
        $distribution = Distribution::factory()
            ->hasFiles(3)
            ->hasTargets(2)
            ->create(['created_by' => $this->admin->id]);

        $response = $this->get(route('distributions.show', $distribution));

        $response->assertStatus(200);
        $response->assertViewIs('admin.distributions.show');
        $response->assertViewHas('distribution');
        
        $viewDistribution = $response->viewData('distribution');
        $this->assertTrue($viewDistribution->relationLoaded('files'));
        $this->assertTrue($viewDistribution->relationLoaded('targets'));
        $this->assertCount(3, $viewDistribution->files);
        $this->assertCount(2, $viewDistribution->targets);
    }

    public function test_show_loads_computer_relationship()
    {
        $distribution = Distribution::factory()
            ->hasTargets(1)
            ->create(['created_by' => $this->admin->id]);

        $response = $this->get(route('distributions.show', $distribution));

        $response->assertStatus(200);
        $viewDistribution = $response->viewData('distribution');
        $target = $viewDistribution->targets->first();
        $this->assertTrue($target->relationLoaded('computer'));
    }

    public function test_destroy_deletes_distribution()
    {
        $distribution = Distribution::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->delete(route('distributions.destroy', $distribution));

        $response->assertRedirect(route('distributions.index'));
        $response->assertSessionHas('success', 'Distribution deleted');
        
        $this->assertDatabaseMissing('distributions', ['id' => $distribution->id]);
    }

    public function test_store_creates_recurring_distribution()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $distribution = Distribution::factory()->make();
            $mock->shouldReceive('createDistribution')
                ->once()
                ->andReturn($distribution);
            $mock->shouldNotReceive('startDistribution');
        });

        $data = [
            'name' => 'Test Recurring Distribution',
            'type' => 'recurring',
            'target_type' => 'specific',
            'targets' => [1, 2, 3],
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertRedirect(route('distributions.index'));
        $response->assertSessionHas('success', 'Distribution created successfully');
    }

    public function test_store_passes_all_data_to_service()
    {
        $this->mock(DistributionService::class, function ($mock) {
            $distribution = Distribution::factory()->make();
            $mock->shouldReceive('createDistribution')
                ->once()
                ->with(
                    \Mockery::on(function ($data) {
                        return isset($data['name']) && 
                               isset($data['type']) && 
                               isset($data['description']) && 
                               isset($data['target_type']);
                    }),
                    $this->admin->id
                )
                ->andReturn($distribution);
            $mock->shouldReceive('startDistribution')
                ->once();
        });

        $data = [
            'name' => 'Complete Test Distribution',
            'type' => 'immediate',
            'description' => 'Test description',
            'target_type' => 'all',
        ];

        $response = $this->post(route('distributions.store'), $data);

        $response->assertRedirect(route('distributions.index'));
    }
}