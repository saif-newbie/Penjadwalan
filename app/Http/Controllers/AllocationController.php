<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAllocationRequest;
use App\Models\Allocation;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class AllocationController extends Controller
{
    public function store(Project $project, StoreAllocationRequest $request): RedirectResponse
    {
        $exists = Allocation::where('task_id', $request->input('task_id'))
            ->where('resource_id', $request->input('resource_id'))
            ->exists();

        if (! $exists) {
            Allocation::create($request->only(['task_id', 'resource_id']));
        }

        return back()->with('success', 'Alokasi ditambahkan.');
    }

    public function destroy(Project $project, Allocation $allocation): RedirectResponse
    {
        $allocation->delete();

        return back()->with('success', 'Alokasi dihapus.');
    }
}
