@extends($layout ?? 'installer-web::layouts.app')

@section('title', 'Requirements')

@section('content')
    <h2 class="mb-4 text-xl font-semibold text-slate-900">Server requirements</h2>

    <div class="space-y-6">
        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">PHP</h3>
            <p @class(['text-sm', 'text-emerald-600' => $report['php']['passes'], 'text-red-600' => ! $report['php']['passes']])>
                {{ $report['php']['current'] }} (requires {{ $report['php']['required'] }}+)
            </p>
        </div>

        <div>
            <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Extensions</h3>
            <ul class="grid grid-cols-2 gap-1 text-sm">
                @foreach ($report['extensions'] as $name => $ok)
                    <li @class(['text-emerald-600' => $ok, 'text-red-600' => ! $ok])>
                        {{ $ok ? '✓' : '✗' }} {{ $name }}
                    </li>
                @endforeach
            </ul>
        </div>

        @if (! empty($report['permissions']))
            <div>
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">Writable paths</h3>
                <ul class="space-y-1 text-sm">
                    @foreach ($report['permissions'] as $path => $ok)
                        <li @class(['text-emerald-600' => $ok, 'text-red-600' => ! $ok])>
                            {{ $ok ? '✓' : '✗' }} {{ $path }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('installer-web.store', ['step' => 'requirements']) }}">
            @csrf
            <button type="submit" @disabled(! $report['passes'])
                @class([
                    'rounded-lg px-5 py-2.5 font-medium text-white',
                    'bg-indigo-600 hover:bg-indigo-500' => $report['passes'],
                    'cursor-not-allowed bg-slate-300' => ! $report['passes'],
                ])>
                Continue
            </button>
        </form>
    </div>
@endsection
