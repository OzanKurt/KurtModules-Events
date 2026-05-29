<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Flow\Models\CheckInAttempt;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @extends Factory<CheckInAttempt>
 */
class CheckInAttemptFactory extends Factory
{
    /** @var class-string<CheckInAttempt> */
    protected $model = CheckInAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'succeeded' => true,
            'occurred_at' => now(),
        ];
    }

    public function failed(string $reason): static
    {
        return $this->state(fn () => [
            'succeeded' => false,
            'failure_reason' => $reason,
        ]);
    }
}
