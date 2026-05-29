<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Events\OrderPaid;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

// Observers are registered by EventsServiceProvider; no manual setup needed.

it('TicketObserver increments tickets_sold_count when ticket is created issued', function () {
    $event = CatalogEvent::factory()->create(['tickets_sold_count' => 0]);
    Ticket::factory()->create(['event_id' => $event->id, 'status' => TicketStatus::Issued]);

    expect($event->fresh()->tickets_sold_count)->toBe(1);
});

it('TicketObserver does not increment when created in non-issued status', function () {
    $event = CatalogEvent::factory()->create(['tickets_sold_count' => 0]);
    Ticket::factory()->create(['event_id' => $event->id, 'status' => TicketStatus::Cancelled]);

    expect($event->fresh()->tickets_sold_count)->toBe(0);
});

it('TicketObserver decrements when an issued ticket transitions to cancelled', function () {
    $event = CatalogEvent::factory()->create(['tickets_sold_count' => 0]);
    $ticket = Ticket::factory()->create(['event_id' => $event->id, 'status' => TicketStatus::Issued]);
    expect($event->fresh()->tickets_sold_count)->toBe(1);

    $ticket->update(['status' => TicketStatus::Cancelled]);

    expect($event->fresh()->tickets_sold_count)->toBe(0);
});

it('OrderObserver dispatches OrderPaid only when status changes to Paid', function () {
    Event::fake([OrderPaid::class]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $order->update(['processor' => 'stripe']); // unrelated change
    Event::assertNotDispatched(OrderPaid::class);

    $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    Event::assertDispatched(OrderPaid::class, fn (OrderPaid $e) => $e->order->id === $order->id);
});

it('OrderObserver accrues payouts to organizers with commission_basis_points', function () {
    Event::fake([OrderPaid::class]);

    $event = CatalogEvent::factory()->create();
    $org = StubUser::create(['email' => 'org@x.com']);
    EventOrganizer::factory()->create([
        'event_id' => $event->id,
        'user_id' => $org->id,
        'commission_basis_points' => 5000, // 50%
    ]);

    $order = Order::factory()->create([
        'event_id' => $event->id,
        'status' => OrderStatus::Pending,
        'total_minor' => 10_000,
        'currency' => 'USD',
    ]);
    $order->update(['status' => OrderStatus::Paid]);

    $entries = PayoutLedgerEntry::query()->where('order_id', $order->id)->get();
    expect($entries)->toHaveCount(1);
    expect($entries[0]->amount_minor)->toBe(5_000);
    expect($entries[0]->organizer_user_id)->toBe($org->id);
    expect($entries[0]->currency)->toBe('USD');
});

it('OrderObserver completes a transfer when paying a transfer fee order', function () {
    Event::fake([OrderPaid::class]);

    $event = CatalogEvent::factory()->create(['starts_at' => now()->addDays(5), 'ends_at' => now()->addDays(5)->addHour()]);
    $ticket = Ticket::factory()->create(['event_id' => $event->id]);

    $newHolder = StubUser::create(['name' => 'NewName', 'email' => 'new@x.com']);
    $feeOrder = Order::factory()->create([
        'event_id' => $event->id,
        'user_id' => $newHolder->id,
        'status' => OrderStatus::Pending,
        'total_minor' => 500,
        'metadata' => ['transfer_for_ticket_id' => $ticket->id],
    ]);

    $feeOrder->update(['status' => OrderStatus::Paid]);

    $ticket->refresh();
    expect($ticket->holder_id)->toBe($newHolder->id);
    expect($ticket->holder_name)->toBe('NewName');
});
