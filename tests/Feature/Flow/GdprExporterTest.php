<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Flow\Support\GdprExporter;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

it('returns all expected sections in the dump', function () {
    $user = StubUser::create(['email' => 'gdpr@x.com']);
    $event = CatalogEvent::factory()->create();

    $order = Order::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    OrderItemAssignment::factory()->create(['order_item_id' => $item->id, 'holder_user_id' => $user->id]);
    $ticket = Ticket::factory()->create([
        'order_item_id' => $item->id, 'event_id' => $event->id, 'holder_id' => $user->id,
    ]);
    Attendee::factory()->create(['event_id' => $event->id, 'user_id' => $user->id, 'ticket_id' => $ticket->id]);
    Application::factory()->create(['event_id' => $event->id, 'applicant_id' => $user->id]);
    SaleQueueEntry::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
    WaitlistEntry::factory()->create(['user_id' => $user->id]);

    $dump = (new GdprExporter)->export($user);

    expect($dump)->toHaveKey('user_id');
    expect($dump)->toHaveKey('attendees');
    expect($dump)->toHaveKey('applications');
    expect($dump)->toHaveKey('orders');
    expect($dump)->toHaveKey('order_item_assignments');
    expect($dump)->toHaveKey('tickets');
    expect($dump)->toHaveKey('refunds_as_requester');
    expect($dump)->toHaveKey('document_uploads');
    expect($dump)->toHaveKey('audit_log_as_actor');
    expect($dump)->toHaveKey('sale_queue_entries');
    expect($dump)->toHaveKey('waitlist_entries');

    expect($dump['attendees'])->toHaveCount(1);
    expect($dump['applications'])->toHaveCount(1);
    expect($dump['orders'])->toHaveCount(1);
    expect($dump['tickets'])->toHaveCount(1);
    expect($dump['sale_queue_entries'])->toHaveCount(1);
    expect($dump['waitlist_entries'])->toHaveCount(1);
});

it('returns empty arrays when user has no data', function () {
    $user = StubUser::create(['email' => 'empty@x.com']);

    $dump = (new GdprExporter)->export($user);

    expect($dump['user_id'])->toBe($user->id);
    expect($dump['attendees'])->toBeEmpty();
    expect($dump['orders'])->toBeEmpty();
    expect($dump['tickets'])->toBeEmpty();
});

it('only returns the requested user data', function () {
    $a = StubUser::create(['email' => 'a@x.com']);
    $b = StubUser::create(['email' => 'b@x.com']);
    $event = CatalogEvent::factory()->create();

    Order::factory()->create(['event_id' => $event->id, 'user_id' => $a->id]);
    Order::factory()->create(['event_id' => $event->id, 'user_id' => $b->id]);

    $dump = (new GdprExporter)->export($a);

    expect($dump['orders'])->toHaveCount(1);
});
