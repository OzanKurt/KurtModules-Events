<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Catalog\Models\Session;

final class SessionDeleted
{
    use Dispatchable;

    public function __construct(public readonly Session $session) {}
}
