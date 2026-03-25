<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Group',
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['tienda', 'almacen', 'cedis', 'vendedor', 'especial', 'cobranza']),
        ];
    }
}
