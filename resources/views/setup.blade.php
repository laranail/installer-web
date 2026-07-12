@extends(config('installer-web.layout') ?: 'installer-web::layouts.app')

@section('title', 'Secure the installer')

@section('content')
    <h2 class="text-lg font-semibold text-slate-900">Secure the installer</h2>
    <p class="mt-1 text-sm text-slate-500">
        Optionally set a gate password and lock the installer to your current IP. These
        are written to your <code>.env</code>; no shell access required.
    </p>

    <form method="POST" action="{{ route('installer-web.setup.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Gate password (optional)</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="new-password"
                class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400"
            >
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="lock_ip" value="1" class="rounded border-slate-300">
            Lock the installer to my current IP ({{ request()->ip() }})
        </label>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-lg px-4 py-2 font-medium text-white"
                style="background-color: var(--installer-accent)"
            >
                Save &amp; continue
            </button>
            <a href="{{ route('installer-web.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Skip</a>
        </div>
    </form>
@endsection
