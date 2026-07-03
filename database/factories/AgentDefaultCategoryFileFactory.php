<?php

namespace Database\Factories;

use App\Models\AgentDefaultCategoryFile;
use App\Models\AgentDefaultCategoryRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentDefaultCategoryFileFactory extends Factory
{
    protected $model = AgentDefaultCategoryFile::class;

    public function definition(): array
    {
        return [
            'agent_default_category_route_id' => AgentDefaultCategoryRoute::factory(),
            'file_name' => $this->faker->word().'.'.$this->faker->fileExtension(),
            'file_path' => 'agent_defaults/'.$this->faker->randomNumber().'/'.$this->faker->uuid().'.'.$this->faker->fileExtension(),
            'checksum' => hash('sha256', $this->faker->uuid()),
            'file_size' => $this->faker->numberBetween(1024, 10485760),
        ];
    }
}
