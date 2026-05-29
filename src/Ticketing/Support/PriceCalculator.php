<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Support;

use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;
use Kurt\Modules\Events\Ticketing\Enums\DiscountKind;
use Kurt\Modules\Events\Ticketing\Exceptions\DiscountCodeNotApplicable;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;

final class PriceCalculator
{
    public function apply(DraftOrder $draft, ?DiscountCode $code = null): PriceBreakdown
    {
        $subtotal = $draft->subtotalMinor();
        $discount = 0;

        if ($code !== null) {
            $this->guardApplicable($code, $draft);

            $discount = match ($code->kind) {
                DiscountKind::Percent => intdiv($subtotal * $code->amount_minor, 10_000),
                DiscountKind::FlatAmount => $code->application_scope === DiscountApplicationScope::Order
                    ? $code->amount_minor
                    : $code->amount_minor * $draft->totalQuantity(),
            };

            $discount = min($discount, $subtotal);
        }

        $total = max(0, $subtotal - $discount);

        return new PriceBreakdown($subtotal, $discount, $total, $draft->currency);
    }

    private function guardApplicable(DiscountCode $code, DraftOrder $draft): void
    {
        if (! $code->isActive()) {
            throw DiscountCodeNotApplicable::code('inactive');
        }
        if ($code->kind === DiscountKind::FlatAmount && $code->currency !== $draft->currency) {
            throw DiscountCodeNotApplicable::code('currency_mismatch');
        }
        if ($code->max_uses_per_user !== null && $code->usedByUserCount($draft->buyer) >= $code->max_uses_per_user) {
            throw DiscountCodeNotApplicable::code('per_user_limit');
        }
        if (! $code->appliesToEvent($draft->event)) {
            throw DiscountCodeNotApplicable::code('scope_mismatch');
        }
    }
}
