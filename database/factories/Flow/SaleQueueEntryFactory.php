<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

/**
 * @extends Factory<SaleQueueEntry>
 */
class SaleQueueEntryFactory extends Factory
{
    /** @var class-string<SaleQueueEntry> */
    protected $model = SaleQueueEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => 1,
            'joined_at' => now(),
            'position' => $this->faker->numberBetween(1, 100),
            'last_heartbeat_at' => now(),
            'status' => QueueStatus::Waiting,
        ];
    }
}
