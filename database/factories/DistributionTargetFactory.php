<?php

namespace Database\Factories;

use App\Models\DistributionTarget;
use App\Models\Distribution;
use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DistributionTarget>
 */
class DistributionTargetFactory extends Factory
{
    protected $model = DistributionTarget::class;

    public function definition(): array
    {
        return [
            'distribution_id' => Distribution::factory(),
            'computer_id' => Computer::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'completed', 'failed']),
            'progress' => $this->faker->numberBetween(0, 100),
            'attempts' => $this->faker->numberBetween(0, 4),
            'next_retry_at' => $this->faker->optional(0.3)->dateTimeBetween('now', '+1 day'),
            'error_message' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'progress' => 0,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'progress' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress' => 100,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'progress' => $this->faker->numberBetween(0, 100),
            'error_message' => $this->faker->sentence(),
        ]);
    }
}