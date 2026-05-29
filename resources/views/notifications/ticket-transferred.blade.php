@component('mail::message')
# Ticket transferred

A ticket for **{{ $ticket->event->getTranslation('title', app()->getLocale()) }}** has been transferred.

- Ticket type: {{ $ticket->ticketType->getTranslation('name', app()->getLocale()) }}
- New holder: {{ $ticket->holder_name }}

@component('mail::button', ['url' => ''])
View Ticket
@endcomponent

Thanks!
@endcomponent
