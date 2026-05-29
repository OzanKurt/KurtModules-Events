<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Attendance\Models\AttendanceResponse;

final class AttendanceFormResponseStored
{
    use Dispatchable;

    public function __construct(public readonly AttendanceResponse $response) {}
}
