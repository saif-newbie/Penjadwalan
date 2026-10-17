<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHolidayRequest;
use App\Models\Holiday;
use App\Models\Project;
use App\Services\SchedulerService;
use Illuminate\Http\RedirectResponse;

class HolidayController extends Controller
{
    public function __construct(private SchedulerService $scheduler) {}

    public function store(Project $project, StoreHolidayRequest $request): RedirectResponse
    {
        $project->holidays()->create($request->validated());

        if ($project->holiday_mode === Project::HOLIDAY_NONE) {
            $project->update(['holiday_mode' => Project::HOLIDAY_CUSTOM]);
        }

        $this->scheduler->schedule($project);

        return back()->with('success', 'Libur ditambahkan. Jadwal dihitung ulang.');
    }

    public function destroy(Project $project, Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        $this->scheduler->schedule($project);

        return back()->with('success', 'Libur dihapus. Jadwal dihitung ulang.');
    }
}
