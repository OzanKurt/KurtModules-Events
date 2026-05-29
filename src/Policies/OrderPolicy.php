<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Ticketing\Models\Order;

final class OrderPolicy
{
    public function view(Authenticatable $user, Order $order): bool
    {
        if ($order->user_id === $user->getAuthIdentifier()) {
            return true;
        }

        return $this->isStaff($user);
    }

    public function refund(Authenticatable $user, Order $order): bool
    {
        return $this->isStaff($user) || Gate::forUser($user)->allows('canManageEventRefunds');
    }

    private function isStaff(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('canManageEvents');
    }
}
