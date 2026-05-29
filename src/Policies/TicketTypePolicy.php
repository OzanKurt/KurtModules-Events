<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

final class TicketTypePolicy
{
    public function create(Authenticatable $user, Event $event): bool
    {
        return $this->hasManagerRole($user, $event) || $this->isStaff($user);
    }

    public function update(Authenticatable $user, TicketType $type): bool
    {
        $event = $type->event()->first();
        if ($event === null) {
            return $this->isStaff($user);
        }

        return $this->hasManagerRole($user, $event) || $this->isStaff($user);
    }

    public function delete(Authenticatable $user, TicketType $type): bool
    {
        $event = $type->event()->first();
        if ($event === null) {
            return $this->isStaff($user);
        }

        return $this->hasManagerRole($user, $event) || $this->isStaff($user);
    }

    private function hasManagerRole(Authenticatable $user, Event $event): bool
    {
        return $event->organizers()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('role', [OrganizerRole::Owner->value, OrganizerRole::Manager->value])
            ->exists();
    }

    private function isStaff(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('canManageEvents');
    }
}
