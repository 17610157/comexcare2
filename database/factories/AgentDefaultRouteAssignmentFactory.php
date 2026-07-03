<?php

namespace Database\Factories;

use App\Models\AgentDefaultCategoryRoute;
use App\Models\AgentDefaultRouteAssignment;
use App\Models\Computer;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentDefaultRouteAssignmentFactory extends Factory
{
    protected $model = AgentDefaultRouteAssignment::class;

    public function definition(): array
    {
        return [
            'agent_default_category_route_id' => AgentDefaultCategoryRoute::factory(),
            'assignable_type' => Computer::class,
            'assignable_id' => Computer::factory(),
        ];
    }

    public function forGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'assignable_type' => Group::class,
            'assignable_id' => Group::factory(),
        ]);
    }
}
