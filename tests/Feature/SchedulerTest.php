<?php

namespace Tests\Feature;

use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Task;
use App\Services\SchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Uji Jadwal',
            'start_date' => '2025-01-03', // Jumat
            'weekend_mode' => Project::WEEKEND_OFF,
            'holiday_mode' => Project::HOLIDAY_NONE,
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ], $attributes));
    }

    private function makeTask(Project $project, string $name, int $duration, string $start, int $position = 0): Task
    {
        return Task::create([
            'project_id' => $project->id,
            'name' => $name,
            'start_date' => $start,
            'duration' => $duration,
            'priority' => Task::PRIORITY_NORMAL,
            'color' => '#3b82f6',
            'position' => $position,
        ]);
    }

    public function test_task_without_dependencies_starts_on_next_working_day(): void
    {
        $project = $this->makeProject();
        $this->makeTask($project, 'Tugas A', 1, '2025-01-03'); // Jumat

        app(SchedulerService::class)->schedule($project);

        $task = $project->tasks()->first();
        $this->assertSame('2025-01-03', $task->start_date->toDateString());
        $this->assertSame('2025-01-03', $task->end_date->toDateString());
    }

    public function test_fs_dependency_skips_weekend(): void
    {
        $project = $this->makeProject();
        $a = $this->makeTask($project, 'Tugas A', 1, '2025-01-03', 0); // Jumat
        $b = $this->makeTask($project, 'Tugas B', 2, '2025-01-03', 1);

        Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $a->id,
            'successor_id' => $b->id,
            'type' => 'FS',
            'delay' => 0,
        ]);

        app(SchedulerService::class)->schedule($project);

        $b = $project->tasks()->find($b->id);
        // FS: mulai setelah Jumat → Senin 6 Jan, durasi 2 hari kerja → Selasa 7 Jan.
        $this->assertSame('2025-01-06', $b->start_date->toDateString());
        $this->assertSame('2025-01-07', $b->end_date->toDateString());
    }

    public function test_fs_dependency_with_delay_and_project_holiday(): void
    {
        $project = $this->makeProject(['holiday_mode' => Project::HOLIDAY_CUSTOM]);
        Holiday::create([
            'project_id' => $project->id,
            'type' => Holiday::TYPE_ONE_OFF,
            'date' => '2025-01-07', // Selasa
            'label' => 'Libur uji',
        ]);

        $a = $this->makeTask($project, 'Tugas A', 1, '2025-01-06', 0); // Senin
        $b = $this->makeTask($project, 'Tugas B', 1, '2025-01-06', 1);

        Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $a->id,
            'successor_id' => $b->id,
            'type' => 'FS',
            'delay' => 1, // 1 hari kalender setelah selesai → Selasa (libur) → Rabu
        ]);

        app(SchedulerService::class)->schedule($project);

        $b = $project->tasks()->find($b->id);
        $this->assertSame('2025-01-08', $b->start_date->toDateString());
        $this->assertSame('2025-01-08', $b->end_date->toDateString());
    }

    public function test_ss_dependency_with_calendar_delay(): void
    {
        $project = $this->makeProject();
        $a = $this->makeTask($project, 'Tugas A', 5, '2025-01-06', 0); // Senin, 5 hari kerja → Jumat
        $b = $this->makeTask($project, 'Tugas B', 3, '2025-01-06', 1);

        Dependency::create([
            'project_id' => $project->id,
            'predecessor_id' => $a->id,
            'successor_id' => $b->id,
            'type' => 'SS',
            'delay' => 3, // 3 hari kalender setelah Senin → Kamis
        ]);

        app(SchedulerService::class)->schedule($project);

        $b = $project->tasks()->find($b->id);
        $this->assertSame('2025-01-09', $b->start_date->toDateString());
        $this->assertSame('2025-01-13', $b->end_date->toDateString()); // Kamis, Jumat, Senin
    }

    public function test_summary_task_dates_aggregate_from_children(): void
    {
        $project = $this->makeProject();
        $parent = $this->makeTask($project, 'Induk', 1, '2025-01-06', 0);
        $childA = $this->makeTask($project, 'Anak A', 2, '2025-01-06', 1);
        $childB = $this->makeTask($project, 'Anak B', 2, '2025-01-08', 2);

        $childA->update(['parent_id' => $parent->id]);
        $childB->update(['parent_id' => $parent->id]);

        app(SchedulerService::class)->schedule($project);

        $parent = $project->tasks()->find($parent->id);
        $this->assertSame('2025-01-06', $parent->start_date->toDateString());
        $this->assertSame('2025-01-09', $parent->end_date->toDateString());
        $this->assertSame(4, $parent->duration);
    }

    public function test_schedule_survives_dependency_cycle(): void
    {
        $project = $this->makeProject();
        $a = $this->makeTask($project, 'A', 2, '2025-01-06', 0);
        $b = $this->makeTask($project, 'B', 3, '2025-01-06', 1);

        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $a->id, 'successor_id' => $b->id, 'type' => 'FS', 'delay' => 0]);
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $b->id, 'successor_id' => $a->id, 'type' => 'FS', 'delay' => 0]);

        $schedule = app(SchedulerService::class)->schedule($project);

        $this->assertNotNull($schedule['project_start']);
        $this->assertSame(2, $schedule['tasks']->count());
    }
}
