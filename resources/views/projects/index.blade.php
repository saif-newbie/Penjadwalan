@extends('layout')

@section('title', 'Daftar Proyek')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Daftar Proyek</h1>
    <a href="{{ route('wizard.step1') }}"
       class="px-4 py-2 rounded-md bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">
        + Buat Proyek Baru
    </a>
</div>

@if ($projects->isEmpty())
    <div class="rounded-lg border border-dashed border-slate-400 bg-white p-10 text-center text-slate-500">
        Belum ada proyek. Mulai dengan membuat proyek baru melalui wizard 3 langkah.
    </div>
@else
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach ($projects as $project)
            <div class="rounded-lg bg-white shadow p-4 flex flex-col gap-2">
                <a href="{{ route('projects.show', $project) }}" class="font-semibold text-lg text-slate-900 hover:text-sky-600">
                    {{ $project->name }}
                </a>
                <div class="text-xs text-slate-500">
                    {{ $project->organization ?: '—' }} · {{ $project->tasks_count }} tugas
                </div>
                <p class="text-sm text-slate-600 line-clamp-2">{{ \Illuminate\Support\Str::limit($project->description, 100) }}</p>
                <div class="mt-auto flex items-center gap-2 pt-2 text-sm">
                    <a href="{{ route('projects.show', $project) }}"
                       class="px-3 py-1.5 rounded bg-slate-800 text-white hover:bg-slate-700 text-xs">Buka</a>
                    <a href="{{ route('projects.export', $project) }}"
                       class="px-3 py-1.5 rounded border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs">Export JSON</a>
                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="ml-auto"
                          onsubmit="return confirm('Hapus proyek {{ $project->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button class="px-3 py-1.5 rounded border border-red-300 text-red-600 hover:bg-red-50 text-xs">Hapus</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="mt-10 rounded-lg bg-white shadow p-4 max-w-xl">
    <h2 class="font-semibold mb-2">Import Proyek</h2>
    <p class="text-sm text-slate-500 mb-3">Unggah file JSON hasil export untuk memuat ulang sebuah proyek.</p>
    <form action="{{ route('projects.import') }}" method="POST" enctype="multipart/form-data" class="flex gap-2">
        @csrf
        <input type="file" name="file" accept="application/json" required
               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded file:border-0 file:bg-slate-200 file:px-3 file:py-1.5 file:text-slate-700">
        <button class="px-4 py-2 rounded bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-500 whitespace-nowrap">Import</button>
    </form>
</div>
@endsection
