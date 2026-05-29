<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Policies\WaitlistPolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function waitlistPolicy(): WaitlistPolicy
{
    return new WaitlistPolicy;
}

it('allows any authenticated user to join the waitlist', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $type = TicketType::factory()->create();

    expect(waitlistPolicy()->join($user, $type))->toBeTrue();
});

it('allows the entry owner to leave', function () {
    $user = StubUser::create(['email' => 'u@example.com']);
    $type = TicketType::factory()->create();
    $entry = WaitlistEntry::factory()->create(['ticket_type_id' => $type->id, 'user_id' => $user->id]);

    expect(waitlistPolicy()->leave($user, $entry))->toBeTrue();
});

it('denies non-owner from leaving the waitlist', function () {
    $owner = StubUser::create(['email' => 'o@example.com']);
    $other = StubUser::create(['email' => 'x@example.com']);
    $type = TicketType::factory()->create();
    $entry = WaitlistEntry::factory()->create(['ticket_type_id' => $type->id, 'user_id' => $owner->id]);

    expect(waitlistPolicy()->leave($other, $entry))->toBeFalse();
});
