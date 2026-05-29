<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @extends Factory<PayoutLedgerEntry>
 */
class PayoutLedgerEntryFactory extends Factory
{
    /** @var class-string<PayoutLedgerEntry> */
    protected $model = PayoutLedgerEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'organizer_user_id' => 1,
            'share_basis_points' => 5000,
            'amount_minor' => 500,
            'currency' => 'USD',
            'status' => PayoutStatus::Accrued,
        ];
    }
}
