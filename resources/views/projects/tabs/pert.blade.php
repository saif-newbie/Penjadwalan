<div class="rounded-lg bg-white shadow p-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold">PERT Chart</h2>
        <button id="toggle-critical-pert" type="button"
                class="px-3 py-1.5 rounded-md bg-red-600 text-white text-xs font-medium hover:bg-red-500">
            Hide Critical Path
        </button>
    </div>

    @if ($critical['paths'])
        <div class="mb-4 rounded-md bg-slate-50 border border-slate-200 p-3 text-xs">
            <div class="font-semibold text-slate-700 mb-1">Semua Jalur ({{ count($critical['paths']) }})</div>
            <table class="w-full text-left">
                <thead class="text-slate-500">
                    <tr><th class="py-0.5">Jalur</th><th class="py-0.5">Total Durasi</th><th class="py-0.5"></th></tr>
                </thead>
                <tbody>
                    @foreach ($critical['paths'] as $path)
                        <tr class="{{ $path['duration'] === $critical['critical_path']['duration'] ? 'text-red-700 font-semibold' : 'text-slate-600' }}">
                            <td class="py-0.5">{{ implode(' → ', $path['names']) }}</td>
                            <td class="py-0.5">{{ $path['duration'] }} hari</td>
                            <td class="py-0.5">{{ $path['duration'] === $critical['critical_path']['duration'] ? '★ kritis' : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div id="pert-chart" class="overflow-x-auto" data-url="{{ route('data.pert', $project) }}">
        <p class="text-slate-400 text-sm p-4">Memuat PERT chart…</p>
    </div>
</div>
