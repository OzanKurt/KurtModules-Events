<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Support;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Events\EventClonedFrom;
use Kurt\Modules\Events\Catalog\Models\Event;

final class EventCloner
{
    /** @param array<string, mixed> $overrides */
    public function clone(Event $source, array $overrides = []): Event
    {
        return DB::transaction(function () use ($source, $overrides) {
            $excluded = [
                'slug',
                'tickets_sold_count',
                'attendees_count',
                'applications_pending_count',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
            ];

            $new = $source->replicate($excluded)->fill($overrides);
            $new->status = EventStatus::Draft;
            $new->save();

            foreach ($source->sessions as $session) {
                $clonedSession = $session->replicate(['attendees_count']);
                $clonedSession->event_id = $new->id;
                $clonedSession->save();
            }

            foreach ($source->ticketTypes as $type) {
                $clonedType = $type->replicate(['sold_count']);
                $clonedType->event_id = $new->id;
                $clonedType->save();

                foreach ($type->priceTiers as $tier) {
                    $clonedTier = $tier->replicate(['sold_count']);
                    $clonedTier->ticket_type_id = $clonedType->id;
                    $clonedTier->save();
                }
            }

            EventClonedFrom::dispatch($source, $new);

            return $new->refresh();
        });
    }
}
