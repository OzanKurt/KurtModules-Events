<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\TicketTransferred;
use Kurt\Modules\Events\Ticketing\Events\TicketTransferRequested;
use Kurt\Modules\Events\Ticketing\Exceptions\TransferNotAllowed;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Kurt\Modules\Events\Ticketing\Support\TransferEngine;

it('completes a free transfer immediately', function () {
    Event::fake([TicketTransferred::class, TicketTransferRequested::class]);

    $event = CatalogEvent::factory()->create([
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'transferable' => true,
        'transfer_fee_minor' => null,
    ]);
    $oldHolder = StubUser::create(['name' => 'Old', 'email' => 'old@x.com']);
    $newHolder = StubUser::create(['name' => 'New Holder', 'email' => 'new@x.com']);

    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'holder_id' => $oldHolder->id,
    ]);

    $result = (new TransferEngine)->attemptTransfer($ticket, $newHolder);
    $result->refresh();

    expect($result->holder_id)->toBe($newHolder->id);
    expect($result->holder_name)->toBe('New Holder');
    expect($result->holder_email)->toBe('new@x.com');
    expect($result->transferred_from)->toBe($oldHolder->id);
    expect($result->transferred_at)->not->toBeNull();
    expect($result->transfer_fee_order_id)->toBeNull();

    Event::assertDispatched(TicketTransferred::class);
    Event::assertNotDispatched(TicketTransferRequested::class);
});

it('creates a pending fee order when transfer_fee_minor > 0', function () {
    Event::fake([TicketTransferred::class, TicketTransferRequested::class]);

    $event = CatalogEvent::factory()->create([
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'transferable' => true,
        'transfer_fee_minor' => 500,
        'transfer_fee_currency' => 'USD',
    ]);
    $oldHolder = StubUser::create(['email' => 'a@x.com']);
    $newHolder = StubUser::create(['name' => 'Buyer', 'email' => 'b@x.com']);

    $ticket = Ticket::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'holder_id' => $oldHolder->id,
    ]);

    $result = (new TransferEngine)->attemptTransfer($ticket, $newHolder);
    $result->refresh();

    expect($result->transfer_fee_order_id)->not->toBeNull();
    expect($result->holder_id)->toBe($oldHolder->id); // not yet transferred

    $feeOrder = $result->transferFeeOrder;
    expect($feeOrder->status)->toBe(OrderStatus::Pending);
    expect($feeOrder->total_minor)->toBe(500);
    expect($feeOrder->currency)->toBe('USD');
    expect($feeOrder->metadata['transfer_for_ticket_id'])->toBe($ticket->id);

    Event::assertDispatched(TicketTransferRequested::class);
    Event::assertNotDispatched(TicketTransferred::class);
});

it('rejects transfer when ticket type is not transferable', function () {
    $event = CatalogEvent::factory()->create([
        'starts_at' => now()->addDays(10),
        'ends_at' => now()->addDays(10)->addHours(2),
    ]);
    $type = TicketType::factory()->nontransferable()->create(['event_id' => $event->id]);
    $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id]);
    $newHolder = StubUser::create(['email' => 'x@x.com']);

    expect(fn () => (new TransferEngine)->attemptTransfer($ticket, $newHolder))
        ->toThrow(TransferNotAllowed::class, 'Type not transferable');
});

it('rejects transfer after the deadline', function () {
    $event = CatalogEvent::factory()->create([
        'starts_at' => now()->addHours(5),
        'ends_at' => now()->addHours(7),
    ]);
    $type = TicketType::factory()->create([
        'event_id' => $event->id,
        'transferable' => true,
        'transfer_deadline_hours_before_event' => 12, // window already closed
    ]);
    $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id]);
    $newHolder = StubUser::create(['email' => 'late@x.com']);

    expect(fn () => (new TransferEngine)->attemptTransfer($ticket, $newHolder))
        ->toThrow(TransferNotAllowed::class, 'Deadline passed');
});

it('completeTransfer falls back to email or id when new holder has no name', function () {
    Event::fake([TicketTransferred::class]);

    $event = CatalogEvent::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id, 'transferable' => true]);
    $ticket = Ticket::factory()->create(['event_id' => $event->id, 'ticket_type_id' => $type->id]);
    $newHolder = StubUser::create(['email' => 'only-email@x.com']);

    $result = (new TransferEngine)->completeTransfer($ticket, $newHolder);

    expect($result->holder_name)->toBe('only-email@x.com');
    expect($result->holder_email)->toBe('only-email@x.com');
});
