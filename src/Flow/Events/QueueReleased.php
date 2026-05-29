<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

final class QueueReleased implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(public readonly SaleQueueEntry $entry) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("events.queue.{$this->entry->event_id}.user.{$this->entry->user_id}"),
        ];
    }
}
