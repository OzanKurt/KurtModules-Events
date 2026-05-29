<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;

final class DocumentRejected
{
    use Dispatchable;

    public function __construct(public readonly DocumentVerification $verification) {}
}
