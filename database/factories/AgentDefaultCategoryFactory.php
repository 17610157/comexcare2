<?php

namespace Database\Factories;

use App\Models\AgentDefaultCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentDefaultCategoryFactory extends Factory
{
    protected $model = AgentDefaultCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word().' Category',
            'description' => $this->faker->sentence(),
        ];
    }
}
