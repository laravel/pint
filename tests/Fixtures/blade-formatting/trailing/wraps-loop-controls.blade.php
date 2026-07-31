@foreach ($users as $user)
    @continue(
        $user->isBanned() ||
            $user->trashed()
    )

    @break(
        $loop->iteration > $maximumNumberOfUsers
    )

    <p>{{ $user->name }}</p>
@endforeach
