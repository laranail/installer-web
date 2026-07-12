{{-- Reusable field renderer — use in custom step views: <x-installer-web::field :field="$field" /> --}}
@props(['field'])

@include('installer-web::partials.field', ['field' => $field])
