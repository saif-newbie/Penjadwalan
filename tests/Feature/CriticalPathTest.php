<?php

namespace Tests\Feature;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Task;
use App\Services\CriticalPathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalPathTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jaring aktivitas A-J (sample PERT):
     * Durasi A1 B2 C3 D5 E7 F4 G12 H21 I2 J3.
     * Dependensi D<-A, E<-B, F<-B, G<-C, H<-D,E, I<-G, J<-F,H,I.
     * Jalur: ADHJ=30, BEHJ=33, BFJ=9, CGIJ=20 → kritis BEHJ.
     */
    private function seedNetwork(): Project
    {
        $project = Project::create([
            'name' => 'Jaring A-J',
            'start_date' => '2025-09-01',
            'weekend_mode' => Project::WEEKEND_OFF,
            'holiday_mode' => Project::HOLIDAY_NONE,
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ]);

        $durations = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 5, 'E' => 7, 'F' => 4, 'G' => 12, 'H' => 21, 'I' => 2, 'J' => 3];

        $tasks = [];
        foreach ($durations as $key => $duration) {
            $tasks[$key] = Task::create([
                'project_id' => $project->id,
                'name' => 'Aktivitas '.$key,
                'start_date' => '2025-09-01',
                'duration' => $duration,
                'priority' => Task::PRIORITY_NORMAL,
                'color' => '#3b82f6',
                'position' => array_search($key, array_keys($durations)),
            ]);
        }

        $edges = [
            ['D', 'A'], ['E', 'B'], ['F', 'B'], ['G', 'C'],
            ['H', 'D'], ['H', 'E'], ['I', 'G'],
            ['J', 'F'], ['J', 'H'], ['J', 'I'],
        ];

        foreach ($edges as [$successor, $predecessor]) {
            Dependency::create([
                'project_id' => $project->id,
                'predecessor_id' => $tasks[$predecessor]->id,
                'successor_id' => $tasks[$successor]->id,
                'type' => 'FS',
                'delay' => 0,
            ]);
        }

        return $project;
    }

    public function test_critical_path_is_behj_with_duration_33(): void
    {
        $project = $this->seedNetwork();

        $analysis = app(CriticalPathService::class)->analyze($project);

        $this->assertCount(4, $analysis['paths']);
        $this->assertSame(33, $analysis['critical_path']['duration']);
        $this->assertSame(
            ['Aktivitas B', 'Aktivitas E', 'Aktivitas H', 'Aktivitas J'],
            $analysis['critical_path']['names']
        );

        $criticalNames = collect($analysis['critical_task_ids'])
            ->map(fn (int $id) => Task::find($id)->name)
            ->all();
        $this->assertSame(['Aktivitas B', 'Aktivitas E', 'Aktivitas H', 'Aktivitas J'], $criticalNames);
    }

    public function test_all_paths_are_enumerated_and_sorted(): void
    {
        $project = $this->seedNetwork();

        $analysis = app(CriticalPathService::class)->analyze($project);
        $durations = collect($analysis['paths'])->pluck('duration')->all();

        $this->assertSame([33, 30, 20, 9], $durations);
    }

    public function test_independent_tasks_are_single_paths(): void
    {
        $project = Project::create([
            'name' => 'Tanpa dependensi',
            'start_date' => '2025-09-01',
            'weekend_mode' => Project::WEEKEND_OFF,
            'holiday_mode' => Project::HOLIDAY_NONE,
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ]);

        Task::create(['project_id' => $project->id, 'name' => 'X', 'start_date' => '2025-09-01', 'duration' => 4, 'priority' => 'normal', 'color' => '#000000', 'position' => 0]);
        Task::create(['project_id' => $project->id, 'name' => 'Y', 'start_date' => '2025-09-01', 'duration' => 6, 'priority' => 'normal', 'color' => '#000000', 'position' => 1]);

        $analysis = app(CriticalPathService::class)->analyze($project);

        $this->assertCount(2, $analysis['paths']);
        $this->assertSame('Y', $analysis['critical_path']['names'][0]);
        $this->assertSame(6, $analysis['critical_path']['duration']);
    }
}
