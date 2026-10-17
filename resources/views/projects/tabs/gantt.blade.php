<div class="rounded-lg bg-white shadow p-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">Gantt Chart</h2>
        <button id="toggle-critical" type="button"
                class="px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-medium hover:bg-red-500">
            Hide Critical Path
        </button>
    </div>
    <div id="gantt-chart" class="overflow-x-auto" data-url="{{ route('data.gantt', $project) }}">
        <p class="text-slate-400 text-sm p-4">Memuat Gantt chart…</p>
    </div>
</div>
