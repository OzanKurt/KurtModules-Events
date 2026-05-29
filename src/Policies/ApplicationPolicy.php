<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;

final class ApplicationPolicy
{
    public function view(Authenticatable $user, Application $application): bool
    {
        if ($application->applicant_id === $user->getAuthIdentifier()) {
            return true;
        }

        return $this->isOrganizerManager($user, $application) || $this->isStaff($user);
    }

    public function withdraw(Authenticatable $user, Application $application): bool
    {
        return $application->applicant_id === $user->getAuthIdentifier();
    }

    public function approve(Authenticatable $user, Application $application): bool
    {
        return $this->isOrganizerManager($user, $application) || $this->isStaff($user);
    }

    public function reject(Authenticatable $user, Application $application): bool
    {
        return $this->isOrganizerManager($user, $application) || $this->isStaff($user);
    }

    private function isOrganizerManager(Authenticatable $user, Application $application): bool
    {
        $event = $application->event()->first();
        if (! $event instanceof Event) {
            return false;
        }

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
