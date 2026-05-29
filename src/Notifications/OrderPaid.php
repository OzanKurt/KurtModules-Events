<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Events\Ticketing\Models\Order;

final class OrderPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

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
            ->subject('Payment received for your order')
            ->view('events::notifications.order-paid', ['order' => $this->order]);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return [
            'order_id' => $this->order->id,
            'event_id' => $this->order->event_id,
            'total_minor' => $this->order->total_minor,
            'currency' => $this->order->currency,
        ];
    }
}
