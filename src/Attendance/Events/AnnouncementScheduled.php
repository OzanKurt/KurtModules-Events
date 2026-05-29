<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Attendance\Models\Announcement;

final class AnnouncementScheduled
{
    use Dispatchable;

    public function __construct(public readonly Announcement $announcement) {}
}
