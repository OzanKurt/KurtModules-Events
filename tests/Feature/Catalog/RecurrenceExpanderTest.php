<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Support\RecurrenceExpander;

it('returns 0 when recurrence_rule is empty', function () {
    $event = CatalogEvent::factory()->create(['recurrence_rule' => null]);
    expect((new RecurrenceExpander)->expand($event, 90))->toBe(0);
});

it('generates weekly occurrences within the window', function () {
    $start = now()->startOfDay()->addHour();
    $event = CatalogEvent::factory()->create([
        'starts_at' => $start,
        'ends_at' => $start->copy()->addHour(),
        'recurrence_rule' => [
            'frequency' => 'weekly',
            'interval' => 1,
        ],
    ]);

    $count = (new RecurrenceExpander)->expand($event, 30);

    expect($count)->toBe(4);
    expect(CatalogEvent::query()->where('parent_event_id', $event->id)->count())->toBe(4);
});

it('is idempotent on re-run for the same window', function () {
    $start = now()->startOfDay()->addHour();
    $event = CatalogEvent::factory()->create([
        'starts_at' => $start,
        'ends_at' => $start->copy()->addHour(),
        'recurrence_rule' => [
            'frequency' => 'weekly',
            'interval' => 1,
        ],
    ]);

    (new RecurrenceExpander)->expand($event, 30);
    $second = (new RecurrenceExpander)->expand($event, 30);

    expect($second)->toBe(0);
    expect(CatalogEvent::query()->where('parent_event_id', $event->id)->count())->toBe(4);
});

it('respects the count rule limit', function () {
    $start = now()->startOfDay()->addHour();
    $event = CatalogEvent::factory()->create([
        'starts_at' => $start,
        'ends_at' => $start->copy()->addHour(),
        'recurrence_rule' => [
            'frequency' => 'daily',
            'interval' => 1,
            'count' => 3,
        ],
    ]);

    $count = (new RecurrenceExpander)->expand($event, 30);

    expect($count)->toBe(3);
});
