<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mini GanttProject')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col" style="font-size: {{ $fontSize ?? 14 }}px;">
    <nav class="bg-slate-900 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('projects.index') }}" class="font-bold text-lg tracking-tight">
                📅 Mini GanttProject
            </a>
            <div class="flex items-center gap-4 text-sm">
                <a href="{{ route('projects.index') }}" class="hover:text-sky-300">Proyek</a>
                <a href="{{ route('wizard.step1') }}" class="px-3 py-1.5 rounded bg-sky-500 hover:bg-sky-400 font-medium">+ Proyek Baru</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-6 w-full flex-1">
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-100 border border-green-300 text-green-800 px-4 py-2 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-100 border border-red-300 text-red-800 px-4 py-2 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-slate-200 border-t border-slate-300 py-3 text-center text-xs text-slate-500">
        Mini GanttProject — Laravel + Tailwind CSS + SVG
    </footer>

    @stack('scripts')
</body>
</html>
