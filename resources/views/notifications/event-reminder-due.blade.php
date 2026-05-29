@component('mail::message')
# Event reminder

**{{ $event->getTranslation('title', app()->getLocale()) }}** is coming up.

- Starts at: {{ $event->starts_at }}
- Timezone: {{ $event->timezone }}

@component('mail::button', ['url' => ''])
View Event
@endcomponent

See you there!
@endcomponent
