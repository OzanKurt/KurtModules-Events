<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\SponsorCompTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @property int $id
 * @property int $sponsor_id
 * @property int $ticket_id
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SponsorCompTicket extends Model
{
    /** @use HasFactory<SponsorCompTicketFactory> */
    use HasFactory;

    protected $table = 'events_sponsor_comp_tickets';

    /** @var list<string> */
    protected $fillable = ['sponsor_id', 'ticket_id', 'issued_at'];

    /** @var array<string, string> */
    protected $casts = [
        'issued_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Sponsor, $this>
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Sponsor::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    protected static function newFactory(): SponsorCompTicketFactory
    {
        return SponsorCompTicketFactory::new();
    }
}
