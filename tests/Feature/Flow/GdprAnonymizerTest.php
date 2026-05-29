<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;
use Kurt\Modules\Events\Flow\Support\GdprAnonymizer;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

function anonymizer(): GdprAnonymizer
{
    return new GdprAnonymizer(app('config'));
}

it('replaces ticket holder_name/email with hash and clears metadata', function () {
    $user = StubUser::create(['email' => 'h@x.com']);
    $ticket = Ticket::factory()->create([
        'holder_id' => $user->id,
        'holder_name' => 'Real Name',
        'holder_email' => 'real@x.com',
    ]);

    anonymizer()->anonymize($user);

    $ticket->refresh();
    expect($ticket->holder_name)->toStartWith('gdpr-');
    expect($ticket->holder_email)->toStartWith('gdpr-');
    expect($ticket->metadata)->toHaveKey('anonymized_at');
});

it('replaces OrderItemAssignment holder data', function () {
    $user = StubUser::create(['email' => 'a@x.com']);
    $item = OrderItem::factory()->create();
    $assignment = OrderItemAssignment::factory()->create([
        'order_item_id' => $item->id,
        'holder_user_id' => $user->id,
        'holder_name' => 'Real Name',
        'holder_email' => 'real@x.com',
    ]);

    anonymizer()->anonymize($user);

    $assignment->refresh();
    expect($assignment->holder_name)->toStartWith('gdpr-');
    expect($assignment->holder_email)->toStartWith('gdpr-');
    expect($assignment->holder_metadata)->toHaveKey('anonymized_at');
});

it('clears Attendee profile to anonymized timestamp', function () {
    $user = StubUser::create(['email' => 'p@x.com']);
    $event = CatalogEvent::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['name' => 'Bob', 'date_of_birth' => '1990-01-01'],
    ]);

    anonymizer()->anonymize($user);

    $attendee->refresh();
    expect($attendee->profile)->toHaveKey('anonymized_at');
    expect($attendee->profile)->not->toHaveKey('name');
});

it('nullifies audit_log.actor_id when config is true (default)', function () {
    config()->set('events.gdpr.anonymize_audit_log_actor', true);

    $user = StubUser::create(['email' => 'al@x.com']);
    $entry = AuditLogEntry::factory()->create([
        'actor_id' => $user->id,
        'action' => 'something',
        'occurred_at' => now(),
    ]);

    anonymizer()->anonymize($user);

    $entry->refresh();
    expect($entry->actor_id)->toBeNull();
});

it('keeps audit_log.actor_id when config is false', function () {
    config()->set('events.gdpr.anonymize_audit_log_actor', false);

    $user = StubUser::create(['email' => 'b@x.com']);
    $entry = AuditLogEntry::factory()->create([
        'actor_id' => $user->id,
        'action' => 'kept',
        'occurred_at' => now(),
    ]);

    anonymizer()->anonymize($user);

    $entry->refresh();
    expect($entry->actor_id)->toBe($user->id);
});
