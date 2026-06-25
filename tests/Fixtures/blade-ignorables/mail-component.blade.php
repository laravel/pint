<x-mail::message>
# Order Shipped

Your order has shipped! Here are the details:

- Item one
- Item two

<x-mail::button :url="$url">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
