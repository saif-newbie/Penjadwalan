<?php

use App\Http\Controllers\AllocationController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\DependencyController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectWizardController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');

Route::get('/projects/create', [ProjectWizardController::class, 'create'])->name('wizard.step1');
Route::post('/projects/create', [ProjectWizardController::class, 'storeStep1'])->name('wizard.step1.store');
Route::get('/projects/create/step-2', [ProjectWizardController::class, 'step2'])->name('wizard.step2');
Route::post('/projects/create/step-2', [ProjectWizardController::class, 'storeStep2'])->name('wizard.step2.store');
Route::get('/projects/create/step-3', [ProjectWizardController::class, 'step3'])->name('wizard.step3');
Route::post('/projects', [ProjectWizardController::class, 'store'])->name('wizard.store');

Route::post('/projects/import', [ProjectController::class, 'import'])->name('projects.import');

Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::put('/projects/{project}/settings', [ProjectController::class, 'updateSettings'])->name('projects.settings');
Route::get('/projects/{project}/export', [ProjectController::class, 'export'])->name('projects.export');
Route::delete('/projects/{project}', [ProjectWizardController::class, 'destroy'])->name('projects.destroy');

Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::put('/projects/{project}/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/projects/{project}/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

Route::post('/projects/{project}/dependencies', [DependencyController::class, 'store'])->name('dependencies.store');
Route::put('/projects/{project}/dependencies/{dependency}', [DependencyController::class, 'update'])->name('dependencies.update');
Route::delete('/projects/{project}/dependencies/{dependency}', [DependencyController::class, 'destroy'])->name('dependencies.destroy');

Route::post('/projects/{project}/resources', [ResourceController::class, 'store'])->name('resources.store');
Route::put('/projects/{project}/resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
Route::delete('/projects/{project}/resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');
Route::post('/projects/{project}/resources/{resource}/holidays', [ResourceController::class, 'storeHoliday'])->name('resources.holidays.store');
Route::delete('/projects/{project}/resources/{resource}/holidays/{holiday}', [ResourceController::class, 'destroyHoliday'])->name('resources.holidays.destroy');

Route::post('/projects/{project}/allocations', [AllocationController::class, 'store'])->name('allocations.store');
Route::delete('/projects/{project}/allocations/{allocation}', [AllocationController::class, 'destroy'])->name('allocations.destroy');

Route::post('/projects/{project}/holidays', [HolidayController::class, 'store'])->name('holidays.store');
Route::delete('/projects/{project}/holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

Route::get('/projects/{project}/data/gantt', [ChartController::class, 'gantt'])->name('data.gantt');
Route::get('/projects/{project}/data/resources', [ChartController::class, 'resources'])->name('data.resources');
Route::get('/projects/{project}/data/pert', [ChartController::class, 'pert'])->name('data.pert');
Route::get('/projects/{project}/data/critical-path', [ChartController::class, 'criticalPath'])->name('data.critical');
