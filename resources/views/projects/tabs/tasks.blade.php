@php
    $sched = $schedule['tasks']->keyBy('id');
    $taskOptions = $project->tasks->sortBy('position')->values();
    $fmt = $project->date_format;
    $parents = $project->tasks->whereNull('parent_id')->sortBy('position')->values();
@endphp

<div class="space-y-4">
    {{-- Daftar tugas --}}
    <div class="rounded-lg bg-white shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Daftar Tugas</h2>
            <span class="text-xs text-slate-500">{{ $project->tasks->count() }} tugas</span>
        </div>

        @if ($project->tasks->isEmpty())
            <p class="text-sm text-slate-400">Belum ada tugas. Tambahkan tugas pertama di bawah.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 border-b">
                        <tr>
                            <th class="text-left py-2 pr-2">Nama</th>
                            <th class="text-left py-2 pr-2">Prioritas</th>
                            <th class="text-left py-2 pr-2">Mulai</th>
                            <th class="text-left py-2 pr-2">Selesai</th>
                            <th class="text-right py-2 pr-2">Durasi</th>
                            <th class="text-right py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($parents as $root)
                            @php $rows = collect([$root])->concat($root->children); @endphp
                            @foreach ($rows as $task)
                                @php $s = $sched->get($task->id); $isChild = $task->parent_id !== null; @endphp
                                <tr class="border-b last:border-0 {{ $isChild ? 'bg-slate-50' : '' }}">
                                    <td class="py-1.5 pr-2">
                                        <span class="inline-block w-3 h-3 rounded-sm mr-2 align-middle" style="background: {{ $task->color }}"></span>
                                        {{ $isChild ? '↳ ' : '' }}{{ $task->name }}
                                        @if ($task->isSummary()) <span class="text-[10px] uppercase text-slate-400">(induk)</span> @endif
                                    </td>
                                    <td class="py-1.5 pr-2">
                                        <span class="text-xs px-1.5 py-0.5 rounded {{ $task->priority === 'high' ? 'bg-red-100 text-red-700' : ($task->priority === 'low' ? 'bg-slate-200 text-slate-600' : 'bg-sky-100 text-sky-700') }}">{{ $task->priority }}</span>
                                    </td>
                                    <td class="py-1.5 pr-2">{{ $s ? \App\Support\DateFormat::format($s['start_date'], $fmt) : '—' }}</td>
                                    <td class="py-1.5 pr-2">{{ $s ? \App\Support\DateFormat::format($s['end_date'], $fmt) : '—' }}</td>
                                    <td class="py-1.5 pr-2 text-right">{{ $task->duration }} hari</td>
                                    <td class="py-1.5 text-right whitespace-nowrap">
                                        <button type="button" x-data x-on:click="document.getElementById('edit-task-{{ $task->id }}').classList.toggle('hidden')"
                                                class="text-sky-600 hover:underline text-xs">Ubah</button>
                                        <form action="{{ route('tasks.destroy', [$project, $task]) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Hapus tugas ini beserta dependensinya?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline text-xs ml-2">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                                <tr id="edit-task-{{ $task->id }}" class="hidden">
                                    <td colspan="6" class="bg-slate-50 p-3">
                                        <form action="{{ route('tasks.update', [$project, $task]) }}" method="POST" class="grid grid-cols-2 md:grid-cols-6 gap-2 items-end text-xs">
                                            @csrf @method('PUT')
                                            <label class="block col-span-2">Nama
                                                <input name="name" value="{{ old('name', $task->name) }}" required
                                                       class="w-full mt-0.5 rounded border-slate-300">
                                            </label>
                                            <label class="block">Mulai
                                                <input type="date" name="start_date" value="{{ $task->start_date?->format('Y-m-d') }}" required
                                                       class="w-full mt-0.5 rounded border-slate-300">
                                            </label>
                                            <label class="block">Durasi (hari kerja)
                                                <input type="number" name="duration" min="1" max="1000" value="{{ $task->duration }}" required
                                                       class="w-full mt-0.5 rounded border-slate-300">
                                            </label>
                                            <label class="block">Prioritas
                                                <select name="priority" class="w-full mt-0.5 rounded border-slate-300">
                                                    @foreach (\App\Models\Task::PRIORITIES as $p)
                                                        <option value="{{ $p }}" @selected($task->priority === $p)>{{ $p }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" name="color" value="{{ $task->color }}" class="h-8 w-10 rounded border-slate-300">
                                                <button type="submit" class="px-3 py-1 rounded bg-sky-600 text-white">Simpan</button>
                                            </div>
                                            <label class="block col-span-2 md:col-span-6">Induk tugas
                                                <select name="parent_id" class="w-full mt-0.5 rounded border-slate-300">
                                                    <option value="">— (tugas induk) —</option>
                                                    @foreach ($taskOptions as $opt)
                                                        @if ($opt->id !== $task->id && ! $task->children->pluck('id')->contains($opt->id))
                                                            <option value="{{ $opt->id }}" @selected($task->parent_id === $opt->id)>{{ $opt->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </label>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Tambah tugas --}}
    <div class="rounded-lg bg-white shadow p-4">
        <h2 class="font-semibold mb-3">Tambah Tugas</h2>
        <form action="{{ route('tasks.store', $project) }}" method="POST" class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end text-sm">
            @csrf
            <label class="block col-span-2">Nama tugas
                <input name="name" value="{{ old('name') }}" required class="w-full mt-1 rounded border-slate-300">
            </label>
            <label class="block">Tanggal mulai
                <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" required
                       class="w-full mt-1 rounded border-slate-300">
            </label>
            <label class="block">Durasi (hari kerja)
                <input type="number" name="duration" min="1" max="1000" value="{{ old('duration', 5) }}" required
                       class="w-full mt-1 rounded border-slate-300">
            </label>
            <label class="block">Prioritas
                <select name="priority" class="w-full mt-1 rounded border-slate-300">
                    @foreach (\App\Models\Task::PRIORITIES as $p)
                        <option value="{{ $p }}" @selected(old('priority', 'normal') === $p)>{{ $p }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">Warna
                <input type="color" name="color" value="{{ old('color', '#3b82f6') }}" class="w-full mt-1 h-9 rounded border-slate-300">
            </label>
            <label class="block col-span-2 md:col-span-6">Induk tugas (opsional)
                <select name="parent_id" class="w-full mt-1 rounded border-slate-300">
                    <option value="">— tugas induk —</option>
                    @foreach ($taskOptions as $opt)
                        <option value="{{ $opt->id }}" @selected(old('parent_id') == $opt->id)>{{ $opt->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="col-span-2 md:col-span-6">
                <button type="submit" class="px-4 py-2 rounded bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">+ Tambah Tugas</button>
            </div>
        </form>
    </div>

    {{-- Dependensi --}}
    <div class="rounded-lg bg-white shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Dependensi Antar Tugas</h2>
            <span class="text-xs text-slate-500">FS = Finish→Start, SS = Start→Start, FF = Finish→Finish, SF = Start→Finish; delay dalam hari kalender</span>
        </div>

        @if ($project->dependencies->isEmpty())
            <p class="text-sm text-slate-400">Belum ada dependensi.</p>
        @else
            <ul class="text-sm divide-y mb-4">
                @foreach ($project->dependencies as $dep)
                    <li class="py-2 flex flex-wrap items-center gap-2">
                        <span class="font-medium">{{ $dep->predecessor?->name }}</span>
                        <span class="text-xs px-1.5 py-0.5 rounded bg-slate-800 text-white">{{ $dep->type }}</span>
                        <span class="text-xs text-slate-500">delay {{ $dep->delay }} hari</span>
                        <span class="text-slate-400">→</span>
                        <span class="font-medium">{{ $dep->successor?->name }}</span>

                        <form action="{{ route('dependencies.update', [$project, $dep]) }}" method="POST" class="ml-auto flex items-center gap-1 text-xs">
                            @csrf @method('PUT')
                            <select name="type" class="rounded border-slate-300">
                                @foreach (\App\Models\Dependency::TYPES as $t)
                                    <option value="{{ $t }}" @selected($dep->type === $t)>{{ $t }}</option>
                                @endforeach
                            </select>
                            <input type="number" name="delay" min="0" max="365" value="{{ $dep->delay }}" class="w-16 rounded border-slate-300" title="Delay (hari)">
                            <button type="submit" class="px-2 py-1 rounded bg-slate-200 hover:bg-slate-300">Simpan</button>
                        </form>
                        <form action="{{ route('dependencies.destroy', [$project, $dep]) }}" method="POST" class="text-xs">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline ml-1">Hapus</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form action="{{ route('dependencies.store', $project) }}" method="POST" class="grid grid-cols-2 md:grid-cols-6 gap-3 items-end text-sm border-t pt-3">
            @csrf
            <label class="block col-span-2">Predecessor
                <select name="predecessor_id" required class="w-full mt-1 rounded border-slate-300">
                    @foreach ($taskOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block col-span-2">Successor
                <select name="successor_id" required class="w-full mt-1 rounded border-slate-300">
                    @foreach ($taskOptions as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">Tipe
                <select name="type" class="w-full mt-1 rounded border-slate-300">
                    @foreach (\App\Models\Dependency::TYPES as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">Delay (hari)
                <input type="number" name="delay" min="0" max="365" value="{{ old('delay', 0) }}" class="w-full mt-1 rounded border-slate-300">
            </label>
            <div class="col-span-2 md:col-span-6">
                <button type="submit" class="px-4 py-2 rounded bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">+ Tambah Dependensi</button>
            </div>
        </form>
    </div>
</div>
