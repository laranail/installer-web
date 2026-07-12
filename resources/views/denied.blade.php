@extends(config('installer-web.layout') ?: 'installer-web::layouts.app')

@section('title', 'Access denied')

@section('content')
    <h2 class="text-lg font-semibold text-slate-900">Access denied</h2>
    <p class="mt-1 text-sm text-slate-500">You don't have access to the installer.</p>
@endsection
