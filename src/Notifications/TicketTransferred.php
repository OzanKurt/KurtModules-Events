<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

final class TicketTransferred extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?int $oldHolderId,
        public readonly int $newHolderId,
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
        $event = $this->ticket->event()->firstOrFail();

        return (new MailMessage)
            ->subject('Ticket transferred for '.$event->getTranslation('title', app()->getLocale()))
            ->view('events::notifications.ticket-transferred', [
                'ticket' => $this->ticket,
                'oldHolderId' => $this->oldHolderId,
                'newHolderId' => $this->newHolderId,
            ]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->ticket->event_id,
            'old_holder_id' => $this->oldHolderId,
            'new_holder_id' => $this->newHolderId,
        ];
    }
}
