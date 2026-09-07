<div>
    <?php $a = \App\Models\Category::all(); ?>

    <p>{{ count($a) }}</p>

    <?php $b = \App\Models\Product::query()->count(); ?>

    <p>{{ $b }}</p>

    <?php
    $c = \App\Support\Money::from(100);
    ?>

    <p>{{ $c }}</p>
</div>
