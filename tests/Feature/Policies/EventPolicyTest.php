<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Policies\EventPolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

function eventPolicy(): EventPolicy
{
    return new EventPolicy;
}

it('allows anyone to view a public published event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Published,
        'visibility' => EventVisibility::Public,
    ]);

    expect(eventPolicy()->view(null, $event))->toBeTrue();
});

it('denies guest from viewing a private event', function () {
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Private,
    ]);

    expect(eventPolicy()->view(null, $event))->toBeFalse();
});

it('allows an organizer to view their unpublished event', function () {
    $user = StubUser::create(['email' => 'org@example.com']);
    $event = Event::factory()->create([
        'status' => EventStatus::Draft,
        'visibility' => EventVisibility::Private,
    ]);
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $user->id, 'role' => OrganizerRole::Owner]);

    expect(eventPolicy()->view($user, $event))->toBeTrue();
});

it('allows an organizer manager to update', function () {
    $user = StubUser::create(['email' => 'mgr@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $user->id, 'role' => OrganizerRole::Manager]);

    expect(eventPolicy()->update($user, $event))->toBeTrue();
});

it('denies a scanner from updating', function () {
    $user = StubUser::create(['email' => 'scan@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $user->id, 'role' => OrganizerRole::Scanner]);

    expect(eventPolicy()->update($user, $event))->toBeFalse();
});

it('only allows owner role to delete', function () {
    $owner = StubUser::create(['email' => 'own@example.com']);
    $manager = StubUser::create(['email' => 'mgr@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $owner->id, 'role' => OrganizerRole::Owner]);
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $manager->id, 'role' => OrganizerRole::Manager]);

    expect(eventPolicy()->delete($owner, $event))->toBeTrue();
    expect(eventPolicy()->delete($manager, $event))->toBeFalse();
});

it('allows approveForPublication when canManageEventApprovals gate passes', function () {
    Gate::define('canManageEventApprovals', fn () => true);
    $user = StubUser::create(['email' => 'admin@example.com']);
    $event = Event::factory()->create();

    expect(eventPolicy()->approveForPublication($user, $event))->toBeTrue();
});
