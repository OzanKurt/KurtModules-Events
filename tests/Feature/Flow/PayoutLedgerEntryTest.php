<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\PayoutStatus;
use Kurt\Modules\Events\Flow\Models\PayoutLedgerEntry;

it('stores in events_payout_ledger table', function () {
    $entry = PayoutLedgerEntry::factory()->create();

    expect($entry->getTable())->toBe('events_payout_ledger');
    expect($entry->status)->toBe(PayoutStatus::Accrued);
});
