<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\Session;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\SessionCheckIn;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('checks in a ticket independently per session', function () {
    $event = Event::factory()->create();
    $type = TicketType::factory()->for($event)->create();
    $session1 = Session::factory()->for($event)->create();
    $session2 = Session::factory()->for($event)->create();
    $type->sessions()->attach([$session1->id, $session2->id]);

    $ticket = Ticket::factory()->for($type, 'ticketType')->for($event)->create();
    $scanner = StubUser::create(['email' => 'scanner@x.com']);

    SessionCheckIn::create([
        'session_id' => $session1->id,
        'ticket_id' => $ticket->id,
        'checked_in_at' => now(),
        'checked_in_by' => $scanner->id,
    ]);

    expect(SessionCheckIn::where('session_id', $session1->id)->where('ticket_id', $ticket->id)->exists())->toBeTrue();
    expect(SessionCheckIn::where('session_id', $session2->id)->where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('records distinct check-ins per session for the same ticket', function () {
    $event = Event::factory()->create();
    $type = TicketType::factory()->for($event)->create();
    $session1 = Session::factory()->for($event)->create();
    $session2 = Session::factory()->for($event)->create();
    $type->sessions()->attach([$session1->id, $session2->id]);

    $ticket = Ticket::factory()->for($type, 'ticketType')->for($event)->create();
    $scanner = StubUser::create(['email' => 'scanner2@x.com']);

    SessionCheckIn::create([
        'session_id' => $session1->id,
        'ticket_id' => $ticket->id,
        'checked_in_at' => now(),
        'checked_in_by' => $scanner->id,
    ]);
    SessionCheckIn::create([
        'session_id' => $session2->id,
        'ticket_id' => $ticket->id,
        'checked_in_at' => now(),
        'checked_in_by' => $scanner->id,
    ]);

    expect(SessionCheckIn::where('ticket_id', $ticket->id)->count())->toBe(2);
});
