@component('mail::message')
# Refund processed

Your refund has been processed.

- Amount: {{ $refund->amount_minor / 100 }} {{ $refund->currency }}

@component('mail::button', ['url' => ''])
View Order
@endcomponent

Thanks!
@endcomponent
