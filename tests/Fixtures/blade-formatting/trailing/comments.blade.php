<div>
    @if (
        $user->isAdmin()
        && $user->isActive() // admins, owners,
    )
        <span>Admin</span>
    @endif

    @if (
        $user->isAdmin()
        // nothing else matters,
    )
        <span>Admin</span>
    @endif
</div>
