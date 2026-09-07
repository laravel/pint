<div>
    <x-category-list :items="\App\Models\Category::all()" />

    <x-badge :mode="\App\Enums\CalculationMode::Default" class="grid grid-cols-{{ \App\Enums\CalculationMode::count() }}" />
</div>
