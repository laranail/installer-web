@extends($layout ?? 'laranail/installer-web::layouts.app')

@section('title', ucfirst(str_replace(['-', '_'], ' ', $current)))

@section('content')
    @livewire($component ?? 'laranail-installer-web.wizard-step', ['step' => $current])
@endsection
