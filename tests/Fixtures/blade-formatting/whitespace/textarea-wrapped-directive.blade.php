@props(['autocomplete' => false, 'id' => null])
@php
    $componentId = $id ?? Str::uuid();
@endphp
<textarea
    {!! $attributes->merge(['class' => 'w-full dark:text-white text-black dark:caret-white caret-black focus:border-pink-500 dark:border-slate-800 border-slate-300 dark:bg-slate-900/50 bg-slate-50/50 backdrop-blur-sm dark:focus:ring-slate-900 focus:ring-slate-100 rounded-lg shadow-sm sm:text-sm']) !!}
    @if ($autocomplete === true)
        x-data="usesDynamicAutocomplete('{{ $componentId }}')"
        x-bind="autocompleteInputBindings"
    @endif
>
    {{ $slot }}
</textarea>

@if ($autocomplete === true)
<livewire:autocomplete :componentId="$componentId"></livewire:autocomplete>
@endif
