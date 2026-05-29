<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Ticketing\Models\Order;

final class RefundPolicy
{
    public function request(Authenticatable $user, Order $order): bool
    {
        if ($order->user_id === $user->getAuthIdentifier()) {
            return true;
        }

        return $this->isStaff($user);
    }

    public function process(Authenticatable $user, Refund $refund): bool
    {
        return Gate::forUser($user)->allows('canManageEventRefunds') || $this->isStaff($user);
    }

    public function view(Authenticatable $user, Refund $refund): bool
    {
        $order = $refund->order()->first();
        if ($order !== null && $order->user_id === $user->getAuthIdentifier()) {
            return true;
        }

        return $this->isStaff($user);
    }

    private function isStaff(Authenticatable $user): bool
    {
        return Gate::forUser($user)->allows('canManageEvents');
    }
}
