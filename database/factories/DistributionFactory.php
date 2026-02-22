<?php

namespace Database\Factories;

use App\Models\Distribution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Distribution>
 */
class DistributionFactory extends Factory
{
    protected $model = Distribution::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['immediate', 'scheduled', 'recurring']),
            'schedule' => $this->faker->optional(0.7)->randomElement([
                ['frequency' => 'daily', 'time' => '09:00'],
                ['frequency' => 'weekly', 'days' => ['monday', 'wednesday', 'friday'], 'time' => '14:00'],
                ['frequency' => 'monthly', 'day' => 1, 'time' => '06:00'],
            ]),
            'description' => $this->faker->optional(0.5)->sentence(),
            'created_by' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'failed']),
            'scheduled_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', '+1 week'),
        ];
    }

    public function withFiles(int $count = 1): static
    {
        return $this->has(DistributionFile::factory()->count($count), 'files');
    }

    public function withTargets(int $count = 1): static
    {
        return $this->has(DistributionTarget::factory()->count($count), 'targets');
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    public function immediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'immediate',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'scheduled',
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recurring',
        ]);
    }


}