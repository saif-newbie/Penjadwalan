<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\CriticalPathService;
use App\Services\SchedulerService;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint JSON yang dipakai JavaScript/SVG untuk menggambar
 * Gantt chart, resource chart, dan PERT chart.
 */
class ChartController extends Controller
{
    public function __construct(
        private SchedulerService $scheduler,
        private CriticalPathService $criticalPath,
    ) {}

    public function gantt(Project $project): JsonResponse
    {
        $schedule = $this->scheduler->schedule($project);
        $project->load(['tasks', 'dependencies']);

        $criticalIds = $this->criticalPath->analyze($project)['critical_task_ids'];

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'start' => $schedule['project_start'],
                'end' => $schedule['project_end'],
                'weekend_mode' => $project->weekend_mode,
                'date_format' => $project->date_format,
                'font_size' => $project->font_size,
            ],
            'tasks' => $schedule['tasks']->map(fn (array $task) => $task + [
                'is_critical' => in_array($task['id'], $criticalIds, true),
            ]),
            'dependencies' => $project->dependencies->map(fn ($dep) => [
                'id' => $dep->id,
                'predecessor_id' => $dep->predecessor_id,
                'successor_id' => $dep->successor_id,
                'type' => $dep->type,
                'delay' => $dep->delay,
            ]),
            'holidays' => $project->holidays->map(fn ($h) => [
                'type' => $h->type,
                'date' => $h->date?->toDateString(),
                'month' => $h->month,
                'day' => $h->day,
                'label' => $h->label,
            ]),
        ]);
    }

    public function resources(Project $project): JsonResponse
    {
        $schedule = $this->scheduler->schedule($project);

        $project->load(['tasks', 'resources.holidays', 'resources.allocations']);

        $taskIndex = $schedule['tasks']->keyBy('id');

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'start' => $schedule['project_start'],
                'end' => $schedule['project_end'],
                'date_format' => $project->date_format,
            ],
            'resources' => $project->resources->map(fn ($resource) => [
                'id' => $resource->id,
                'name' => $resource->name,
                'role' => $resource->role,
                'salary' => $resource->salary,
                'holidays' => $resource->holidays->map(fn ($h) => [
                    'date' => $h->date->toDateString(),
                    'label' => $h->label,
                ]),
                'allocations' => $resource->allocations->map(fn ($allocation) => $taskIndex->get($allocation->task_id))->filter()->values(),
            ]),
        ]);
    }

    public function pert(Project $project): JsonResponse
    {
        $schedule = $this->scheduler->schedule($project);
        $project->load(['tasks', 'dependencies']);

        $criticalIds = $this->criticalPath->analyze($project)['critical_task_ids'];

        $taskRows = $schedule['tasks']->keyBy('id');

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'date_format' => $project->date_format,
            ],
            'nodes' => $project->tasks->filter(fn ($t) => $t->parent_id === null)->map(fn ($task) => [
                'id' => $task->id,
                'name' => $task->name,
                'duration' => $task->duration,
                'start' => $taskRows[$task->id]['start_date'] ?? null,
                'end' => $taskRows[$task->id]['end_date'] ?? null,
                'color' => $task->color,
                'is_critical' => in_array($task->id, $criticalIds, true),
            ])->values(),
            'edges' => $project->dependencies->map(fn ($dep) => [
                'from' => $dep->predecessor_id,
                'to' => $dep->successor_id,
                'type' => $dep->type,
                'delay' => $dep->delay,
            ]),
        ]);
    }

    public function criticalPath(Project $project): JsonResponse
    {
        $analysis = $this->criticalPath->analyze($project);

        return response()->json($analysis);
    }
}
