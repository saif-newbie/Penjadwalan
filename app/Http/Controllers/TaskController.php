<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\SchedulerService;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function __construct(private SchedulerService $scheduler) {}

    public function store(Project $project, StoreTaskRequest $request): RedirectResponse
    {
        $position = (int) $project->tasks()->max('position') + 1;

        $project->tasks()->create(array_merge($request->validated(), ['position' => $position]));

        $this->scheduler->schedule($project);

        return back()->with('success', 'Tugas ditambahkan.');
    }

    public function update(Project $project, Task $task, UpdateTaskRequest $request): RedirectResponse
    {
        $task->update($request->validated());

        $this->scheduler->schedule($project);

        return back()->with('success', 'Tugas diperbarui.');
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        $task->delete();

        $this->scheduler->schedule($project);

        return back()->with('success', 'Tugas dihapus.');
    }
}
