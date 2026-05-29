<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('completes full purchase flow: reserve -> pay -> tickets issued with group assignment', function () {
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $organizer = StubUser::create(['email' => 'organizer@x.com']);
    $event = Event::factory()->create();
    $event->organizers()->create(['user_id' => $organizer->id, 'role' => OrganizerRole::Owner]);
    $type = TicketType::factory()->for($event)->create([
        'price_minor' => 5000,
        'currency' => 'USD',
        'capacity' => 10,
    ]);

    $events = app(EventsService::class);

    $order = $events->reserve($type, $buyer, 3, [
        ['name' => 'A', 'email' => 'a@x.com', 'user_id' => null],
        ['name' => 'B', 'email' => 'b@x.com', 'user_id' => null],
        ['name' => 'C', 'email' => 'c@x.com', 'user_id' => null],
    ]);

    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->total_minor)->toBe(15000);
    expect($type->refresh()->sold_count)->toBe(3);

    $events->pay($order, 'stripe', 'ch_test_123');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    expect(Ticket::where('event_id', $event->id)->count())->toBe(3);
    foreach (Ticket::where('event_id', $event->id)->get() as $ticket) {
        expect($ticket->qr_token)->not->toBe('placeholder');
    }
});
