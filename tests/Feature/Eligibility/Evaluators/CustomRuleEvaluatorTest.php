<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\CustomRuleEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubBadEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubCustomEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

beforeEach(function () {
    StubCustomEvaluator::$lastPayload = null;
    StubCustomEvaluator::$lastContext = null;
});

it('fails when FQCN missing', function () {
    $user = StubUser::create(['email' => 'custom-missing@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(CustomRuleEvaluator::class)->evaluate($attendee, []);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Invalid custom evaluator FQCN');
});

it('fails when class does not exist', function () {
    $user = StubUser::create(['email' => 'custom-noclass@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(CustomRuleEvaluator::class)
        ->evaluate($attendee, ['evaluator' => 'Nope\\NotAClass']);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Invalid custom evaluator FQCN');
});

it('fails when resolved class does not implement contract', function () {
    $user = StubUser::create(['email' => 'custom-bad@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(CustomRuleEvaluator::class)
        ->evaluate($attendee, ['evaluator' => StubBadEvaluator::class]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Class does not implement RequirementEvaluator');
});

it('delegates to resolved evaluator with config + context', function () {
    $user = StubUser::create(['email' => 'custom-delegate@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(CustomRuleEvaluator::class)->evaluate(
        $attendee,
        ['evaluator' => StubCustomEvaluator::class, 'config' => ['threshold' => 5]],
        ['requirement_id' => 99],
    );

    expect($result->status)->toBe(CheckStatus::Passed);
    expect(StubCustomEvaluator::$lastPayload)->toBe(['threshold' => 5]);
    expect(StubCustomEvaluator::$lastContext)->toBe(['requirement_id' => 99]);
});
