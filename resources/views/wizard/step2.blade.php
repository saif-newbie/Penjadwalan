@extends('layout')

@section('title', 'Langkah 2 — Template Peran')

@section('content')
<div class="max-w-2xl mx-auto">
    @include('wizard.steps', ['current' => 2])

    <div class="rounded-lg bg-white shadow p-6">
        <h1 class="text-xl font-bold mb-1">Template Peran</h1>
        <p class="text-sm text-slate-500 mb-5">Pilih daftar peran yang tersedia untuk anggota tim proyek ini.</p>

        <form action="{{ route('wizard.step2.store') }}" method="POST" class="space-y-4">
            @csrf

            @php($templates = [
                'default' => ['label' => 'Default', 'roles' => \App\Models\Role::TEMPLATES['default'], 'desc' => 'Peran umum lintas jenis proyek.'],
                'software_development' => ['label' => 'Software Development', 'roles' => \App\Models\Role::TEMPLATES['software_development'], 'desc' => 'Peran khas tim pengembangan perangkat lunak.'],
            ])

            <div class="space-y-3">
                @foreach ($templates as $key => $tpl)
                    <label class="flex gap-3 rounded-lg border {{ old('role_template', $data['role_template'] ?? 'default') === $key ? 'border-sky-500 bg-sky-50' : 'border-slate-300' }} p-4 cursor-pointer hover:border-sky-400">
                        <input type="radio" name="role_template" value="{{ $key }}"
                               {{ old('role_template', $data['role_template'] ?? 'default') === $key ? 'checked' : '' }}
                               class="mt-1 accent-sky-600">
                        <span>
                            <span class="block font-semibold">{{ $tpl['label'] }}</span>
                            <span class="block text-sm text-slate-500">{{ $tpl['desc'] }}</span>
                            <span class="block text-xs text-slate-400 mt-1">{{ implode(' · ', $tpl['roles']) }}</span>
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-between pt-2">
                <a href="{{ route('wizard.step1') }}" class="px-5 py-2 rounded-md border border-slate-300 text-sm text-slate-600 hover:bg-slate-50">← Kembali</a>
                <button class="px-5 py-2 rounded-md bg-sky-600 text-white text-sm font-medium hover:bg-sky-500">Selanjutnya →</button>
            </div>
        </form>
    </div>
</div>
@endsection
