<?php

namespace Database\Factories;

use App\Models\Command;
use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Command>
 */
class CommandFactory extends Factory
{
    protected $model = Command::class;

    public function definition(): array
    {
        $commandTypes = ['download', 'update', 'restart', 'inventory', 'custom'];
        $commandType = $this->faker->randomElement($commandTypes);

        $data = match ($commandType) {
            'download' => [
                'file_id' => $this->faker->numberBetween(1, 1000),
                'distribution_target_id' => $this->faker->numberBetween(1, 100),
            ],
            'update' => [
                'version' => $this->faker->semver(),
                'force' => $this->faker->boolean(),
            ],
            'restart' => [
                'delay' => $this->faker->numberBetween(0, 300),
                'message' => $this->faker->optional(0.5)->sentence(),
            ],
            'inventory' => [
                'detailed' => $this->faker->boolean(),
                'include_software' => $this->faker->boolean(),
            ],
            'custom' => [
                'command' => $this->faker->word(),
                'parameters' => $this->faker->words(3),
            ],
            default => [],
        };

        return [
            'computer_id' => Computer::factory(),
            'type' => $commandType,
            'data' => $data,
            'status' => $this->faker->randomElement(['pending', 'sent', 'completed', 'failed']),
            'sent_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 day', 'now'),
            'completed_at' => $this->faker->optional(0.5)->dateTimeBetween('-1 day', 'now'),
            'response' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'sent_at' => null,
            'completed_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'sent_at' => $this->faker->dateTimeBetween('-2 hours', '-1 hour'),
            'completed_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response' => $this->faker->sentence(),
        ]);
    }

    public function download(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'download',
            'data' => array_merge([
                'file_id' => $this->faker->numberBetween(1, 1000),
                'distribution_target_id' => $this->faker->numberBetween(1, 100),
            ], $data),
        ]);
    }

    public function update(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'update',
            'data' => array_merge([
                'version' => $this->faker->semver(),
                'force' => $this->faker->boolean(),
            ], $data),
        ]);
    }

    public function restart(array $data = []): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'restart',
            'data' => array_merge([
                'delay' => $this->faker->numberBetween(0, 300),
                'message' => $this->faker->optional(0.5)->sentence(),
            ], $data),
        ]);
    }
}