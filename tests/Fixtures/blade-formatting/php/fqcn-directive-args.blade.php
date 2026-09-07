<div>
    @foreach (\App\Models\Category::all() as $category)
        <li>{{ $category->name }}</li>
    @endforeach

    @if ($mode === \App\Enums\CalculationMode::Default)
        <span>default</span>
    @endif

    @class(['active' => $state === \App\Enums\State::Active])
</div>
