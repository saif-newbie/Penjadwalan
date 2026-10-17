<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProjectRequest;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Project;
use App\Services\CriticalPathService;
use App\Services\ProjectTransferService;
use App\Services\SchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class ProjectController extends Controller
{
    public function __construct(
        private SchedulerService $scheduler,
        private CriticalPathService $criticalPath,
        private ProjectTransferService $transfer,
    ) {}

    public function index(): View
    {
        return view('projects.index', [
            'projects' => Project::withCount('tasks')->latest()->get(),
        ]);
    }

    public function show(Project $project): View
    {
        $schedule = $this->scheduler->schedule($project);

        return view('projects.show', [
            'project' => $project->load([
                'roles',
                'holidays',
                'tasks.children',
                'dependencies.predecessor',
                'dependencies.successor',
                'resources.holidays',
                'resources.allocations',
            ]),
            'schedule' => $schedule,
            'critical' => $this->criticalPath->analyze($project),
        ]);
    }

    public function updateSettings(Project $project, UpdateSettingsRequest $request): RedirectResponse
    {
        $project->update($request->validated());

        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function export(Project $project): Response
    {
        $payload = json_encode($this->transfer->export($project), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return response($payload, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="project-'.$project->id.'.json"',
        ]);
    }

    public function import(ImportProjectRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $decoded = json_decode((string) file_get_contents($file->getRealPath()), true);

        if (! is_array($decoded)) {
            return back()->withErrors(['file' => 'File JSON tidak valid.']);
        }

        try {
            $project = $this->transfer->import($decoded);
        } catch (RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil diimpor.');
    }
}
