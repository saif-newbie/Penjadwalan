<?php

namespace Database\Factories;

use App\Models\Resource;
use App\Models\ResourceHoliday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceHoliday>
 */
class ResourceHolidayFactory extends Factory
{
    protected $model = ResourceHoliday::class;

    public function definition(): array
    {
        return [
            'resource_id' => Resource::factory(),
            'date' => fake()->date('Y-m-d'),
            'label' => fake()->optional()->word(),
        ];
    }
}
