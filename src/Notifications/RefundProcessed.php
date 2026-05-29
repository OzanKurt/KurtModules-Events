<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Flow\Models\Refund;

final class RefundProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Refund $refund) {}

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
            ->subject('Your refund has been processed')
            ->view('events::notifications.refund-processed', ['refund' => $this->refund]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'refund_id' => $this->refund->id,
            'order_id' => $this->refund->order_id,
            'amount_minor' => $this->refund->amount_minor,
            'currency' => $this->refund->currency,
        ];
    }
}
