@component('mail::message')
# Order Shipped

Your order has shipped.

@component('mail::button', ['url' => $url])
View Order
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
