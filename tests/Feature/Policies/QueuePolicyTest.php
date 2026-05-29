<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Policies\QueuePolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

function queuePolicy(): QueuePolicy
{
    return new QueuePolicy;
}

it('allows any authenticated user to join the queue', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $event = Event::factory()->create();

    expect(queuePolicy()->join($user, $event))->toBeTrue();
});

it('allows the entry owner to leave', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $event = Event::factory()->create();
    $entry = SaleQueueEntry::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);

    expect(queuePolicy()->leave($user, $entry))->toBeTrue();
});

it('denies non-owner from leaving', function () {
    $owner = StubUser::create(['email' => 'o@example.com']);
    $other = StubUser::create(['email' => 'x@example.com']);
    $event = Event::factory()->create();
    $entry = SaleQueueEntry::factory()->create(['event_id' => $event->id, 'user_id' => $owner->id]);

    expect(queuePolicy()->leave($other, $entry))->toBeFalse();
});
