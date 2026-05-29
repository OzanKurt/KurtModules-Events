<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketTypeMode;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('rejected application with paid reservation triggers refund', function () {
    $applicant = StubUser::create(['email' => 'app@x.com']);
    $organizer = StubUser::create(['email' => 'org@x.com']);
    $event = Event::factory()->create();
    $event->organizers()->create(['user_id' => $organizer->id, 'role' => OrganizerRole::Owner]);
    $type = TicketType::factory()->for($event)->create([
        'mode' => TicketTypeMode::Application,
        'price_minor' => 10000,
        'currency' => 'USD',
    ]);

    $events = app(EventsService::class);

    // Manually create paid reservation order tied to the application.
    $order = Order::factory()->for($event)->create([
        'user_id' => $applicant->id,
        'status' => OrderStatus::Paid,
        'total_minor' => 10000,
        'currency' => 'USD',
        'paid_at' => now()->subDay(),
    ]);

    $app = $events->apply($type, $applicant);
    $app->forceFill(['reservation_order_id' => $order->id])->save();

    $refund = $events->reject($app, $organizer, 'Not the right fit');

    expect($refund)->not->toBeNull();
    expect($refund->reason)->toBe(RefundReason::Rejection);
    expect($refund->status)->toBe(RefundStatus::Pending);
});

it('approved application does not refund a paid reservation', function () {
    $applicant = StubUser::create(['email' => 'app2@x.com']);
    $organizer = StubUser::create(['email' => 'org2@x.com']);
    $event = Event::factory()->create();
    $event->organizers()->create(['user_id' => $organizer->id, 'role' => OrganizerRole::Owner]);
    $type = TicketType::factory()->for($event)->create([
        'mode' => TicketTypeMode::Application,
        'price_minor' => 10000,
        'currency' => 'USD',
    ]);

    $events = app(EventsService::class);

    $app = $events->apply($type, $applicant);
    $approved = $events->approve($app, $organizer);

    expect($approved->status->value)->toBe('approved');
    expect($approved->decided_by)->toBe($organizer->id);
});
