<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Events\QueueReleased;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

final class QueueReleaser
{
    public function __construct(private readonly Repository $config) {}

    public function releaseFor(Event $event): int
    {
        $concurrency = (int) $this->config->get('events.queue.active_concurrency', 100);
        $windowSeconds = (int) $this->config->get('events.queue.active_window_seconds', 300);

        return DB::transaction(function () use ($event, $concurrency, $windowSeconds) {
            $activeCount = SaleQueueEntry::query()
                ->where('event_id', $event->id)
                ->where('status', QueueStatus::Active->value)
                ->lockForUpdate()
                ->count();

            $promote = max(0, $concurrency - $activeCount);
            if ($promote === 0) {
                return 0;
            }

            $waiting = SaleQueueEntry::query()
                ->where('event_id', $event->id)
                ->where('status', QueueStatus::Waiting->value)
                ->orderBy('position')
                ->limit($promote)
                ->lockForUpdate()
                ->get();

            foreach ($waiting as $entry) {
                $entry->forceFill([
                    'status' => QueueStatus::Active,
                    'released_at' => now(),
                    'expires_at' => now()->addSeconds($windowSeconds),
                ])->save();
                QueueReleased::dispatch($entry);
            }

            return $waiting->count();
        });
    }
}
