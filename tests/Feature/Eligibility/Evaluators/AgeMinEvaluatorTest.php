<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\AgeMinEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('passes when attendee is at least min age', function () {
    $user = StubUser::create(['email' => 'agemin-pass@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(20)->toDateString()],
    ]);

    $result = (new AgeMinEvaluator)->evaluate($attendee, ['min' => 18]);

    expect($result->status)->toBe(CheckStatus::Passed);
    expect($result->data['age'])->toBe(20);
});

it('fails when attendee younger than min', function () {
    $user = StubUser::create(['email' => 'agemin-fail@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(16)->toDateString()],
    ]);

    $result = (new AgeMinEvaluator)->evaluate($attendee, ['min' => 18]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Minimum age is 18');
    expect($result->data['age'])->toBe(16);
});

it('returns pending when DOB missing', function () {
    $user = StubUser::create(['email' => 'agemin-pending@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['name' => 'No DOB'],
    ]);

    $result = (new AgeMinEvaluator)->evaluate($attendee, ['min' => 18]);

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('Date of birth not provided');
});

it('fails on garbage DOB', function () {
    $user = StubUser::create(['email' => 'agemin-garbage@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => 'not-a-date'],
    ]);

    $result = (new AgeMinEvaluator)->evaluate($attendee, ['min' => 18]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Invalid date of birth');
});
