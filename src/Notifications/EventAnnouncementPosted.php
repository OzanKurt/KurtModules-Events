<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\AnnouncementRecipient;

final class EventAnnouncementPosted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Announcement $announcement,
        public readonly AnnouncementRecipient $recipient,
    ) {}

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
            ->subject($this->announcement->subject)
            ->view('events::notifications.announcement-posted', [
                'announcement' => $this->announcement,
                'recipient' => $this->recipient,
            ]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'announcement_id' => $this->announcement->id,
            'event_id' => $this->announcement->event_id,
            'subject' => $this->announcement->subject,
        ];
    }
}
