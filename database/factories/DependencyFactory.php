<?php

namespace Database\Factories;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dependency>
 */
class DependencyFactory extends Factory
{
    protected $model = Dependency::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'predecessor_id' => Task::factory(),
            'successor_id' => Task::factory(),
            'type' => fake()->randomElement(Dependency::TYPES),
            'delay' => fake()->numberBetween(0, 5),
        ];
    }
}
