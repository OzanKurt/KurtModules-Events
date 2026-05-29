<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\FreeFormEvaluator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('always returns pending awaiting reviewer', function () {
    $user = StubUser::create(['email' => 'freeform@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = (new FreeFormEvaluator)->evaluate($attendee, ['question' => 'Why join?']);

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('Awaiting reviewer');
});
