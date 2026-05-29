<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\QueueStatus;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

it('persists status enum + datetime casts', function () {
    $entry = SaleQueueEntry::factory()->create(['status' => QueueStatus::Active]);

    expect($entry->status)->toBe(QueueStatus::Active);
    expect($entry->last_heartbeat_at)->not->toBeNull();
});
