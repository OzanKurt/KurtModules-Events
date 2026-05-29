@component('mail::message')
# Application update

Your application for **{{ $application->event->getTranslation('title', app()->getLocale()) }}** was not approved.

@if (!empty($reason))
Reason: {{ $reason }}
@endif

@component('mail::button', ['url' => ''])
Open Event
@endcomponent

Thanks for applying.
@endcomponent
