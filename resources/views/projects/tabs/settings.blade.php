<div class="space-y-4">
    {{-- Pengaturan tampilan --}}
    <div class="rounded-lg bg-white shadow p-4">
        <h2 class="font-semibold mb-3">Pengaturan Tampilan</h2>
        <form action="{{ route('projects.settings', $project) }}" method="POST" class="grid grid-cols-2 md:grid-cols-4 gap-3 items-end text-sm">
            @csrf @method('PUT')
            <label class="block">Format tanggal
                <select name="date_format" class="w-full mt-1 rounded border-slate-300">
                    @foreach (\App\Models\Project::DATE_FORMATS as $f)
                        <option value="{{ $f }}" @selected($project->date_format === $f)>{{ $f }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">Ukuran font (px)
                <input type="number" name="font_size" min="10" max="24" value="{{ old('font_size', $project->font_size) }}" required
                       class="w-full mt-1 rounded border-slate-300">
            </label>
            <div class="col-span-2">
                <button type="submit" class="px-4 py-2 rounded bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">Simpan Pengaturan</button>
            </div>
        </form>

        <div class="mt-4 text-xs text-slate-500 grid grid-cols-2 md:grid-cols-4 gap-2 border-t pt-3">
            <div>Weekend: <strong>{{ $project->weekend_mode === 'off' ? 'Sabtu–Minggu libur' : 'berjalan terus' }}</strong></div>
            <div>Libur: <strong>{{ $project->holiday_mode === 'custom' ? 'kustom ('.$project->holidays->count().')' : 'tanpa libur' }}</strong></div>
            <div>Mulai proyek: <strong>{{ $project->start_date?->format('Y-m-d') ?? '—' }}</strong></div>
            <div>Jumlah tugas: <strong>{{ $project->tasks->count() }}</strong></div>
        </div>
    </div>

    {{-- Libur proyek --}}
    <div class="rounded-lg bg-white shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Kalender Libur Proyek</h2>
            <span class="text-xs text-slate-500">Libur memengaruhi perhitungan hari kerja &amp; jadwal</span>
        </div>

        @if ($project->holidays->isEmpty())
            <p class="text-sm text-slate-400 mb-3">Tidak ada hari libur kustom.</p>
        @else
            <ul class="text-sm flex flex-wrap gap-2 mb-3">
                @foreach ($project->holidays as $holiday)
                    <li class="inline-flex items-center gap-1 px-2 py-1 rounded bg-amber-100 text-amber-800">
                        @if ($holiday->type === 'recurring')
                            <span class="text-[10px] uppercase bg-amber-500 text-white rounded px-1">tahunan</span>
                            {{ sprintf('%02d-%02d', $holiday->month, $holiday->day) }}
                        @else
                            {{ \App\Support\DateFormat::format($holiday->date, $project->date_format) }}
                        @endif
                        @if ($holiday->label) <span class="text-amber-600">· {{ $holiday->label }}</span> @endif
                        <form action="{{ route('holidays.destroy', [$project, $holiday]) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-amber-700 hover:text-red-600" title="Hapus">✕</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form action="{{ route('holidays.store', $project) }}" method="POST" class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end text-sm border-t pt-3">
            @csrf
            <label class="block">Tipe
                <select name="type" class="w-full mt-1 rounded border-slate-300">
                    <option value="one_off">Sekali (tanggal)</option>
                    <option value="recurring">Berulang (bulan–tanggal)</option>
                </select>
            </label>
            <label class="block">Tanggal
                <input type="date" name="date" value="{{ old('date') }}" class="w-full mt-1 rounded border-slate-300">
            </label>
            <label class="block">Bulan
                <input type="number" name="month" min="1" max="12" value="{{ old('month') }}" placeholder="8" class="w-full mt-1 rounded border-slate-300">
            </label>
            <label class="block">Tanggal (1-31)
                <input type="number" name="day" min="1" max="31" value="{{ old('day') }}" placeholder="17" class="w-full mt-1 rounded border-slate-300">
            </label>
            <label class="block">Label
                <input name="label" value="{{ old('label') }}" placeholder="Hari Kemerdekaan" class="w-full mt-1 rounded border-slate-300">
            </label>
            <div class="col-span-2 md:col-span-5">
                <button type="submit" class="px-4 py-2 rounded bg-amber-500 text-white text-sm font-medium hover:bg-amber-400">+ Tambah Libur</button>
            </div>
        </form>
    </div>

    {{-- Impor / ekspor --}}
    <div class="rounded-lg bg-white shadow p-4">
        <h2 class="font-semibold mb-3">Ekspor / Impor Proyek</h2>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <a href="{{ route('projects.export', $project) }}"
               class="px-4 py-2 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">⬇ Unduh JSON</a>

            <form action="{{ route('projects.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept="application/json" required class="text-sm">
                <button type="submit" class="px-4 py-2 rounded bg-slate-800 text-white text-sm hover:bg-slate-700">⬆ Impor Proyek Baru</button>
            </form>
        </div>
    </div>

    {{-- Hapus proyek --}}
    <div class="rounded-lg bg-white shadow p-4 border border-red-200">
        <h2 class="font-semibold text-red-700 mb-2">Zona Berbahaya</h2>
        <form action="{{ route('projects.destroy', $project) }}" method="POST"
              onsubmit="return confirm('Hapus proyek ini beserta seluruh tugas, dependensi, dan alokasinya?')" class="flex items-center gap-3">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 rounded bg-red-600 text-white text-sm font-medium hover:bg-red-500">Hapus Proyek</button>
            <span class="text-xs text-slate-500">Tindakan ini tidak dapat dibatalkan.</span>
        </form>
    </div>
</div>
