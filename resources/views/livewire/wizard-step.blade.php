<div>
    @error('form')
        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200">{{ $message }}</div>
    @enderror

    <form wire:submit="save" class="space-y-5">
        @foreach ($fields as $field)
            @if ($field->isVisible($data))
                @include('installer-web::partials.field', ['field' => $field])
            @endif
        @endforeach

        <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 font-medium text-white hover:bg-indigo-500">
            Continue
        </button>
    </form>
</div>
