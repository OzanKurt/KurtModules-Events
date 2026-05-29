@component('mail::message')
# A ticket is available

A spot opened up. Claim it before {{ $entry->claim_expires_at }}.

@component('mail::button', ['url' => ''])
Claim Ticket
@endcomponent

@endcomponent
