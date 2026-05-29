<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\RefundReason;
use Kurt\Modules\Events\Flow\Enums\RefundStatus;
use Kurt\Modules\Events\Flow\Models\Refund;

it('creates a refund with reason + status casts', function () {
    $refund = Refund::factory()->create([
        'reason' => RefundReason::OrganizerInitiated,
    ]);

    expect($refund->reason)->toBe(RefundReason::OrganizerInitiated);
    expect($refund->status)->toBe(RefundStatus::Pending);
});

it('processed state populates processed_at', function () {
    $refund = Refund::factory()->processed()->create();

    expect($refund->status)->toBe(RefundStatus::Processed);
    expect($refund->processed_at)->not->toBeNull();
});
