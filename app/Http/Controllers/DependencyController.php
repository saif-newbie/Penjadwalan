<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDependencyRequest;
use App\Models\Dependency;
use App\Models\Project;
use App\Services\SchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DependencyController extends Controller
{
    public function __construct(private SchedulerService $scheduler) {}

    public function store(Project $project, StoreDependencyRequest $request): RedirectResponse
    {
        $exists = $project->dependencies()
            ->where('predecessor_id', $request->input('predecessor_id'))
            ->where('successor_id', $request->input('successor_id'))
            ->exists();

        if ($exists) {
            return back()->withErrors(['successor_id' => 'Dependensi tersebut sudah ada.']);
        }

        $project->dependencies()->create(array_merge($request->validated(), [
            'delay' => $request->input('delay', 0),
        ]));

        $this->scheduler->schedule($project);

        return back()->with('success', 'Dependensi ditambahkan. Jadwal dihitung ulang.');
    }

    public function update(Request $request, Project $project, Dependency $dependency): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:FS,SS,FF,SF'],
            'delay' => ['nullable', 'integer', 'min:0', 'max:365'],
        ]);

        $dependency->update(['type' => $validated['type'], 'delay' => $validated['delay'] ?? 0]);

        $this->scheduler->schedule($project);

        return back()->with('success', 'Dependensi diperbarui. Jadwal dihitung ulang.');
    }

    public function destroy(Project $project, Dependency $dependency): RedirectResponse
    {
        $dependency->delete();

        $this->scheduler->schedule($project);

        return back()->with('success', 'Dependensi dihapus. Jadwal dihitung ulang.');
    }
}
