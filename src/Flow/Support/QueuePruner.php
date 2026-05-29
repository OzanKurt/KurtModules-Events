<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Contracts\Config\Repository;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

final class QueuePruner
{
    public function __construct(private readonly Repository $config) {}

    public function pruneFor(Event $event): int
    {
        $cutoff = now()->subSeconds((int) $this->config->get('events.queue.heartbeat_timeout_seconds', 60));

        return SaleQueueEntry::query()
            ->where('event_id', $event->id)
            ->where('status', QueueStatus::Waiting->value)
            ->where('last_heartbeat_at', '<', $cutoff)
            ->update(['status' => QueueStatus::Abandoned->value]);
    }
}
