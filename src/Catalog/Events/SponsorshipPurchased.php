<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Flow\Models\Sponsor;

final class SponsorshipPurchased
{
    use Dispatchable;

    public function __construct(public readonly Sponsor $sponsor) {}
}
