<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'organization' => fake()->company(),
            'website' => fake()->url(),
            'description' => fake()->paragraph(),
            'start_date' => fake()->date('Y-m-d'),
            'weekend_mode' => Project::WEEKEND_OFF,
            'holiday_mode' => Project::HOLIDAY_NONE,
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ];
    }
}
