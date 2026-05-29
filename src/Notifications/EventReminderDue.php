<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Catalog\Models\Event;

final class EventReminderDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Event $event) {}

    /** @return array<int, string> */
    public function via(): array
    {
        /** @var array<int, string> $channels */
        $channels = (array) config('events.notifications.channels', ['mail', 'database']);

        return $channels;
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: '.$this->event->getTranslation('title', app()->getLocale()))
            ->view('events::notifications.event-reminder-due', ['event' => $this->event]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'event_id' => $this->event->id,
            'starts_at' => $this->event->starts_at->toIso8601String(),
        ];
    }
}
