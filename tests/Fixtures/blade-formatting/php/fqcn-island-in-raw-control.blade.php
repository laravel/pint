<div>
    <?php foreach ($items as $item) { ?>
        <?php $row = \App\Models\Category::find($item->id); ?>
        <li>{{ $row->name }}</li>
    <?php } ?>

    <?php if (\App\Enums\CalculationMode::count() > 1): ?>
        <span>many</span>
    <?php endif; ?>
</div>
