<?php

namespace Database\Factories;

use App\Models\AgentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentVersion>
 */
class AgentVersionFactory extends Factory
{
    protected $model = AgentVersion::class;

    public function definition(): array
    {
        return [
            'version' => $this->faker->semver(),
            'channel' => $this->faker->randomElement(['stable', 'beta', 'alpha', 'dev']),
            'checksum' => $this->faker->sha256(),
            'changelog' => $this->faker->optional(0.8)->paragraphs(2),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'release_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'download_url' => $this->faker->optional(0.7)->url(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function stable(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'stable',
        ]);
    }

    public function beta(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'beta',
        ]);
    }

    public function alpha(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'alpha',
        ]);
    }

    public function dev(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'dev',
        ]);
    }
}