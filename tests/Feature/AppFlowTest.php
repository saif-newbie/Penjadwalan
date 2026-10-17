<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_seeded_projects(): void
    {
        $this->seed();

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Pengembangan Sistem SDLC');
        $response->assertSee('Jaring Aktivitas A-J (PERT)');
    }

    public function test_wizard_flow_creates_project_with_roles(): void
    {
        $this->post('/projects/create', [
            'name' => 'Proyek Wizard',
            'organization' => 'PT Uji',
            'website' => 'https://example.test',
            'description' => 'Deskripsi',
        ])->assertRedirect(route('wizard.step2'));

        $this->post('/projects/create/step-2', [
            'role_template' => 'software_development',
        ])->assertRedirect(route('wizard.step3'));

        $this->post('/projects', [
            'weekend_mode' => 'off',
            'holiday_mode' => 'none',
        ])->assertRedirect();

        $project = Project::firstWhere('name', 'Proyek Wizard');
        $this->assertNotNull($project);
        $this->assertGreaterThan(0, $project->roles()->count());
    }

    public function test_project_show_page_renders_all_tabs(): void
    {
        $this->seed();
        $project = Project::firstWhere('name', 'Pengembangan Sistem SDLC');

        $response = $this->get(route('projects.show', $project));
        $response->assertOk();
        $response->assertSee('Gantt Chart');
        $response->assertSee('Resource Chart');
        $response->assertSee('PERT Chart');
        $response->assertSee('Daftar Tugas');
        $response->assertSee('Analisis Kebutuhan');
    }

    public function test_chart_endpoints_return_json(): void
    {
        $this->seed();
        $project = Project::firstWhere('name', 'Jaring Aktivitas A-J (PERT)');

        $gantt = $this->getJson(route('data.gantt', $project))->assertOk();
        $this->assertCount(10, $gantt->json('tasks'));
        $this->assertNotEmpty($gantt->json('dependencies'));
        $this->assertNotNull($gantt->json('project.start'));

        $resources = $this->getJson(route('data.resources', $project))->assertOk();
        $this->assertIsArray($resources->json('resources'));

        $pert = $this->getJson(route('data.pert', $project))->assertOk();
        $this->assertCount(10, $pert->json('nodes'));
        $this->assertCount(10, $pert->json('edges'));

        $critical = $this->getJson(route('data.critical', $project))->assertOk();
        $this->assertSame(33, $critical->json('critical_path.duration'));
    }

    public function test_store_task_and_dependency_recalculates_schedule(): void
    {
        $this->seed();
        $project = Project::firstWhere('name', 'Jaring Aktivitas A-J (PERT)');
        $taskA = $project->tasks()->firstWhere('name', 'Aktivitas A');

        $this->post(route('tasks.store', $project), [
            'name' => 'Aktivitas Baru',
            'start_date' => '2025-09-01',
            'duration' => 3,
            'priority' => 'high',
            'color' => '#123456',
        ])->assertRedirect();

        $new = $project->tasks()->firstWhere('name', 'Aktivitas Baru');
        $this->assertNotNull($new);
        $this->assertNotNull($new->start_date);

        // Dependensi ke tugas yang sama → harus ditolak validasinya.
        $this->post(route('dependencies.store', $project), [
            'predecessor_id' => $taskA->id,
            'successor_id' => $taskA->id,
            'type' => 'FS',
            'delay' => 0,
        ])->assertSessionHasErrors('successor_id');

        // Dependensi valid → dijadwalkan ulang.
        $this->post(route('dependencies.store', $project), [
            'predecessor_id' => $taskA->id,
            'successor_id' => $new->id,
            'type' => 'FS',
            'delay' => 0,
        ])->assertSessionHasNoErrors();

        $new->refresh();
        $this->assertGreaterThan(
            $taskA->end_date->toDateString(),
            $new->start_date->toDateString()
        );
    }

    public function test_export_and_import_round_trip(): void
    {
        $this->seed();
        $project = Project::firstWhere('name', 'Jaring Aktivitas A-J (PERT)');

        $countBefore = Project::count();

        $export = $this->get(route('projects.export', $project))->assertOk();
        $payload = json_decode($export->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertCount(10, $payload['tasks']);

        $tmp = tempnam(sys_get_temp_dir(), 'proj').'.json';
        file_put_contents($tmp, json_encode($payload));

        $this->post(route('projects.import'), [
            'file' => new \Illuminate\Http\UploadedFile($tmp, 'project.json', 'application/json', null, true),
        ])->assertRedirect();

        $this->assertSame($countBefore + 1, Project::count());

        @unlink($tmp);
    }

    public function test_settings_update_and_holiday_store(): void
    {
        $this->seed();
        $project = Project::firstWhere('name', 'Pengembangan Sistem SDLC');

        $this->put(route('projects.settings', $project), [
            'date_format' => 'dd/mm/yyyy',
            'font_size' => 16,
        ])->assertSessionHas('success');

        $project->refresh();
        $this->assertSame('dd/mm/yyyy', $project->date_format);
        $this->assertSame(16, $project->font_size);

        $before = Task::where('project_id', $project->id)->count();
        $this->assertGreaterThan(0, $before);

        $this->post(route('holidays.store', $project), [
            'type' => 'one_off',
            'date' => '2025-07-01',
            'label' => 'Libur Uji',
        ])->assertSessionHas('success');

        $this->assertSame(3, $project->holidays()->count());
    }
}
