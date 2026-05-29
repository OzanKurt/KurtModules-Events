<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Observers;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

final class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        if ($ticket->status === TicketStatus::Issued) {
            DB::table('events_events')
                ->where('id', $ticket->event_id)
                ->update(['tickets_sold_count' => DB::raw('tickets_sold_count + 1')]);
        }
    }

    public function updated(Ticket $ticket): void
    {
        if (! $ticket->wasChanged('status')) {
            return;
        }

        $previous = $ticket->getOriginal('status');
        $cancelledNow = in_array($ticket->status, [TicketStatus::Cancelled, TicketStatus::Refunded], true);
        $wasIssued = $previous instanceof TicketStatus
            ? $previous === TicketStatus::Issued
            : $previous === TicketStatus::Issued->value;

        if ($cancelledNow && $wasIssued) {
            DB::table('events_events')
                ->where('id', $ticket->event_id)
                ->update(['tickets_sold_count' => DB::raw('CASE WHEN tickets_sold_count > 0 THEN tickets_sold_count - 1 ELSE 0 END')]);
        }
    }
}
