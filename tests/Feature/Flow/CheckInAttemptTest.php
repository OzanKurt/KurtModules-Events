<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Models\CheckInAttempt;

it('records succeeded attempt', function () {
    $attempt = CheckInAttempt::factory()->create();

    expect($attempt->succeeded)->toBeTrue();
    expect($attempt->failure_reason)->toBeNull();
});

it('records failed attempt with reason', function () {
    $attempt = CheckInAttempt::factory()->failed('already_checked_in')->create();

    expect($attempt->succeeded)->toBeFalse();
    expect($attempt->failure_reason)->toBe('already_checked_in');
});
