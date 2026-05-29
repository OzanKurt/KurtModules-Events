<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\RefundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $ticket_id
 * @property int $amount_minor
 * @property string $currency
 * @property RefundReason $reason
 * @property string|null $reason_note
 * @property RefundStatus $status
 * @property string|null $processor_reference
 * @property int|null $requested_by
 * @property int|null $processed_by
 * @property Carbon|null $processed_at
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_refunds';

    /** @var list<string> */
    protected $fillable = [
        'order_id', 'ticket_id',
        'amount_minor', 'currency',
        'reason', 'reason_note', 'status',
        'processor_reference',
        'requested_by', 'processed_by', 'processed_at',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => RefundStatus::class,
        'reason' => RefundReason::class,
        'processed_at' => 'datetime',
        'metadata' => 'array',
        'amount_minor' => 'integer',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->userBelongsTo('requested_by');
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function processedBy(): BelongsTo
    {
        return $this->userBelongsTo('processed_by');
    }

    protected static function newFactory(): RefundFactory
    {
        return RefundFactory::new();
    }
}
