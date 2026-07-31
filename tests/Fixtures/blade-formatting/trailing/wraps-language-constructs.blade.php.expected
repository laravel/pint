@use(
    'App\Models\User'
)

@php(
    $total = $order->subtotal + $order->tax
)

@isset(
    $user->profile->avatar
)
    <img src="{{ $user->profile->avatar }}" alt="" />
@endisset

@empty(
    $notifications
)
    <p>Nothing here.</p>
@endempty

@unset(
    $temporaryValue
)
