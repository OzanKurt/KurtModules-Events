<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\AuditLogEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Catalog\Models\Event;

/**
 * @property int $id
 * @property int|null $event_id
 * @property int|null $actor_id
 * @property string|null $actor_type
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<string, mixed>|null $changes
 * @property array<string, mixed>|null $context
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class AuditLogEntry extends Model
{
    /** @use HasFactory<AuditLogEntryFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_audit_log';

    /** @var list<string> */
    protected $fillable = [
        'event_id', 'actor_id', 'actor_type',
        'action', 'subject_type', 'subject_id',
        'changes', 'context', 'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'changes' => 'array',
        'context' => 'array',
        'occurred_at' => 'datetime',
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
    public function actor(): BelongsTo
    {
        return $this->userBelongsTo('actor_id');
    }

    protected static function newFactory(): AuditLogEntryFactory
    {
        return AuditLogEntryFactory::new();
    }
}
