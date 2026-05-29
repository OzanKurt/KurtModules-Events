<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Eligibility\Models\RequirementCheck;

final class RequirementCheckFailed
{
    use Dispatchable;

    public function __construct(public readonly RequirementCheck $check) {}
}
