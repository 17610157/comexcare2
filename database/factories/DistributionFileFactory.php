<?php

namespace Database\Factories;

use App\Models\DistributionFile;
use App\Models\Distribution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DistributionFile>
 */
class DistributionFileFactory extends Factory
{
    protected $model = DistributionFile::class;

    public function definition(): array
    {
        return [
            'distribution_id' => Distribution::factory(),
            'file_name' => $this->faker->words(3, true) . '.' . $this->faker->fileExtension(),
            'file_path' => 'distributions/' . $this->faker->uuid() . '.' . $this->faker->fileExtension(),
            'checksum' => $this->faker->sha256(),
            'file_size' => $this->faker->numberBetween(1024, 100 * 1024 * 1024), // 1KB to 100MB
        ];
    }
}