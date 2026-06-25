@foreach ($items as $item)
    @include('partials.header')

    @include('partials.body')
@endforeach

@if ($condition)
    <span>first</span>

    <span>second</span>
@endif
