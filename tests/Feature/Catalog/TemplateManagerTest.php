<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Events\EventCreatedFromTemplate;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Models\EventTemplate;
use Kurt\Modules\Events\Catalog\Models\Session;
use Kurt\Modules\Events\Catalog\Support\TemplateManager;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function templateManager(): TemplateManager
{
    return new TemplateManager;
}

it('saveAs serializes an event to a template payload', function () {
    $source = CatalogEvent::factory()->create([
        'title' => ['en' => 'Source Event'],
        'timezone' => 'UTC',
    ]);
    Session::factory()->create(['event_id' => $source->id, 'title' => ['en' => 'Intro session']]);
    TicketType::factory()->create(['event_id' => $source->id, 'name' => ['en' => 'GA']]);

    $owner = StubUser::create(['email' => 'o@x.com']);

    $template = templateManager()->saveAs($source, $owner, 'My template');

    expect($template->owner_id)->toBe($owner->id);
    expect($template->name)->toBe('My template');
    expect($template->slug)->toBe('my-template');
    expect($template->payload)->toHaveKey('event');
    expect($template->payload)->toHaveKey('sessions');
    expect($template->payload)->toHaveKey('ticket_types');
    expect($template->payload['sessions'])->toHaveCount(1);
    expect($template->payload['ticket_types'])->toHaveCount(1);
});

it('spawn creates a new Event with sessions, ticket types, and an organizer', function () {
    Event::fake([EventCreatedFromTemplate::class]);

    $organizer = StubUser::create(['email' => 'org@x.com']);
    $template = EventTemplate::factory()->create([
        'payload' => [
            'event' => [
                'title' => ['en' => 'From Template'],
                'timezone' => 'UTC',
                'visibility' => 'public',
                'starts_at' => now()->addDays(10)->toDateTimeString(),
                'ends_at' => now()->addDays(10)->addHour()->toDateTimeString(),
            ],
            'sessions' => [
                ['title' => ['en' => 'S1'], 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHour(), 'position' => 0],
            ],
            'ticket_types' => [
                ['name' => ['en' => 'GA'], 'price_minor' => 0, 'currency' => 'USD', 'mode' => 'open', 'max_per_order' => 5, 'position' => 0, 'refundable' => true, 'transferable' => true, 'consumer_protection_exempt' => false],
            ],
        ],
    ]);

    $event = templateManager()->spawn($template, $organizer);

    expect($event)->toBeInstanceOf(CatalogEvent::class);
    expect($event->status)->toBe(EventStatus::Draft);
    expect($event->sessions()->count())->toBe(1);
    expect($event->ticketTypes()->count())->toBe(1);
    expect($event->organizers()->count())->toBe(1);
    expect($event->organizers()->first()->role)->toBe(OrganizerRole::Owner);

    $template->refresh();
    expect($template->used_count)->toBe(1);

    Event::assertDispatched(EventCreatedFromTemplate::class);
});

it('uses PendingApproval when publishing.require_approval is true', function () {
    config()->set('events.publishing.require_approval', true);

    $organizer = StubUser::create(['email' => 'p@x.com']);
    $template = EventTemplate::factory()->create([
        'payload' => [
            'event' => [
                'title' => ['en' => 'X'],
                'timezone' => 'UTC',
                'visibility' => 'public',
                'starts_at' => now()->addDays(10)->toDateTimeString(),
                'ends_at' => now()->addDays(10)->addHour()->toDateTimeString(),
            ],
        ],
    ]);

    $event = templateManager()->spawn($template, $organizer);

    expect($event->status)->toBe(EventStatus::PendingApproval);
});

it('saveAs + spawn produces a usable event', function () {
    $source = CatalogEvent::factory()->create([
        'title' => ['en' => 'Round Trip'],
        'timezone' => 'Europe/Berlin',
    ]);
    Session::factory()->create(['event_id' => $source->id, 'title' => ['en' => 'A session']]);

    $owner = StubUser::create(['email' => 'r@x.com']);

    $template = templateManager()->saveAs($source, $owner, 'Round trip template');
    $spawned = templateManager()->spawn($template, $owner);

    expect($spawned->getTranslation('title', 'en'))->toBe('Round Trip');
    expect($spawned->timezone)->toBe('Europe/Berlin');
});
