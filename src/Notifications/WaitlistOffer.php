<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;

final class WaitlistOffer extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly WaitlistEntry $entry) {}

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
            ->subject('A ticket is available for you')
            ->view('events::notifications.waitlist-offer', ['entry' => $this->entry]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'waitlist_entry_id' => $this->entry->id,
            'ticket_type_id' => $this->entry->ticket_type_id,
            'claim_expires_at' => $this->entry->claim_expires_at?->toIso8601String(),
        ];
    }
}
