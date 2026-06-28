@php
    $ui = app(\Simtabi\Laranail\Installer\Web\Support\WebUiRegistry::class);
    $branding = (array) config('installer-web.branding', []);
    $accent = $branding['theme'] ?? '#4f46e5';
    $heading = $branding['title'] ?? (config('app.name', 'Application') . ' installer');
    $stepMeta = (array) config('installer.steps', []);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Install') · {{ config('app.name', 'Laravel') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>:root { --installer-accent: {{ $accent }}; }</style>
    {!! $ui->renderSection('head') !!}
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <header class="mb-8 text-center">
            @if (! empty($branding['logo']))
                <img src="{{ $branding['logo'] }}" alt="{{ $heading }}" class="mx-auto mb-3 h-12">
            @endif
            <h1 class="text-2xl font-semibold text-slate-900">{{ $heading }}</h1>
        </header>

        {!! $ui->renderSection('before-content') !!}
        @stack('before-content')

        @isset($steps)
            <nav class="mb-8 flex flex-wrap justify-center gap-2 text-sm" aria-label="Steps">
                @foreach ($steps as $step)
                    @php($meta = $stepMeta[$step->key()] ?? [])
                    <span
                        @class([
                            'inline-flex items-center gap-1 rounded-full px-3 py-1',
                            'text-white' => $step->key() === ($current ?? null),
                            'bg-white text-slate-500 ring-1 ring-slate-200' => $step->key() !== ($current ?? null),
                        ])
                        @if ($step->key() === ($current ?? null)) style="background-color: var(--installer-accent)" @endif
                        @isset($meta['description']) title="{{ $meta['description'] }}" @endisset
                    >
                        @isset($meta['icon'])<span aria-hidden="true">{{ $meta['icon'] }}</span>@endisset
                        {{ $step->label() }}
                    </span>
                @endforeach
            </nav>
        @endisset

        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-200">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            @yield('content')
        </main>

        {!! $ui->renderSection('after-content') !!}
        @stack('after-content')

        <footer class="mt-8 text-center text-xs text-slate-400">
            {!! $ui->renderSection('footer') !!}
            @stack('footer')
        </footer>
    </div>
</body>
</html>
