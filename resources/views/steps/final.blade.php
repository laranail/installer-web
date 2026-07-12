@extends($layout ?? 'installer-web::layouts.app')

@section('title', 'Finished')

@section('content')
    <div class="text-center">
        <h2 class="mb-2 text-xl font-semibold text-slate-900">You're almost done</h2>
        <p class="mb-6 text-slate-600">Click below to finalize the installation.</p>

        <form method="POST" action="{{ route('installer-web.store', ['step' => 'final']) }}">
            @csrf
            <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 font-medium text-white hover:bg-emerald-500">
                Complete installation
            </button>
        </form>
    </div>
@endsection
