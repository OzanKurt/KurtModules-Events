@component('mail::message')
# Your ticket is ready

You're confirmed for **{{ $ticket->event->getTranslation('title', app()->getLocale()) }}**.

- Ticket type: {{ $ticket->ticketType->getTranslation('name', app()->getLocale()) }}
- Holder: {{ $ticket->holder_name }}

@component('mail::button', ['url' => ''])
View Ticket
@endcomponent

Thanks!
@endcomponent
