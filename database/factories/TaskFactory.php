<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'parent_id' => null,
            'name' => fake()->words(3, true),
            'start_date' => fake()->date('Y-m-d'),
            'end_date' => null,
            'duration' => fake()->numberBetween(1, 10),
            'priority' => Task::PRIORITY_NORMAL,
            'color' => fake()->hexColor(),
            'position' => 0,
        ];
    }
}
