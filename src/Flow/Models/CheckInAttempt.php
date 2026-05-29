<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\CheckInAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $scanner_user_id
 * @property string|null $nonce
 * @property string|null $ip
 * @property string|null $user_agent
 * @property bool $succeeded
 * @property string|null $failure_reason
 * @property Carbon $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CheckInAttempt extends Model
{
    /** @use HasFactory<CheckInAttemptFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_check_in_attempts';

    /** @var list<string> */
    protected $fillable = [
        'ticket_id', 'scanner_user_id',
        'nonce', 'ip', 'user_agent',
        'succeeded', 'failure_reason',
        'occurred_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'occurred_at' => 'datetime',
        'succeeded' => 'boolean',
    ];

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
    public function scanner(): BelongsTo
    {
        return $this->userBelongsTo('scanner_user_id');
    }

    protected static function newFactory(): CheckInAttemptFactory
    {
        return CheckInAttemptFactory::new();
    }
}
