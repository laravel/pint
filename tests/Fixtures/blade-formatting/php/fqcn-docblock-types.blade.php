<div>
    <?php
    /** @var \App\Models\Category $category */
    $category = $rows[0];

    $format = function (\App\Support\Money $money): \App\Support\Money {
        return $money;
    };
    ?>

    <span>{{ $format($category->price) }}</span>
</div>
