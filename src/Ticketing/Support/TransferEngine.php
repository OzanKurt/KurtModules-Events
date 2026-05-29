<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\TicketTransferred;
use Kurt\Modules\Events\Ticketing\Events\TicketTransferRequested;
use Kurt\Modules\Events\Ticketing\Exceptions\TransferNotAllowed;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

final class TransferEngine
{
    public function attemptTransfer(Ticket $ticket, Model $newHolder): Ticket
    {
        if (! $ticket->ticketType->transferable) {
            throw new TransferNotAllowed('Type not transferable');
        }
        if (! $ticket->transferable()) {
            throw new TransferNotAllowed('Deadline passed');
        }

        $fee = $ticket->ticketType->transfer_fee_minor;
        if ($fee !== null && $fee > 0) {
            $feeOrder = $this->createFeeOrder($ticket, $newHolder, $fee, (string) $ticket->ticketType->transfer_fee_currency);
            $ticket->forceFill(['transfer_fee_order_id' => $feeOrder->id])->save();
            TicketTransferRequested::dispatch($ticket, $newHolder);

            return $ticket;
        }

        return $this->completeTransfer($ticket, $newHolder);
    }

    public function completeTransfer(Ticket $ticket, Model $newHolder): Ticket
    {
        $oldHolderId = $ticket->holder_id;
        $name = (string) ($newHolder->getAttribute('name') ?? $newHolder->getAttribute('email') ?? $newHolder->getKey());
        $email = (string) ($newHolder->getAttribute('email') ?? '');

        $ticket->forceFill([
            'holder_id' => $newHolder->getKey(),
            'holder_name' => $name,
            'holder_email' => $email,
            'transferred_from' => $oldHolderId,
            'transferred_at' => now(),
        ])->save();

        TicketTransferred::dispatch($ticket, $oldHolderId, $newHolder);

        return $ticket;
    }

    private function createFeeOrder(Ticket $ticket, Model $newHolder, int $amountMinor, string $currency): Order
    {
        return DB::transaction(fn () => Order::create([
            'event_id' => $ticket->event_id,
            'user_id' => $newHolder->getKey(),
            'status' => OrderStatus::Pending,
            'subtotal_minor' => $amountMinor,
            'discount_minor' => 0,
            'tax_minor' => 0,
            'total_minor' => $amountMinor,
            'currency' => $currency,
            'metadata' => ['transfer_for_ticket_id' => $ticket->id],
        ]));
    }
}
