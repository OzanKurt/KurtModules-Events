<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class WaitlistPolicy
{
    public function join(Authenticatable $user, TicketType $type): bool
    {
        return $user->getAuthIdentifier() !== null;
    }

    public function leave(Authenticatable $user, WaitlistEntry $entry): bool
    {
        return $entry->user_id === $user->getAuthIdentifier();
    }
}
