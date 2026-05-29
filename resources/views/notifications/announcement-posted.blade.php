@component('mail::message')
# {{ $announcement->subject }}

{!! $announcement->body !!}

@component('mail::button', ['url' => ''])
View Event
@endcomponent

Thanks!
@endcomponent
