{{-- Reusable field renderer — use in custom step views: <x-laranail-installer-web::field :field="$field" /> --}}
@props(['field'])

@include('laranail-installer-web::partials.field', ['field' => $field])
