<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\GroupMembershipEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubGroupResolver;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

beforeEach(function () {
    StubGroupResolver::$groups = [];
    config()->set('events.requirements.group_resolver', null);
});

it('returns pending when no resolver is configured', function () {
    $user = StubUser::create(['email' => 'gm-noresolver@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(GroupMembershipEvaluator::class)
        ->evaluate($attendee, ['group' => ['vips']]);

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('No group resolver configured');
});

it('passes when user group intersects required', function () {
    config()->set('events.requirements.group_resolver', StubGroupResolver::class);
    StubGroupResolver::$groups = ['vips', 'partners'];

    $user = StubUser::create(['email' => 'gm-pass@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(GroupMembershipEvaluator::class)
        ->evaluate($attendee, ['group' => ['vips', 'staff']]);

    expect($result->status)->toBe(CheckStatus::Passed);
    expect($result->data['matched_groups'])->toBe(['vips']);
});

it('fails when no required group matches', function () {
    config()->set('events.requirements.group_resolver', StubGroupResolver::class);
    StubGroupResolver::$groups = ['guests'];

    $user = StubUser::create(['email' => 'gm-fail@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(GroupMembershipEvaluator::class)
        ->evaluate($attendee, ['group' => ['vips', 'staff']]);

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Not a member of required group');
});

it('accepts a single string group value', function () {
    config()->set('events.requirements.group_resolver', StubGroupResolver::class);
    StubGroupResolver::$groups = ['vips'];

    $user = StubUser::create(['email' => 'gm-string@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = app(GroupMembershipEvaluator::class)
        ->evaluate($attendee, ['group' => 'vips']);

    expect($result->status)->toBe(CheckStatus::Passed);
    expect($result->data['matched_groups'])->toBe(['vips']);
});
