<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\SaleQueueEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property Carbon $joined_at
 * @property int $position
 * @property Carbon|null $released_at
 * @property Carbon|null $expires_at
 * @property Carbon $last_heartbeat_at
 * @property QueueStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SaleQueueEntry extends Model
{
    /** @use HasFactory<SaleQueueEntryFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_sale_queue_entries';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'user_id',
        'joined_at', 'position',
        'released_at', 'expires_at',
        'last_heartbeat_at', 'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => QueueStatus::class,
        'joined_at' => 'datetime',
        'released_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    protected static function newFactory(): SaleQueueEntryFactory
    {
        return SaleQueueEntryFactory::new();
    }
}
