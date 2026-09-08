@php
    $rows = \App\Models\Category::all();
    $mode = \App\Enums\CalculationMode::Default;
@endphp

@php($total = \App\Models\Product::count())

<div>{{ count($rows) }} {{ $mode->value }} {{ $total }}</div>
