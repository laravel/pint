<div>
    @foreach ($items as $item)
        <?php
        $row = \App\Models\Category::childrenOf($item->id);
        ?>
        <span>{{ $row->name }}</span>
    @endforeach

    @if ($admin)
        <?php $count = \App\Models\Category::count(); ?>
        <span>{{ $count }}</span>
    @endif
</div>
