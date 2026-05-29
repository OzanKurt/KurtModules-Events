<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Support\QueuePruner;

function queuePruner(): QueuePruner
{
    return new QueuePruner(app('config'));
}

it('marks waiting entries past heartbeat timeout as abandoned', function () {
    config()->set('events.queue.heartbeat_timeout_seconds', 60);

    $event = CatalogEvent::factory()->create();

    $stale = SaleQueueEntry::factory()->create([
        'event_id' => $event->id,
        'user_id' => 1,
        'status' => QueueStatus::Waiting,
        'last_heartbeat_at' => now()->subSeconds(120),
    ]);

    $fresh = SaleQueueEntry::factory()->create([
        'event_id' => $event->id,
        'user_id' => 2,
        'status' => QueueStatus::Waiting,
        'last_heartbeat_at' => now()->subSeconds(10),
    ]);

    $count = queuePruner()->pruneFor($event);

    expect($count)->toBe(1);
    expect($stale->fresh()->status)->toBe(QueueStatus::Abandoned);
    expect($fresh->fresh()->status)->toBe(QueueStatus::Waiting);
});

it('does not touch active entries', function () {
    config()->set('events.queue.heartbeat_timeout_seconds', 60);

    $event = CatalogEvent::factory()->create();
    $active = SaleQueueEntry::factory()->create([
        'event_id' => $event->id,
        'user_id' => 1,
        'status' => QueueStatus::Active,
        'last_heartbeat_at' => now()->subSeconds(900),
    ]);

    $count = queuePruner()->pruneFor($event);

    expect($count)->toBe(0);
    expect($active->fresh()->status)->toBe(QueueStatus::Active);
});

it('returns count of pruned rows', function () {
    config()->set('events.queue.heartbeat_timeout_seconds', 30);

    $event = CatalogEvent::factory()->create();

    foreach (range(1, 4) as $i) {
        SaleQueueEntry::factory()->create([
            'event_id' => $event->id,
            'user_id' => $i,
            'status' => QueueStatus::Waiting,
            'last_heartbeat_at' => now()->subSeconds(60),
        ]);
    }

    $count = queuePruner()->pruneFor($event);

    expect($count)->toBe(4);
});
