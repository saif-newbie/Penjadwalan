@php
    $taskOptions = $project->tasks->sortBy('position')->values();
    $fmt = $project->date_format;
@endphp

<div class="space-y-4">
    {{-- Anggota tim --}}
    <div class="rounded-lg bg-white shadow p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold">Anggota Tim</h2>
            <span class="text-xs text-slate-500">{{ $project->resources->count() }} anggota</span>
        </div>

        @if ($project->resources->isEmpty())
            <p class="text-sm text-slate-400 mb-3">Belum ada anggota tim.</p>
        @else
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm">
                    <thead class="text-xs text-slate-500 border-b">
                        <tr>
                            <th class="text-left py-2 pr-2">Nama</th>
                            <th class="text-left py-2 pr-2">Role</th>
                            <th class="text-left py-2 pr-2">Kontak</th>
                            <th class="text-right py-2 pr-2">Gaji / hari</th>
                            <th class="text-right py-2 pr-2">Tugas dipegang</th>
                            <th class="text-right py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($project->resources as $resource)
                            <tr class="border-b last:border-0">
                                <td class="py-1.5 pr-2 font-medium">{{ $resource->name }}</td>
                                <td class="py-1.5 pr-2">{{ $resource->role ?: '—' }}</td>
                                <td class="py-1.5 pr-2 text-xs text-slate-500">{{ $resource->email ?: $resource->phone ?: '—' }}</td>
                                <td class="py-1.5 pr-2 text-right">{{ $resource->salary ? number_format((float) $resource->salary, 0, ',', '.') : '—' }}</td>
                                <td class="py-1.5 pr-2 text-right">{{ $resource->allocations->count() }}</td>
                                <td class="py-1.5 text-right whitespace-nowrap">
                                    <button type="button" x-data x-on:click="document.getElementById('edit-resource-{{ $resource->id }}').classList.toggle('hidden')"
                                            class="text-sky-600 hover:underline text-xs">Ubah</button>
                                    <form action="{{ route('resources.destroy', [$project, $resource]) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus anggota ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-xs ml-2">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <tr id="edit-resource-{{ $resource->id }}" class="hidden">
                                <td colspan="6" class="bg-slate-50 p-3">
                                    <form action="{{ route('resources.update', [$project, $resource]) }}" method="POST"
                                          class="grid grid-cols-2 md:grid-cols-6 gap-2 items-end text-xs">
                                        @csrf @method('PUT')
                                        <label class="block">Nama <input name="name" value="{{ $resource->name }}" required class="w-full mt-0.5 rounded border-slate-300"></label>
                                        <label class="block">Role <input name="role" value="{{ $resource->role }}" class="w-full mt-0.5 rounded border-slate-300"></label>
                                        <label class="block">Email <input type="email" name="email" value="{{ $resource->email }}" class="w-full mt-0.5 rounded border-slate-300"></label>
                                        <label class="block">Telepon <input name="phone" value="{{ $resource->phone }}" class="w-full mt-0.5 rounded border-slate-300"></label>
                                        <label class="block">Gaji/hari <input type="number" step="0.01" name="salary" value="{{ $resource->salary }}" class="w-full mt-0.5 rounded border-slate-300"></label>
                                        <button type="submit" class="px-3 py-1 rounded bg-sky-600 text-white">Simpan</button>
                                    </form>

                                    {{-- Hari libur pribadi --}}
                                    <div class="mt-2 text-xs">
                                        <span class="font-semibold text-slate-600">Hari libur pribadi:</span>
                                        @forelse ($resource->holidays as $h)
                                            <span class="inline-flex items-center gap-1 ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">
                                                {{ \App\Support\DateFormat::format($h->date, $fmt) }}{{ $h->label ? ' · '.$h->label : '' }}
                                                <form action="{{ route('resources.holidays.destroy', [$project, $resource, $h]) }}" method="POST" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-amber-700 hover:text-red-600" title="Hapus">✕</button>
                                                </form>
                                            </span>
                                        @empty
                                            <span class="text-slate-400">tidak ada</span>
                                        @endforelse
                                        <form action="{{ route('resources.holidays.store', [$project, $resource]) }}" method="POST" class="inline-flex items-center gap-1 ml-2">
                                            @csrf
                                            <input type="date" name="date" required class="rounded border-slate-300">
                                            <input name="label" placeholder="Label" class="rounded border-slate-300 w-24">
                                            <button type="submit" class="px-2 py-0.5 rounded bg-amber-500 text-white">+ Libur</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <form action="{{ route('resources.store', $project) }}" method="POST" class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end text-sm border-t pt-3">
            @csrf
            <label class="block">Nama <input name="name" value="{{ old('name') }}" required class="w-full mt-1 rounded border-slate-300"></label>
            <label class="block">Role <input name="role" value="{{ old('role') }}" placeholder="Developer" class="w-full mt-1 rounded border-slate-300"></label>
            <label class="block">Email <input type="email" name="email" value="{{ old('email') }}" class="w-full mt-1 rounded border-slate-300"></label>
            <label class="block">Telepon <input name="phone" value="{{ old('phone') }}" class="w-full mt-1 rounded border-slate-300"></label>
            <label class="block">Gaji/hari <input type="number" step="0.01" name="salary" value="{{ old('salary') }}" class="w-full mt-1 rounded border-slate-300"></label>
            <div class="col-span-2 md:col-span-5">
                <button type="submit" class="px-4 py-2 rounded bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">+ Tambah Anggota</button>
            </div>
        </form>
    </div>

    {{-- Alokasi tugas --}}
    <div class="rounded-lg bg-white shadow p-4">
        <h2 class="font-semibold mb-3">Alokasi Tugas</h2>

        @php $allocations = $project->resources->flatMap(fn ($r) => $r->allocations->map(fn ($a) => $a)->values()); @endphp

        @if ($allocations->isEmpty())
            <p class="text-sm text-slate-400 mb-3">Belum ada alokasi. Tentukan siapa mengerjakan tugas apa.</p>
        @else
            <ul class="text-sm divide-y mb-4">
                @foreach ($project->resources as $resource)
                    @foreach ($resource->allocations as $allocation)
                        <li class="py-1.5 flex items-center gap-2">
                            <span class="font-medium">{{ $resource->name }}</span>
                            <span class="text-slate-400">mengerjakan</span>
                            <span>{{ $project->tasks->firstWhere('id', $allocation->task_id)?->name ?? '—' }}</span>
                            <form action="{{ route('allocations.destroy', [$project, $allocation]) }}" method="POST" class="ml-auto">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs">Hapus</button>
                            </form>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        @endif

        @if ($project->resources->isNotEmpty() && $project->tasks->isNotEmpty())
            <form action="{{ route('allocations.store', $project) }}" method="POST" class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end text-sm border-t pt-3">
                @csrf
                <label class="block">Tugas
                    <select name="task_id" required class="w-full mt-1 rounded border-slate-300">
                        @foreach ($taskOptions as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">Anggota tim
                    <select name="resource_id" required class="w-full mt-1 rounded border-slate-300">
                        @foreach ($project->resources as $res)
                            <option value="{{ $res->id }}">{{ $res->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div>
                    <button type="submit" class="px-4 py-2 rounded bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">+ Alokasikan</button>
                </div>
            </form>
        @endif
    </div>
</div>
