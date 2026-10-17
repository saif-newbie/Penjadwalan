@php($steps = ['Informasi', 'Peran', 'Kalender'])

<div class="mb-6">
    <ol class="flex items-center gap-2 text-sm">
        @foreach ($steps as $i => $label)
            @php($n = $i + 1)
            <li class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold
                    {{ $n === $current ? 'bg-sky-600 text-white' : ($n < $current ? 'bg-emerald-500 text-white' : 'bg-slate-300 text-slate-600') }}">
                    {{ $n < $current ? '✓' : $n }}
                </span>
                <span class="{{ $n === $current ? 'font-semibold text-slate-900' : 'text-slate-500' }}">{{ $label }}</span>
            </li>
            @if (! $loop->last)
                <li class="flex-1 border-t border-slate-300"></li>
            @endif
        @endforeach
    </ol>
</div>
