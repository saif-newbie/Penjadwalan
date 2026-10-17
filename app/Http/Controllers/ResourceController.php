<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceHolidayRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Models\Project;
use App\Models\Resource;
use App\Models\ResourceHoliday;
use Illuminate\Http\RedirectResponse;

class ResourceController extends Controller
{
    public function store(Project $project, StoreResourceRequest $request): RedirectResponse
    {
        $project->resources()->create($request->validated());

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    public function update(Project $project, Resource $resource, StoreResourceRequest $request): RedirectResponse
    {
        $resource->update($request->validated());

        return back()->with('success', 'Anggota tim diperbarui.');
    }

    public function destroy(Project $project, Resource $resource): RedirectResponse
    {
        $resource->delete();

        return back()->with('success', 'Anggota tim dihapus.');
    }

    public function storeHoliday(Project $project, Resource $resource, StoreResourceHolidayRequest $request): RedirectResponse
    {
        $exists = $resource->holidays()->whereDate('date', $request->input('date'))->exists();

        if (! $exists) {
            $resource->holidays()->create($request->validated());
        }

        return back()->with('success', 'Hari libur pribadi ditambahkan.');
    }

    public function destroyHoliday(Project $project, Resource $resource, ResourceHoliday $holiday): RedirectResponse
    {
        if ($holiday->resource_id === $resource->id) {
            $holiday->delete();
        }

        return back()->with('success', 'Hari libur pribadi dihapus.');
    }
}
