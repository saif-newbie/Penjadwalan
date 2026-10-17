<?php

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Task;
use App\Services\SchedulerService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(SchedulerService $scheduler): void
    {
        $this->seedSdlcProject($scheduler);
        $this->seedNetworkProject($scheduler);
    }

    /**
     * Sample (a): proyek SDLC, 6 tugas, 30 Jun - 31 Jul 2025,
     * weekend libur, libur custom one-off 17 Juli.
     */
    private function seedSdlcProject(SchedulerService $scheduler): void
    {
        $project = Project::create([
            'name' => 'Pengembangan Sistem SDLC',
            'organization' => 'PT Analisis Arsip Digital',
            'website' => 'https://example.com/sdlc',
            'description' => 'Proyek pengembangan sistem informasi mengikuti siklus SDLC: analisis, perancangan, implementasi, pengujian, dan penerapan.',
            'start_date' => '2025-06-30',
            'weekend_mode' => Project::WEEKEND_OFF,
            'holiday_mode' => Project::HOLIDAY_CUSTOM,
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ]);

        foreach (Role::TEMPLATES[Role::TEMPLATE_SOFTWARE] as $i => $name) {
            Role::create(['project_id' => $project->id, 'name' => $name, 'sort_order' => $i]);
        }

        Holiday::create([
            'project_id' => $project->id,
            'type' => Holiday::TYPE_ONE_OFF,
            'date' => '2025-07-17',
            'label' => 'Libur Nasional 17 Juli',
        ]);

        Holiday::create([
            'project_id' => $project->id,
            'type' => Holiday::TYPE_RECURRING,
            'month' => 8,
            'day' => 17,
            'label' => 'Kemerdekaan (setiap tahun)',
        ]);

        $tasks = collect([
            ['name' => 'Analisis Kebutuhan', 'duration' => 5, 'color' => '#3b82f6'],
            ['name' => 'Analisis Sistem', 'duration' => 8, 'color' => '#0ea5e9'],
            ['name' => 'Perancangan', 'duration' => 7, 'color' => '#8b5cf6'],
            ['name' => 'Implementasi', 'duration' => 5, 'color' => '#f59e0b'],
            ['name' => 'Pengujian', 'duration' => 2, 'color' => '#ef4444'],
            ['name' => 'Penerapan & Pemeliharaan', 'duration' => 2, 'color' => '#22c55e'],
        ])->map(fn (array $t, int $i) => Task::create([
            'project_id' => $project->id,
            'name' => $t['name'],
            'start_date' => '2025-06-30',
            'duration' => $t['duration'],
            'priority' => Task::PRIORITY_NORMAL,
            'color' => $t['color'],
            'position' => $i,
        ]));

        // Analisis Sistem SS dari Analisis Kebutuhan (delay 3),
        // Perancangan SS dari Analisis Sistem (delay 5),
        // Implementasi/Pengujian/Penerapan berantai FS.
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $tasks[0]->id, 'successor_id' => $tasks[1]->id, 'type' => 'SS', 'delay' => 3]);
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $tasks[1]->id, 'successor_id' => $tasks[2]->id, 'type' => 'SS', 'delay' => 5]);
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $tasks[2]->id, 'successor_id' => $tasks[3]->id, 'type' => 'FS', 'delay' => 0]);
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $tasks[3]->id, 'successor_id' => $tasks[4]->id, 'type' => 'FS', 'delay' => 0]);
        Dependency::create(['project_id' => $project->id, 'predecessor_id' => $tasks[4]->id, 'successor_id' => $tasks[5]->id, 'type' => 'FS', 'delay' => 0]);

        // Tim: 1 project manager, 2 analis, 2 programmer, 2 tester.
        $members = collect([
            ['name' => 'Sari Wulandari', 'role' => 'Project Manager', 'salary' => 15000000],
            ['name' => 'Ahmad Fauzi', 'role' => 'Analis', 'salary' => 10000000],
            ['name' => 'Budi Santoso', 'role' => 'Analis', 'salary' => 10000000],
            ['name' => 'Citra Lestari', 'role' => 'Programmer', 'salary' => 12000000],
            ['name' => 'Dian Permata', 'role' => 'Programmer', 'salary' => 12000000],
            ['name' => 'Eko Prasetyo', 'role' => 'Tester', 'salary' => 9000000],
            ['name' => 'Fitri Handayani', 'role' => 'Tester', 'salary' => 9000000],
        ])->map(fn (array $m, int $i) => Resource::create([
            'project_id' => $project->id,
            'name' => $m['name'],
            'phone' => '0812-1000-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
            'email' => str()->slug($m['name'], '.').'@example.com',
            'role' => $m['role'],
            'salary' => $m['salary'],
        ]));

        // Libur pribadi salah satu tester.
        $members[6]->holidays()->create(['date' => '2025-07-10', 'label' => 'Cuti pribadi']);

        // Alokasi: PM di semua tugas, analis di tugas analisis/perancangan,
        // programmer di implementasi, tester di pengujian & penerapan.
        foreach ($tasks as $task) {
            Allocation::create(['task_id' => $task->id, 'resource_id' => $members[0]->id]);
        }
        foreach ([0, 1, 2] as $i) {
            Allocation::create(['task_id' => $tasks[$i]->id, 'resource_id' => $members[1]->id]);
            Allocation::create(['task_id' => $tasks[$i]->id, 'resource_id' => $members[2]->id]);
        }
        Allocation::create(['task_id' => $tasks[3]->id, 'resource_id' => $members[3]->id]);
        Allocation::create(['task_id' => $tasks[3]->id, 'resource_id' => $members[4]->id]);
        Allocation::create(['task_id' => $tasks[4]->id, 'resource_id' => $members[5]->id]);
        Allocation::create(['task_id' => $tasks[4]->id, 'resource_id' => $members[6]->id]);
        Allocation::create(['task_id' => $tasks[5]->id, 'resource_id' => $members[5]->id]);
        Allocation::create(['task_id' => $tasks[5]->id, 'resource_id' => $members[6]->id]);

        $scheduler->schedule($project);
    }

    /**
     * Sample (b): 10 aktivitas A-J untuk uji jalur kritis.
     * Durasi: A1 B2 C3 D5 E7 F4 G12 H21 I2 J3.
     * Dependensi: D<-A, E<-B, F<-B, G<-C, H<-D,E, I<-G, J<-F,H,I.
     * Jalur: ADHJ=30, BEHJ=33, BFJ=9, CGIJ=20 -> kritis BEHJ (33).
     */
    private function seedNetworkProject(SchedulerService $scheduler): void
    {
        $project = Project::create([
            'name' => 'Jaring Aktivitas A-J (PERT)',
            'organization' => 'PT Analisis Arsip Digital',
            'website' => 'https://example.com/pert',
            'description' => 'Sample jaringan aktivitas 10 tugas untuk demonstrasi PERT dan critical path. Jalur kritis BEHJ = 33 hari.',
            'start_date' => '2025-09-01',
            'weekend_mode' => Project::WEEKEND_OFF,
            'holiday_mode' => Project::HOLIDAY_NONE,
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ]);

        foreach (Role::TEMPLATES[Role::TEMPLATE_DEFAULT] as $i => $name) {
            Role::create(['project_id' => $project->id, 'name' => $name, 'sort_order' => $i]);
        }

        $durations = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 5, 'E' => 7, 'F' => 4, 'G' => 12, 'H' => 21, 'I' => 2, 'J' => 3];
        $colors = ['A' => '#3b82f6', 'B' => '#3b82f6', 'C' => '#3b82f6', 'D' => '#8b5cf6', 'E' => '#8b5cf6', 'F' => '#f59e0b', 'G' => '#f59e0b', 'H' => '#ef4444', 'I' => '#22c55e', 'J' => '#0ea5e9'];

        $tasks = [];
        foreach ($durations as $key => $duration) {
            $tasks[$key] = Task::create([
                'project_id' => $project->id,
                'name' => 'Aktivitas '.$key,
                'start_date' => '2025-09-01',
                'duration' => $duration,
                'priority' => Task::PRIORITY_NORMAL,
                'color' => $colors[$key],
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

        $scheduler->schedule($project);
    }
}
