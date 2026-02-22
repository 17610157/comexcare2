<?php

namespace Database\Factories;

use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Computer>
 */
class ComputerFactory extends Factory
{
    protected $model = Computer::class;

    public function definition(): array
    {
        return [
            'computer_name' => $this->faker->company() . '-' . $this->faker->randomNumber(4),
            'mac_address' => $this->faker->macAddress(),
            'ip_address' => $this->faker->ipv4(),
            'agent_version' => $this->faker->semver(),
            'status' => $this->faker->randomElement(['online', 'offline', 'error']),
            'last_seen' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'system_info' => $this->faker->optional(0.8)->randomElement([
                ['os' => 'Windows 10', 'ram' => '8GB', 'cpu' => 'Intel i5'],
                ['os' => 'Windows 11', 'ram' => '16GB', 'cpu' => 'Intel i7'],
                ['os' => 'Ubuntu 22.04', 'ram' => '4GB', 'cpu' => 'AMD Ryzen 5'],
            ]),
            'group_id' => $this->faker->optional(0.5)->numberBetween(1, 10),
            'agent_config' => $this->faker->optional(0.3)->randomElement([
                ['heartbeat_interval' => 60, 'auto_update' => true],
                ['heartbeat_interval' => 30, 'auto_update' => false],
            ]),
        ];
    }

    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'online',
            'last_seen' => now(),
        ]);
    }

    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offline',
            'last_seen' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'error',
        ]);
    }
}