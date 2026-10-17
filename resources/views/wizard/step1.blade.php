@extends('layout')

@section('title', 'Langkah 1 — Informasi Proyek')

@section('content')
<div class="max-w-2xl mx-auto">
    @include('wizard.steps', ['current' => 1])

    <div class="rounded-lg bg-white shadow p-6">
        <h1 class="text-xl font-bold mb-1">Informasi Proyek</h1>
        <p class="text-sm text-slate-500 mb-5">Isi detail dasar proyek Anda.</p>

        <form action="{{ route('wizard.step1.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">Nama Proyek <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $data['name'] ?? '') }}" required
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Organisasi</label>
                <input type="text" name="organization" value="{{ old('organization', $data['organization'] ?? '') }}"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Website</label>
                <input type="url" name="website" value="{{ old('website', $data['website'] ?? '') }}" placeholder="https://example.com"
                       class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Deskripsi</label>
                <textarea name="description" rows="4"
                          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">{{ old('description', $data['description'] ?? '') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button class="px-5 py-2 rounded-md bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">Selanjutnya →</button>
            </div>
        </form>
    </div>
</div>
@endsection
