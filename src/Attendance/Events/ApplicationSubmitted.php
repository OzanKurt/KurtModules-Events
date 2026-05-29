<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Attendance\Models\Application;

final class ApplicationSubmitted
{
    use Dispatchable;

    public function __construct(public readonly Application $application) {}
}
