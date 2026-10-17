<?php

namespace App\Services;

use App\Models\Allocation;
use App\Models\Dependency;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Export / import proyek dalam format JSON.
 */
class ProjectTransferService
{
    public function __construct(private SchedulerService $scheduler) {}

    public function export(Project $project): array
    {
        $project->load(['tasks', 'dependencies', 'resources.holidays', 'resources.allocations', 'holidays', 'roles']);

        return [
            'format' => 'mini-gantt-project',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'project' => [
                'name' => $project->name,
                'organization' => $project->organization,
                'website' => $project->website,
                'description' => $project->description,
                'start_date' => $project->start_date?->toDateString(),
                'weekend_mode' => $project->weekend_mode,
                'holiday_mode' => $project->holiday_mode,
                'date_format' => $project->date_format,
                'font_size' => $project->font_size,
            ],
            'roles' => $project->roles->pluck('name')->values()->all(),
            'holidays' => $project->holidays->map(fn (Holiday $h) => [
                'type' => $h->type,
                'date' => $h->date?->toDateString(),
                'month' => $h->month,
                'day' => $h->day,
                'label' => $h->label,
            ])->values()->all(),
            // Task memakai index sebagai referensi agar parentId & dependensi tetap valid saat import.
            'tasks' => $project->tasks->sortBy('position')->values()->map(fn (Task $t, int $i) => [
                'key' => $i,
                'parent_key' => $t->parent_id === null
                    ? null
                    : $project->tasks->search(fn (Task $p) => $p->id === $t->parent_id),
                'name' => $t->name,
                'start_date' => $t->start_date?->toDateString(),
                'duration' => $t->duration,
                'priority' => $t->priority,
                'color' => $t->color,
                'position' => $t->position,
            ])->all(),
            'dependencies' => $project->dependencies->map(fn (Dependency $d) => [
                'predecessor_key' => $project->tasks->search(fn (Task $t) => $t->id === $d->predecessor_id),
                'successor_key' => $project->tasks->search(fn (Task $t) => $t->id === $d->successor_id),
                'type' => $d->type,
                'delay' => $d->delay,
            ])->values()->all(),
            'resources' => $project->resources->map(fn (Resource $r) => [
                'name' => $r->name,
                'phone' => $r->phone,
                'email' => $r->email,
                'role' => $r->role,
                'salary' => $r->salary,
                'holidays' => $r->holidays->map(fn ($h) => [
                    'date' => $h->date->toDateString(),
                    'label' => $h->label,
                ])->values()->all(),
                'task_keys' => $r->allocations
                    ->map(fn (Allocation $a) => $project->tasks->search(fn (Task $t) => $t->id === $a->task_id))
                    ->filter()
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }

    public function import(array $payload): Project
    {
        if (($payload['format'] ?? null) !== 'mini-gantt-project') {
            throw new RuntimeException('Format file tidak dikenali. Gunakan file export dari Mini GanttProject.');
        }

        return DB::transaction(function () use ($payload) {
            $data = $payload['project'] ?? [];

            $project = Project::create([
                'name' => $data['name'] ?? 'Imported Project',
                'organization' => $data['organization'] ?? null,
                'website' => $data['website'] ?? null,
                'description' => $data['description'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'weekend_mode' => $data['weekend_mode'] ?? Project::WEEKEND_OFF,
                'holiday_mode' => $data['holiday_mode'] ?? Project::HOLIDAY_NONE,
                'date_format' => $data['date_format'] ?? 'mm/dd/yyyy',
                'font_size' => $data['font_size'] ?? 14,
            ]);

            foreach (($payload['roles'] ?? []) as $i => $name) {
                Role::create(['project_id' => $project->id, 'name' => $name, 'sort_order' => $i]);
            }

            foreach (($payload['holidays'] ?? []) as $holiday) {
                Holiday::create([
                    'project_id' => $project->id,
                    'type' => $holiday['type'] ?? Holiday::TYPE_ONE_OFF,
                    'date' => $holiday['date'] ?? null,
                    'month' => $holiday['month'] ?? null,
                    'day' => $holiday['day'] ?? null,
                    'label' => $holiday['label'] ?? 'Holiday',
                ]);
            }

            /** @var array<int, int> $keyToId */
            $keyToId = [];

            foreach (($payload['tasks'] ?? []) as $task) {
                $created = Task::create([
                    'project_id' => $project->id,
                    'parent_id' => null, // diisi setelah semua tugas ada
                    'name' => $task['name'] ?? 'Task',
                    'start_date' => $task['start_date'] ?? $project->start_date,
                    'duration' => $task['duration'] ?? 1,
                    'priority' => $task['priority'] ?? Task::PRIORITY_NORMAL,
                    'color' => $task['color'] ?? '#3b82f6',
                    'position' => $task['position'] ?? 0,
                ]);
                $keyToId[(int) ($task['key'] ?? count($keyToId))] = $created->id;
            }

            foreach (($payload['tasks'] ?? []) as $task) {
                $parentKey = $task['parent_key'] ?? null;
                if ($parentKey !== null && isset($keyToId[(int) $parentKey])) {
                    Task::whereKey($keyToId[(int) ($task['key'] ?? 0)])->update(['parent_id' => $keyToId[(int) $parentKey]]);
                }
            }

            foreach (($payload['dependencies'] ?? []) as $dep) {
                $pred = $keyToId[(int) ($dep['predecessor_key'] ?? -1)] ?? null;
                $succ = $keyToId[(int) ($dep['successor_key'] ?? -1)] ?? null;

                if ($pred && $succ && $pred !== $succ) {
                    Dependency::create([
                        'project_id' => $project->id,
                        'predecessor_id' => $pred,
                        'successor_id' => $succ,
                        'type' => $dep['type'] ?? 'FS',
                        'delay' => $dep['delay'] ?? 0,
                    ]);
                }
            }

            foreach (($payload['resources'] ?? []) as $resource) {
                $member = Resource::create([
                    'project_id' => $project->id,
                    'name' => $resource['name'] ?? 'Member',
                    'phone' => $resource['phone'] ?? null,
                    'email' => $resource['email'] ?? null,
                    'role' => $resource['role'] ?? null,
                    'salary' => $resource['salary'] ?? null,
                ]);

                foreach (($resource['holidays'] ?? []) as $holiday) {
                    $member->holidays()->create([
                        'date' => $holiday['date'],
                        'label' => $holiday['label'] ?? null,
                    ]);
                }

                foreach (($resource['task_keys'] ?? []) as $key) {
                    if (isset($keyToId[(int) $key])) {
                        Allocation::create(['task_id' => $keyToId[(int) $key], 'resource_id' => $member->id]);
                    }
                }
            }

            $this->scheduler->schedule($project);

            return $project;
        });
    }
}
