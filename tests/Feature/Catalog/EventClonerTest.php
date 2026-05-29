<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Events\EventClonedFrom;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Models\Session;
use Kurt\Modules\Events\Catalog\Support\EventCloner;
use Kurt\Modules\Events\Ticketing\Models\PriceTier;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

it('clones an event with fresh defaults and resets counters', function () {
    Event::fake([EventClonedFrom::class]);

    $source = CatalogEvent::factory()->published()->create([
        'tickets_sold_count' => 42,
        'attendees_count' => 30,
        'applications_pending_count' => 5,
    ]);

    $clone = (new EventCloner)->clone($source);

    expect($clone->id)->not->toBe($source->id);
    expect($clone->status)->toBe(EventStatus::Draft);
    expect($clone->tickets_sold_count)->toBe(0);
    expect($clone->attendees_count)->toBe(0);
    expect($clone->applications_pending_count)->toBe(0);

    Event::assertDispatched(
        EventClonedFrom::class,
        fn (EventClonedFrom $e) => $e->source->id === $source->id && $e->clone->id === $clone->id,
    );
});

it('copies sessions to the clone with new event_id', function () {
    $source = CatalogEvent::factory()->create();
    Session::factory()->create(['event_id' => $source->id, 'position' => 1]);
    Session::factory()->create(['event_id' => $source->id, 'position' => 2]);

    $clone = (new EventCloner)->clone($source);

    expect($clone->sessions()->count())->toBe(2);
    expect($source->sessions()->count())->toBe(2);
});

it('copies ticket types and price tiers with reset sold_count', function () {
    $source = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create([
        'event_id' => $source->id,
        'sold_count' => 50,
    ]);
    PriceTier::factory()->create([
        'ticket_type_id' => $type->id,
        'sold_count' => 25,
    ]);

    $clone = (new EventCloner)->clone($source);

    $cloneTypes = $clone->ticketTypes()->with('priceTiers')->get();
    expect($cloneTypes)->toHaveCount(1);
    expect($cloneTypes[0]->sold_count)->toBe(0);
    expect($cloneTypes[0]->event_id)->toBe($clone->id);
    expect($cloneTypes[0]->priceTiers)->toHaveCount(1);
    expect($cloneTypes[0]->priceTiers[0]->sold_count)->toBe(0);
});

it('respects overrides passed to clone', function () {
    $source = CatalogEvent::factory()->create();

    $clone = (new EventCloner)->clone($source, [
        'title' => ['en' => 'A new run'],
        'timezone' => 'Europe/Berlin',
    ]);

    expect($clone->getTranslation('title', 'en'))->toBe('A new run');
    expect($clone->timezone)->toBe('Europe/Berlin');
});
