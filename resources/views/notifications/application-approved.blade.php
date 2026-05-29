@component('mail::message')
# Application approved

Your application for **{{ $application->event->getTranslation('title', app()->getLocale()) }}** has been approved.

@component('mail::button', ['url' => ''])
Continue
@endcomponent

Welcome aboard!
@endcomponent
