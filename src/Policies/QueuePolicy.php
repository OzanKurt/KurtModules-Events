<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;

final class QueuePolicy
{
    public function join(Authenticatable $user, Event $event): bool
    {
        return $user->getAuthIdentifier() !== null;
    }

    public function leave(Authenticatable $user, SaleQueueEntry $entry): bool
    {
        return $entry->user_id === $user->getAuthIdentifier();
    }
}
