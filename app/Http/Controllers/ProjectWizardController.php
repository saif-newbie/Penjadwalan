<?php

namespace App\Http\Controllers;

use App\Http\Requests\WizardStep1Request;
use App\Http\Requests\WizardStep2Request;
use App\Http\Requests\WizardStep3Request;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\Role;
use App\Services\SchedulerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class ProjectWizardController extends Controller
{
    private const SESSION_KEY = 'project_wizard';

    public function __construct(private SchedulerService $scheduler) {}

    public function create(): View
    {
        return view('wizard.step1', ['data' => Session::get(self::SESSION_KEY, [])]);
    }

    public function storeStep1(WizardStep1Request $request): RedirectResponse
    {
        Session::put(self::SESSION_KEY, array_merge(Session::get(self::SESSION_KEY, []), $request->validated()));

        return redirect()->route('wizard.step2');
    }

    public function step2(): View
    {
        return view('wizard.step2', ['data' => Session::get(self::SESSION_KEY, [])]);
    }

    public function storeStep2(WizardStep2Request $request): RedirectResponse
    {
        Session::put(self::SESSION_KEY, array_merge(Session::get(self::SESSION_KEY, []), $request->validated()));

        return redirect()->route('wizard.step3');
    }

    public function step3(): View
    {
        return view('wizard.step3', ['data' => Session::get(self::SESSION_KEY, [])]);
    }

    public function store(WizardStep3Request $request): RedirectResponse
    {
        $data = array_merge(Session::get(self::SESSION_KEY, []), $request->validated());

        if (empty($data['name'])) {
            return redirect()->route('wizard.step1')->withErrors(['name' => 'Nama proyek wajib diisi.']);
        }

        $project = Project::create([
            'name' => $data['name'],
            'organization' => $data['organization'] ?? null,
            'website' => $data['website'] ?? null,
            'description' => $data['description'] ?? null,
            'start_date' => now()->toDateString(),
            'weekend_mode' => $data['weekend_mode'],
            'holiday_mode' => $data['holiday_mode'],
            'date_format' => 'mm/dd/yyyy',
            'font_size' => 14,
        ]);

        foreach (Role::TEMPLATES[$data['role_template']] as $i => $name) {
            Role::create(['project_id' => $project->id, 'name' => $name, 'sort_order' => $i]);
        }

        if ($project->holiday_mode === Project::HOLIDAY_CUSTOM) {
            foreach (($data['holidays'] ?? []) as $holiday) {
                Holiday::create([
                    'project_id' => $project->id,
                    'type' => $holiday['type'],
                    'date' => $holiday['date'] ?? null,
                    'month' => $holiday['month'] ?? null,
                    'day' => $holiday['day'] ?? null,
                    'label' => $holiday['label'],
                ]);
            }
        }

        Session::forget(self::SESSION_KEY);
        $this->scheduler->schedule($project);

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil dibuat.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyek dihapus.');
    }
}
