<div>
    @fragment('user-list')
        <ul>
            @foreach ($users as $user)
                <li wire:key="user-{{ $user->id }}">{{ $user->name }}</li>
            @endforeach
        </ul>
    @endfragment

    @fragment('footer')
        <footer>{{ $count }} users</footer>
    @endfragment
</div>
