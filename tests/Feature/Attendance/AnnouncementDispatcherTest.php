<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementRecipientStatus;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Events\AnnouncementSent;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\AnnouncementRecipient;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Notifications\EventAnnouncementPosted;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function dispatcher(): AnnouncementDispatcher
{
    return new AnnouncementDispatcher(app('config'));
}

it('dispatches to all attendees when audience is All', function () {
    Event::fake([AnnouncementSent::class]);

    $catalogEvent = CatalogEvent::factory()->create();
    $a1 = Attendee::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 1, 'status' => AttendeeStatus::Registered]);
    $a2 = Attendee::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 2, 'status' => AttendeeStatus::CheckedIn]);
    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::All,
    ]);

    $count = dispatcher()->dispatch($announcement);

    expect($count)->toBe(2);
    expect(AnnouncementRecipient::query()
        ->where('announcement_id', $announcement->id)
        ->where('status', AnnouncementRecipientStatus::Sent->value)
        ->count())->toBe(2);

    Event::assertDispatched(AnnouncementSent::class);
});

it('only reaches Registered attendees when audience is Registered', function () {
    $catalogEvent = CatalogEvent::factory()->create();
    Attendee::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 1, 'status' => AttendeeStatus::Registered]);
    Attendee::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 2, 'status' => AttendeeStatus::Cancelled]);
    Attendee::factory()->create(['event_id' => $catalogEvent->id, 'user_id' => 3, 'status' => AttendeeStatus::CheckedIn]);

    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::Registered,
    ]);

    $count = dispatcher()->dispatch($announcement);

    expect($count)->toBe(1);
});

it('filters by ticket_type_ids when audience is ByTicketType', function () {
    $catalogEvent = CatalogEvent::factory()->create();
    $typeA = TicketType::factory()->create(['event_id' => $catalogEvent->id]);
    $typeB = TicketType::factory()->create(['event_id' => $catalogEvent->id]);

    $ticketA = Ticket::factory()->create(['event_id' => $catalogEvent->id, 'ticket_type_id' => $typeA->id]);
    $ticketB = Ticket::factory()->create(['event_id' => $catalogEvent->id, 'ticket_type_id' => $typeB->id]);

    Attendee::factory()->create(['event_id' => $catalogEvent->id, 'ticket_id' => $ticketA->id, 'user_id' => 1]);
    Attendee::factory()->create(['event_id' => $catalogEvent->id, 'ticket_id' => $ticketB->id, 'user_id' => 2]);

    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::ByTicketType,
        'audience_filter' => ['ticket_type_ids' => [$typeA->id]],
    ]);

    $count = dispatcher()->dispatch($announcement);

    expect($count)->toBe(1);
});

it('sends notifications when events.notifications.enabled is true', function () {
    Notification::fake();
    config()->set('events.notifications.enabled', true);
    config()->set('kurtmodules.user_model', StubUser::class);

    $catalogEvent = CatalogEvent::factory()->create();
    $user = StubUser::create(['name' => 'Alice', 'email' => 'a@x.com']);
    Attendee::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => $user->id,
        'status' => AttendeeStatus::Registered,
    ]);

    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::All,
    ]);

    dispatcher()->dispatch($announcement);

    Notification::assertSentTo($user, EventAnnouncementPosted::class);
});

it('skips notifications when events.notifications.enabled is false', function () {
    Notification::fake();
    config()->set('events.notifications.enabled', false);

    $catalogEvent = CatalogEvent::factory()->create();
    $user = StubUser::create(['name' => 'Bob', 'email' => 'b@x.com']);
    Attendee::factory()->create([
        'event_id' => $catalogEvent->id,
        'user_id' => $user->id,
        'status' => AttendeeStatus::Registered,
    ]);

    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::All,
    ]);

    $count = dispatcher()->dispatch($announcement);

    expect($count)->toBe(1);
    Notification::assertNothingSent();
});

it('denormalises recipient_count on the announcement', function () {
    $catalogEvent = CatalogEvent::factory()->create();
    foreach (range(1, 3) as $i) {
        Attendee::factory()->create([
            'event_id' => $catalogEvent->id,
            'user_id' => $i + 100,
            'status' => AttendeeStatus::Registered,
        ]);
    }

    $announcement = Announcement::factory()->create([
        'event_id' => $catalogEvent->id,
        'audience' => AnnouncementAudience::All,
    ]);

    dispatcher()->dispatch($announcement);

    $announcement->refresh();
    expect($announcement->recipient_count)->toBe(3);
    expect($announcement->sent_at)->not->toBeNull();
});
