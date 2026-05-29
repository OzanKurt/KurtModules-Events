<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Models;

use Database\Factories\Kurt\Modules\Events\Flow\PayoutLedgerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @property int $id
 * @property int $order_id
 * @property int $organizer_user_id
 * @property int $share_basis_points
 * @property int $amount_minor
 * @property string $currency
 * @property PayoutStatus $status
 * @property Carbon|null $paid_out_at
 * @property string|null $payout_reference
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PayoutLedgerEntry extends Model
{
    /** @use HasFactory<PayoutLedgerEntryFactory> */
    use HasFactory;

    use ResolvesUser;

    protected $table = 'events_payout_ledger';

    /** @var list<string> */
    protected $fillable = [
        'order_id', 'organizer_user_id',
        'share_basis_points', 'amount_minor', 'currency',
        'status', 'paid_out_at', 'payout_reference',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => PayoutStatus::class,
        'paid_out_at' => 'datetime',
        'share_basis_points' => 'integer',
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
     * @return BelongsTo<Model, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->userBelongsTo('organizer_user_id');
    }

    protected static function newFactory(): PayoutLedgerEntryFactory
    {
        return PayoutLedgerEntryFactory::new();
    }
}
