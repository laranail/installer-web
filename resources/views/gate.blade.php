@extends(config('installer-web.layout') ?: 'installer-web::layouts.app')

@section('title', 'Verify access')

@section('content')
    <h2 class="text-lg font-semibold text-slate-900">Verify access</h2>
    <p class="mt-1 text-sm text-slate-500">Enter the installer token to continue.</p>

    <form method="POST" action="{{ route('installer-web.gate.store') }}" class="mt-6 space-y-4">
        @csrf
        <div>
            <label for="token" class="block text-sm font-medium text-slate-700">Token</label>
            <input
                id="token"
                name="token"
                type="password"
                autocomplete="off"
                autofocus
                class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-400 focus:ring-slate-400"
            >
            @error('token')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="rounded-lg px-4 py-2 font-medium text-white"
            style="background-color: var(--installer-accent)"
        >
            Continue
        </button>
    </form>
@endsection
