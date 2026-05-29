<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\WaitlistEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

/**
 * @property int $id
 * @property int $ticket_type_id
 * @property int $user_id
 * @property int $quantity
 * @property WaitlistStatus $status
 * @property Carbon|null $offered_at
 * @property Carbon|null $claim_expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class WaitlistEntry extends Model
{
    /** @use HasFactory<WaitlistEntryFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_waitlist_entries';

    /** @var list<string> */
    protected $fillable = [
        'ticket_type_id', 'user_id',
        'quantity', 'status',
        'offered_at', 'claim_expires_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => WaitlistStatus::class,
        'offered_at' => 'datetime',
        'claim_expires_at' => 'datetime',
        'quantity' => 'integer',
    ];

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): WaitlistEntryFactory
    {
        return WaitlistEntryFactory::new();
    }
}
