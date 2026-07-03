<?php

namespace Database\Factories;

use App\Models\AgentDefaultCategory;
use App\Models\AgentDefaultCategoryRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentDefaultCategoryRouteFactory extends Factory
{
    protected $model = AgentDefaultCategoryRoute::class;

    public function definition(): array
    {
        return [
            'agent_default_category_id' => AgentDefaultCategory::factory(),
            'route_pattern' => $this->faker->randomElement([
                '\\\\srv\\files\\templates',
                '\\\\srv\\backup\\documents',
                '\\\\srv\\docs\\reports',
            ]),
            'label' => $this->faker->optional()->word(),
            'download_path_index' => $this->faker->optional()->numberBetween(1, 10),
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }
}
