<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\EventVisibility;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;

final class EventPolicy
{
    public function view(?Authenticatable $user, Event $event): bool
    {
        if ($event->visibility === EventVisibility::Public && $event->status === EventStatus::Published) {
            return true;
        }
        if ($user === null) {
            return false;
        }

        return $this->isOrganizer($user, $event) || $this->isStaff($user);
    }

    public function update(Authenticatable $user, Event $event): bool
    {
        return $this->hasOrganizerRole($user, $event, [OrganizerRole::Owner, OrganizerRole::Manager])
            || $this->isStaff($user);
    }

    public function delete(Authenticatable $user, Event $event): bool
    {
        return $this->hasOrganizerRole($user, $event, [OrganizerRole::Owner])
            || $this->isStaff($user);
    }

    public function approveForPublication(Authenticatable $user, Event $event): bool
    {
        return Gate::forUser($user)->allows('canManageEventApprovals');
    }

    private function isOrganizer(Authenticatable $user, Event $event): bool
    {
        return $event->organizers()->where('user_id', $user->getAuthIdentifier())->exists();
    }

    /** @param array<int, OrganizerRole> $roles */
    private function hasOrganizerRole(Authenticatable $user, Event $event, array $roles): bool
    {
        return $event->organizers()
            ->where('user_id', $user->getAuthIdentifier())
            ->whereIn('role', array_map(fn (OrganizerRole $r) => $r->value, $roles))
            ->exists();
    }

    private function isStaff(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('canManageEvents');
    }
}
