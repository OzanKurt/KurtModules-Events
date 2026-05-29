<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Attendance\Models\Application;

final class ApplicationApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Application $application) {}

    /** @return array<int, string> */
    public function via(): array
    {
        /** @var array<int, string> $channels */
        $channels = (array) config('events.notifications.channels', ['mail', 'database']);

        return $channels;
    }

    public function toMail(): MailMessage
    {
        $event = $this->application->event()->firstOrFail();

        return (new MailMessage)
            ->subject('Your application was approved: '.$event->getTranslation('title', app()->getLocale()))
            ->view('events::notifications.application-approved', ['application' => $this->application]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'application_id' => $this->application->id,
            'event_id' => $this->application->event_id,
        ];
    }
}
