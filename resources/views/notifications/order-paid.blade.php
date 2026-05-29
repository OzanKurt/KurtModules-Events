@component('mail::message')
# Payment received

Thanks! Your order has been paid.

- Total: {{ $order->total_minor / 100 }} {{ $order->currency }}

@component('mail::button', ['url' => ''])
View Order
@endcomponent

Enjoy the event.
@endcomponent
