<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Policies\TicketTypePolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function ticketTypePolicy(): TicketTypePolicy
{
    return new TicketTypePolicy;
}

it('allows manager to create a ticket type on their event', function () {
    $user = StubUser::create(['email' => 'mgr@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $user->id, 'role' => OrganizerRole::Manager]);

    expect(ticketTypePolicy()->create($user, $event))->toBeTrue();
});

it('denies scanner from creating', function () {
    $user = StubUser::create(['email' => 'scan@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $user->id, 'role' => OrganizerRole::Scanner]);

    expect(ticketTypePolicy()->create($user, $event))->toBeFalse();
});

it('allows manager to update + delete a ticket type', function () {
    $user = StubUser::create(['email' => 'mgr@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $user->id, 'role' => OrganizerRole::Manager]);
    $type = TicketType::factory()->create(['event_id' => $event->id]);

    expect(ticketTypePolicy()->update($user, $type))->toBeTrue();
    expect(ticketTypePolicy()->delete($user, $type))->toBeTrue();
});

it('denies stranger from updating', function () {
    $user = StubUser::create(['email' => 'rand@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);

    expect(ticketTypePolicy()->update($user, $type))->toBeFalse();
});
