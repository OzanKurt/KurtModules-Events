<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Ticketing\Models\Order;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /** @var class-string<Refund> */
    protected $model = Refund::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount_minor' => 1000,
            'currency' => 'USD',
            'reason' => RefundReason::AttendeeRequest,
            'status' => RefundStatus::Pending,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn () => [
            'status' => RefundStatus::Processed,
            'processed_at' => now(),
        ]);
    }
}
