<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => Holiday::TYPE_ONE_OFF,
            'date' => fake()->date('Y-m-d'),
            'month' => null,
            'day' => null,
            'label' => fake()->word().' holiday',
        ];
    }

    public function recurring(): static
    {
        return $this->state(fn () => [
            'type' => Holiday::TYPE_RECURRING,
            'date' => null,
            'month' => fake()->numberBetween(1, 12),
            'day' => fake()->numberBetween(1, 28),
        ]);
    }
}
