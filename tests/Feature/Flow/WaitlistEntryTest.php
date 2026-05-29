<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\WaitlistStatus;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;

it('persists with default status', function () {
    $entry = WaitlistEntry::factory()->create();

    expect($entry->status)->toBe(WaitlistStatus::Waiting);
});

it('handles offered state with claim window', function () {
    $entry = WaitlistEntry::factory()->create([
        'status' => WaitlistStatus::Offered,
        'offered_at' => now(),
        'claim_expires_at' => now()->addMinutes(10),
    ]);

    expect($entry->status)->toBe(WaitlistStatus::Offered);
    expect($entry->claim_expires_at)->not->toBeNull();
});
