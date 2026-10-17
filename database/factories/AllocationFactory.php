<?php

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\Resource;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Allocation>
 */
class AllocationFactory extends Factory
{
    protected $model = Allocation::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'resource_id' => Resource::factory(),
        ];
    }
}
