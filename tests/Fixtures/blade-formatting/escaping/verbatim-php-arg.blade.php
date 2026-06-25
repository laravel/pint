<div>
    @verbatim
        @foreach ($users    as    $user)
            <li>{{ user.name }}</li>
        @endforeach

        <p>{{
            some.very.long.value
        }}</p>
    @endverbatim
    <p>{{ $afterwards }}</p>
</div>
