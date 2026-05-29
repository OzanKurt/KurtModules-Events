<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\GenderEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('passes when gender is in allowed list', function () {
    $user = StubUser::create(['email' => 'gender-pass@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['gender' => 'female'],
    ]);

    $result = (new GenderEvaluator)->evaluate($attendee, ['allowed' => ['female', 'nonbinary']]);

    expect($result->status)->toBe(CheckStatus::Passed);
    expect($result->data['gender'])->toBe('female');
});

it('fails when gender is not allowed', function () {
    $user = StubUser::create(['email' => 'gender-fail@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['gender' => 'male'],
    ]);

    $result = (new GenderEvaluator)->evaluate($attendee, ['allowed' => ['female']]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Gender not permitted');
});

it('returns pending when gender missing', function () {
    $user = StubUser::create(['email' => 'gender-pending@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'profile' => ['name' => 'NoGender'],
    ]);

    $result = (new GenderEvaluator)->evaluate($attendee, ['allowed' => ['female']]);

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('Gender not provided');
});
