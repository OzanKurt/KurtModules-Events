<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Exceptions\SelfCancellationNotPermitted;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
    config()->set('events.refunds.consumer_protection_window_days', 14);
});

it('auto-approves refund within EU window for non-exempt unchecked-in tickets', function () {
    $event = Event::factory()->create([
        'starts_at' => now()->addDays(30),
        'ends_at' => now()->addDays(30)->addHour(),
    ]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id,
        'user_id' => $buyer->id,
        'paid_at' => now()->subDays(5),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => false,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id,
        'ticket_type_id' => $type->id,
        'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    $refund = app(EventsService::class)->cancelOrderByBuyer($order, $buyer);

    expect($refund->status)->toBe(RefundStatus::Pending);
    expect($refund->metadata['consumer_protection_eligible'])->toBeTrue();
});

it('throws when EU window expired and no self-cancel deadline', function () {
    $event = Event::factory()->create([
        'starts_at' => now()->addDays(30),
        'ends_at' => now()->addDays(30)->addHour(),
    ]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id,
        'user_id' => $buyer->id,
        'paid_at' => now()->subDays(20),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => false,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id,
        'ticket_type_id' => $type->id,
        'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    expect(fn () => app(EventsService::class)->cancelOrderByBuyer($order, $buyer))
        ->toThrow(SelfCancellationNotPermitted::class);
});

it('honours self-cancel deadline outside EU window', function () {
    // Event is 5 days away (120h), per-type deadline is 48h before event → still inside deadline window.
    $event = Event::factory()->create([
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(5)->addHour(),
    ]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id,
        'user_id' => $buyer->id,
        'paid_at' => now()->subDays(30),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => false,
        'self_cancel_deadline_hours_before_event' => 48,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id,
        'ticket_type_id' => $type->id,
        'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    $refund = app(EventsService::class)->cancelOrderByBuyer($order, $buyer);

    expect($refund->status)->toBe(RefundStatus::Pending);
    expect($refund->metadata['consumer_protection_eligible'])->toBeFalse();
});

it('rejects when any ticket is consumer_protection_exempt', function () {
    $event = Event::factory()->create([
        'starts_at' => now()->addDays(30),
        'ends_at' => now()->addDays(30)->addHour(),
    ]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);
    $order = Order::factory()->paid()->create([
        'event_id' => $event->id,
        'user_id' => $buyer->id,
        'paid_at' => now()->subDays(5),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'consumer_protection_exempt' => true,
        'self_cancel_deadline_hours_before_event' => null,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'ticket_type_id' => $type->id]);
    Ticket::factory()->create([
        'order_item_id' => $item->id,
        'ticket_type_id' => $type->id,
        'event_id' => $event->id,
        'status' => TicketStatus::Issued,
    ]);

    expect(fn () => app(EventsService::class)->cancelOrderByBuyer($order, $buyer))
        ->toThrow(SelfCancellationNotPermitted::class);
});
