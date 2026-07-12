@extends($layout ?? 'installer-web::layouts.app')

@section('title', ucfirst(str_replace(['-', '_'], ' ', $current)))

@section('content')
    @livewire($component ?? 'installer-wizard-step', ['step' => $current])
@endsection
