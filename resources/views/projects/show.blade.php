@extends('layout')

@section('title', $project->name)

@section('content')
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ $project->name }}</h1>
        <p class="text-sm text-slate-500">
            {{ $project->organization ?: '—' }}
            @if ($project->website)
                · <a href="{{ $project->website }}" class="text-sky-600 hover:underline" target="_blank">{{ $project->website }}</a>
            @endif
        </p>
        @if ($project->description)
            <p class="text-sm text-slate-600 mt-1 max-w-2xl">{{ $project->description }}</p>
        @endif
    </div>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('projects.export', $project) }}"
           class="px-3 py-1.5 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Export JSON</a>
        <a href="{{ route('projects.index') }}"
           class="px-3 py-1.5 rounded border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">← Semua Proyek</a>
    </div>
</div>

@if ($schedule['project_start'])
    <div class="mb-5 rounded-lg bg-white shadow px-4 py-2 text-sm text-slate-600 flex flex-wrap gap-x-6 gap-y-1">
        <span>🗓 Periode: <strong>{{ \App\Support\DateFormat::format($schedule['project_start'], $project->date_format) }}</strong>
            – <strong>{{ \App\Support\DateFormat::format($schedule['project_end'], $project->date_format) }}</strong></span>
        <span>📉 Jalur kritis: <strong>{{ $critical['critical_path'] ? implode(' → ', $critical['critical_path']['names']).' ('.$critical['critical_path']['duration'].' hari)' : '—' }}</strong></span>
        <span>📅 Weekend: <strong>{{ $project->weekend_mode === 'off' ? 'libur' : 'tetap berjalan' }}</strong></span>
    </div>
@endif

{{-- Tab navigation --}}
<div x-data="{ tab: 'gantt' }" class="space-y-4">
    <div class="flex flex-wrap gap-1 border-b border-slate-300">
        @foreach (['gantt' => 'Gantt Chart', 'resource' => 'Resource Chart', 'pert' => 'PERT Chart', 'tasks' => 'Tugas & Dependensi', 'team' => 'Tim & Alokasi', 'settings' => 'Pengaturan'] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-white border-slate-300 text-sky-700' : 'bg-slate-200 border-transparent text-slate-600 hover:bg-slate-100'"
                    class="px-4 py-2 rounded-t-lg border-b-2 text-sm font-medium">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div x-show="tab === 'gantt'">
        @include('projects.tabs.gantt', ['project' => $project, 'critical' => $critical])
    </div>
    <div x-show="tab === 'resource'">
        @include('projects.tabs.resource', ['project' => $project])
    </div>
    <div x-show="tab === 'pert'">
        @include('projects.tabs.pert', ['project' => $project, 'critical' => $critical])
    </div>
    <div x-show="tab === 'tasks'">
        @include('projects.tabs.tasks', ['project' => $project])
    </div>
    <div x-show="tab === 'team'">
        @include('projects.tabs.team', ['project' => $project])
    </div>
    <div x-show="tab === 'settings'">
        @include('projects.tabs.settings', ['project' => $project])
    </div>
</div>

@endsection
