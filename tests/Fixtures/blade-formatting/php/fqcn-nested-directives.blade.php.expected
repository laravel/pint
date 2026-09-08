<div>
    @foreach ($groups as $group)
        @if ($group->visible)
            @foreach ($group->items as $item)
                <?php $row = \App\Models\Category::find($item->id); ?>
                <span>{{ $row->name }}</span>
            @endforeach
        @endif
    @endforeach
</div>
