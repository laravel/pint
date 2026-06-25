<div>
    <div id="user-{{ $user->id }}" class="card {{ $active ? 'is-active' : '' }} mt-4">
        <img src="{{ asset("images/{$user->avatar}") }}" alt="{{ $user->name }}'s avatar" />
        <a href="/users/{{ $user->id }}/posts?page={{ $page }}" data-id="{{ $user->id }}">Posts</a>
        <span style="color: {{ $color }}; width: {{ $width }}px">Bar</span>
        <input type="text" name="items[{{ $index }}][name]" value="{{ old("items.{$index}.name", $item->name) }}" />
    </div>
</div>
