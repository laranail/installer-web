@php($key = 'data.'.$field->name)
@php($customType = app(\Simtabi\Laranail\Installer\Web\Support\WebUiRegistry::class)->fieldType($field->type))
<div>
    <label for="{{ $key }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $field->label }}</label>

    @if ($customType)
        @include($customType, ['field' => $field, 'key' => $key])
    @else
    @switch($field->type)
        @case('select')
            <select id="{{ $key }}" wire:model.live="{{ $key }}" class="w-full rounded-lg border-slate-300">
                @foreach ($field->options as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @break

        @case('checkbox')
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model="{{ $key }}" class="rounded border-slate-300">
                <span class="text-sm text-slate-600">{{ $field->label }}</span>
            </label>
            @break

        @case('password')
            <input type="password" id="{{ $key }}" wire:model="{{ $key }}" class="w-full rounded-lg border-slate-300">
            @break

        @case('textarea')
            <textarea id="{{ $key }}" wire:model="{{ $key }}" class="w-full rounded-lg border-slate-300"></textarea>
            @break

        @default
            @php($inputType = in_array($field->type, ['email', 'number', 'url', 'tel', 'date'], true) ? $field->type : 'text')
            <input type="{{ $inputType }}" id="{{ $key }}" wire:model="{{ $key }}" class="w-full rounded-lg border-slate-300">
    @endswitch
    @endif

    @error($key)
        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
    @enderror
</div>
