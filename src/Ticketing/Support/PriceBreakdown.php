<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Support;

final readonly class PriceBreakdown
{
    public function __construct(
        public int $subtotalMinor,
        public int $discountMinor,
        public int $totalMinor,
        public string $currency,
    ) {}
}
