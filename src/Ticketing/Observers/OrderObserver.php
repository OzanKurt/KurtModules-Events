<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Observers;

use Kurt\Modules\Events\Flow\Support\PayoutAccruer;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderPaid;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Support\TransferEngine;

final class OrderObserver
{
    public function __construct(
        private readonly TransferEngine $transferEngine,
        private readonly PayoutAccruer $payoutAccruer,
    ) {}

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        if ($order->status !== OrderStatus::Paid) {
            return;
        }

        $metadata = (array) $order->metadata;
        $ticketId = isset($metadata['transfer_for_ticket_id']) ? (int) $metadata['transfer_for_ticket_id'] : null;
        if ($ticketId !== null) {
            $ticket = Ticket::find($ticketId);
            if ($ticket !== null && $order->buyer !== null) {
                $this->transferEngine->completeTransfer($ticket, $order->buyer);
            }
        }

        if ((bool) config('events.payouts.auto_accrue_on_order_paid', true)) {
            $this->payoutAccruer->accrueFor($order);
        }

        OrderPaid::dispatch($order);
    }
}
