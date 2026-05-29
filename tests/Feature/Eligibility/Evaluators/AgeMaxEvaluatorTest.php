<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\AgeMaxEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('passes when attendee is at most max age', function () {
    $user = StubUser::create(['email' => 'agemax-pass@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(20)->toDateString()],
    ]);

    $result = (new AgeMaxEvaluator)->evaluate($attendee, ['max' => 25]);

    expect($result->status)->toBe(CheckStatus::Passed);
    expect($result->data['age'])->toBe(20);
});

it('fails when attendee older than max', function () {
    $user = StubUser::create(['email' => 'agemax-fail@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => now()->subYears(30)->toDateString()],
    ]);

    $result = (new AgeMaxEvaluator)->evaluate($attendee, ['max' => 25]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Maximum age is 25');
    expect($result->data['age'])->toBe(30);
});

it('returns pending when DOB missing', function () {
    $user = StubUser::create(['email' => 'agemax-pending@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['name' => 'NoDOB'],
    ]);

    $result = (new AgeMaxEvaluator)->evaluate($attendee, ['max' => 25]);

    expect($result->status)->toBe(CheckStatus::Pending);
});

it('fails on garbage DOB', function () {
    $user = StubUser::create(['email' => 'agemax-garbage@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['date_of_birth' => 'garbage'],
    ]);

    $result = (new AgeMaxEvaluator)->evaluate($attendee, ['max' => 25]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Invalid date of birth');
});
