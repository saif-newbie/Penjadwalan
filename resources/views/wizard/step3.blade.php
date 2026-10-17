@extends('layout')

@section('title', 'Langkah 3 — Kalender')

@section('content')
<div class="max-w-3xl mx-auto">
    @include('wizard.steps', ['current' => 3])

    <form action="{{ route('wizard.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="rounded-lg bg-white shadow p-6">
            <h1 class="text-xl font-bold mb-1">Akhir Pekan</h1>
            <p class="text-sm text-slate-500 mb-4">Tentukan apakah ada kegiatan di hari Sabtu/Minggu.</p>

            <div class="space-y-2">
                @foreach (['off' => 'Tidak ada kegiatan (Sabtu & Minggu libur)', 'continuous' => 'Tetap berjalan (7 hari)'] as $value => $label)
                    <label class="flex items-center gap-3 rounded-lg border {{ old('weekend_mode', $data['weekend_mode'] ?? 'off') === $value ? 'border-sky-500 bg-sky-50' : 'border-slate-300' }} p-3 cursor-pointer">
                        <input type="radio" name="weekend_mode" value="{{ $value }}"
                               {{ old('weekend_mode', $data['weekend_mode'] ?? 'off') === $value ? 'checked' : '' }}
                               class="accent-sky-600">
                        <span class="text-sm font-medium">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg bg-white shadow p-6">
            <h1 class="text-xl font-bold mb-1">Kalender Libur</h1>
            <p class="text-sm text-slate-500 mb-4">Pilih tanpa libur, atau daftarkan libur satu kali dan berulang setiap tahun.</p>

            <div class="space-y-2 mb-5">
                @foreach (['none' => 'None (tanpa libur)', 'custom' => 'Custom (libur satu kali & berulang)'] as $value => $label)
                    <label class="flex items-center gap-3 rounded-lg border {{ old('holiday_mode', $data['holiday_mode'] ?? 'none') === $value ? 'border-sky-500 bg-sky-50' : 'border-slate-300' }} p-3 cursor-pointer">
                        <input type="radio" name="holiday_mode" id="hm-{{ $value }}" value="{{ $value }}"
                               {{ old('holiday_mode', $data['holiday_mode'] ?? 'none') === $value ? 'checked' : '' }}
                               class="accent-sky-600" x-data x-on:change.document="null">
                        <span class="text-sm font-medium">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div id="holiday-list" class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-700">Daftar Libur</h2>

                @php($existing = old('holidays', $data['holidays'] ?? []))
                @if (is_array($existing) && count($existing) === 0)
                    @php($existing = [['type' => 'one_off', 'date' => '', 'month' => '', 'day' => '', 'label' => '']])
                @endif

                @foreach ($existing as $i => $holiday)
                    <div class="holiday-row grid grid-cols-12 gap-2 items-end rounded-md border border-slate-200 p-3">
                        <div class="col-span-3">
                            <label class="block text-xs text-slate-500 mb-1">Jenis</label>
                            <select name="holidays[{{ $i }}][type]" class="holiday-type w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                <option value="one_off" {{ ($holiday['type'] ?? 'one_off') === 'one_off' ? 'selected' : '' }}>One-off</option>
                                <option value="recurring" {{ ($holiday['type'] ?? '') === 'recurring' ? 'selected' : '' }}>Recurring</option>
                            </select>
                        </div>
                        <div class="col-span-3 holiday-date">
                            <label class="block text-xs text-slate-500 mb-1">Tanggal</label>
                            <input type="date" name="holidays[{{ $i }}][date]" value="{{ $holiday['date'] ?? '' }}"
                                   class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                        </div>
                        <div class="col-span-3 holiday-recurring hidden">
                            <label class="block text-xs text-slate-500 mb-1">Bulan / Hari</label>
                            <div class="flex gap-1">
                                <select name="holidays[{{ $i }}][month]" class="w-1/2 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                                    <option value="">Bulan</option>
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ (int) ($holiday['month'] ?? 0) === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                    @endfor
                                </select>
                                <input type="number" name="holidays[{{ $i }}][day]" min="1" max="31" value="{{ $holiday['day'] ?? '' }}"
                                       placeholder="Hari" class="w-1/2 rounded-md border border-slate-300 px-2 py-1.5 text-sm">
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs text-slate-500 mb-1">Label</label>
                            <input type="text" name="holidays[{{ $i }}][label]" value="{{ $holiday['label'] ?? '' }}"
                                   class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm" placeholder="Nama libur">
                        </div>
                        <div class="col-span-1">
                            <button type="button" class="remove-holiday w-full rounded-md border border-red-300 text-red-600 px-2 py-1.5 text-xs hover:bg-red-50">✕</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" id="add-holiday"
                    class="mt-3 px-3 py-1.5 rounded-md border border-sky-500 text-sky-600 text-sm hover:bg-sky-50">
                + Tambah Libur
            </button>
        </div>

        <div class="flex justify-between">
            <a href="{{ route('wizard.step2') }}" class="px-5 py-2 rounded-md border border-slate-300 text-sm text-slate-600 hover:bg-slate-50">← Kembali</a>
            <button class="px-6 py-2 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-500">Buat Proyek ✓</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    (function () {
        const list = document.getElementById('holiday-list');
        let index = list.querySelectorAll('.holiday-row').length;

        function syncRow(row) {
            const type = row.querySelector('.holiday-type').value;
            row.querySelector('.holiday-date').classList.toggle('hidden', type !== 'one_off');
            row.querySelector('.holiday-recurring').classList.toggle('hidden', type !== 'recurring');
        }

        function bind(row) {
            syncRow(row);
            row.querySelector('.holiday-type').addEventListener('change', () => syncRow(row));
            row.querySelector('.remove-holiday').addEventListener('click', () => row.remove());
        }

        list.querySelectorAll('.holiday-row').forEach(bind);

        document.getElementById('add-holiday').addEventListener('click', function () {
            const template = list.querySelector('.holiday-row');
            const clone = template.cloneNode(true);
            clone.innerHTML = clone.innerHTML.replace(/holidays\[\d+\]/g, 'holidays[' + (index++) + ']');

            // Reset nilai input hasil clone.
            clone.querySelectorAll('input, select').forEach((el) => {
                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else {
                    el.value = '';
                }
            });

            bind(clone);
            list.appendChild(clone);
        });
    })();
</script>
@endpush
@endsection
