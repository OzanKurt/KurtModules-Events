<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Events\QueueReleased;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Support\QueueReleaser;

function queueReleaser(): QueueReleaser
{
    return new QueueReleaser(app('config'));
}

it('returns 0 when no waiting entries exist', function () {
    $event = CatalogEvent::factory()->create();

    $count = queueReleaser()->releaseFor($event);

    expect($count)->toBe(0);
});

it('promotes lowest-position waiting entries up to concurrency', function () {
    Event::fake([QueueReleased::class]);
    config()->set('events.queue.active_concurrency', 3);

    $catalogEvent = CatalogEvent::factory()->create();

    // Create 5 waiting entries with distinct positions
    foreach ([5, 3, 1, 4, 2] as $i => $position) {
        SaleQueueEntry::factory()->create([
            'event_id' => $catalogEvent->id,
            'user_id' => $i + 10,
            'status' => QueueStatus::Waiting,
            'position' => $position,
        ]);
    }

    $count = queueReleaser()->releaseFor($catalogEvent);

    expect($count)->toBe(3);
    $active = SaleQueueEntry::query()
        ->where('event_id', $catalogEvent->id)
        ->where('status', QueueStatus::Active->value)
        ->orderBy('position')
        ->get();
    expect($active)->toHaveCount(3);
    expect($active[0]->position)->toBe(1);
    expect($active[1]->position)->toBe(2);
    expect($active[2]->position)->toBe(3);

    Event::assertDispatchedTimes(QueueReleased::class, 3);
});

it('respects existing active entries and does not over-promote', function () {
    config()->set('events.queue.active_concurrency', 3);

    $catalogEvent = CatalogEvent::factory()->create();

    // 2 already active
    SaleQueueEntry::factory()->create([
        'event_id' => $catalogEvent->id, 'user_id' => 100, 'position' => 0,
        'status' => QueueStatus::Active,
    ]);
    SaleQueueEntry::factory()->create([
        'event_id' => $catalogEvent->id, 'user_id' => 101, 'position' => 1,
        'status' => QueueStatus::Active,
    ]);

    // 5 waiting
    foreach (range(0, 4) as $i) {
        SaleQueueEntry::factory()->create([
            'event_id' => $catalogEvent->id,
            'user_id' => $i + 200,
            'status' => QueueStatus::Waiting,
            'position' => $i + 10,
        ]);
    }

    $count = queueReleaser()->releaseFor($catalogEvent);

    expect($count)->toBe(1);
    expect(SaleQueueEntry::query()->where('event_id', $catalogEvent->id)
        ->where('status', QueueStatus::Active->value)->count())->toBe(3);
});

it('sets released_at and expires_at when promoting', function () {
    config()->set('events.queue.active_concurrency', 5);
    config()->set('events.queue.active_window_seconds', 120);

    $catalogEvent = CatalogEvent::factory()->create();
    $entry = SaleQueueEntry::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => 1,
        'status' => QueueStatus::Waiting,
        'position' => 1,
    ]);

    queueReleaser()->releaseFor($catalogEvent);

    $entry->refresh();
    expect($entry->status)->toBe(QueueStatus::Active);
    expect($entry->released_at)->not->toBeNull();
    expect($entry->expires_at)->not->toBeNull();
    expect((int) $entry->released_at->diffInSeconds($entry->expires_at, true))->toBe(120);
});

it('returns 0 when concurrency is already saturated', function () {
    config()->set('events.queue.active_concurrency', 2);

    $catalogEvent = CatalogEvent::factory()->create();
    SaleQueueEntry::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 1, 'status' => QueueStatus::Active]);
    SaleQueueEntry::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 2, 'status' => QueueStatus::Active]);
    SaleQueueEntry::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 3, 'status' => QueueStatus::Waiting]);

    $count = queueReleaser()->releaseFor($catalogEvent);

    expect($count)->toBe(0);
});
