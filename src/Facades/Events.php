<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Facades;

use Illuminate\Support\Facades\Facade;
use Kurt\Modules\Events\Support\Events as EventsService;

/**
 * @method static \Kurt\Modules\Events\Catalog\Models\Event createEvent(array<string, mixed> $data, \Illuminate\Database\Eloquent\Model $organizer)
 * @method static void approveForPublication(\Kurt\Modules\Events\Catalog\Models\Event $event, \Illuminate\Database\Eloquent\Model $platformAdmin)
 * @method static void publish(\Kurt\Modules\Events\Catalog\Models\Event $event)
 * @method static void cancel(\Kurt\Modules\Events\Catalog\Models\Event $event, \Illuminate\Database\Eloquent\Model $canceller, string $reason)
 * @method static void complete(\Kurt\Modules\Events\Catalog\Models\Event $event)
 * @method static \Kurt\Modules\Events\Ticketing\Models\Order reserve(\Kurt\Modules\Events\Ticketing\Models\TicketType $type, \Illuminate\Database\Eloquent\Model $buyer, int $quantity, array<int, array{name: string, email: string, user_id?: int|string|null, metadata?: array<string, mixed>}> $holderAssignments, ?string $discountCode = null, ?int $unitPriceMinorOverride = null)
 * @method static void pay(\Kurt\Modules\Events\Ticketing\Models\Order $order, string $processor, string $reference)
 * @method static \Kurt\Modules\Events\Ticketing\Models\Ticket transferTicket(\Kurt\Modules\Events\Ticketing\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $newHolder)
 * @method static \Kurt\Modules\Events\Ticketing\Models\Ticket checkIn(\Kurt\Modules\Events\Ticketing\Models\Ticket $ticket, \Illuminate\Database\Eloquent\Model $scanner)
 * @method static \Kurt\Modules\Events\Ticketing\Models\Ticket checkInByToken(string $qrToken, \Illuminate\Database\Eloquent\Model $scanner)
 *
 * @see EventsService
 */
final class Events extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EventsService::class;
    }
}
