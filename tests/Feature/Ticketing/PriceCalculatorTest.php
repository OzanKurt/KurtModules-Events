<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\DiscountApplicationScope;
use Kurt\Modules\Events\Ticketing\Exceptions\DiscountCodeNotApplicable;
use Kurt\Modules\Events\Ticketing\Models\DiscountCode;
use Kurt\Modules\Events\Ticketing\Models\DiscountCodeUsage;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Support\DraftOrder;
use Kurt\Modules\Events\Ticketing\Support\PriceCalculator;

function draft(string $currency = 'USD', int $unit = 10_000, int $qty = 2): DraftOrder
{
    $event = Event::factory()->create();
    $buyer = StubUser::create(['email' => 'buyer-'.uniqid().'@x.com']);

    return new DraftOrder(
        event: $event,
        buyer: $buyer,
        currency: $currency,
        items: [
            ['ticket_type_id' => 1, 'quantity' => $qty, 'unit_price_minor' => $unit],
        ],
    );
}

it('applies a percent discount (10 percent off 20,000 cents = 18,000 total)', function () {
    $draft = draft(); // 2 * 10,000 = 20,000
    $code = DiscountCode::factory()->create(['amount_minor' => 1000]); // 10.00%

    $breakdown = (new PriceCalculator)->apply($draft, $code);

    expect($breakdown->subtotalMinor)->toBe(20_000);
    expect($breakdown->discountMinor)->toBe(2_000);
    expect($breakdown->totalMinor)->toBe(18_000);
    expect($breakdown->currency)->toBe('USD');
});

it('applies flat-amount order scope discount independent of quantity', function () {
    $draft = draft('USD', 10_000, 3); // 30,000 subtotal
    $code = DiscountCode::factory()->flatAmount(500, 'USD')->create([
        'application_scope' => DiscountApplicationScope::Order,
    ]);

    $breakdown = (new PriceCalculator)->apply($draft, $code);

    expect($breakdown->discountMinor)->toBe(500);
    expect($breakdown->totalMinor)->toBe(29_500);
});

it('applies flat-amount per-ticket scope discount scaled by quantity', function () {
    $draft = draft('USD', 10_000, 3); // 30,000 subtotal
    $code = DiscountCode::factory()->flatAmount(500, 'USD')->create([
        'application_scope' => DiscountApplicationScope::PerTicket,
    ]);

    $breakdown = (new PriceCalculator)->apply($draft, $code);

    expect($breakdown->discountMinor)->toBe(1500); // 500 * 3
    expect($breakdown->totalMinor)->toBe(28_500);
});

it('throws when flat-amount currency does not match draft currency', function () {
    $draft = draft('USD');
    $code = DiscountCode::factory()->flatAmount(500, 'EUR')->create();

    expect(fn () => (new PriceCalculator)->apply($draft, $code))
        ->toThrow(DiscountCodeNotApplicable::class, 'currency_mismatch');
});

it('throws when discount code is expired', function () {
    $draft = draft();
    $code = DiscountCode::factory()->create(['expires_at' => now()->subDay()]);

    expect(fn () => (new PriceCalculator)->apply($draft, $code))
        ->toThrow(DiscountCodeNotApplicable::class, 'inactive');
});

it('throws when discount code active flag is false', function () {
    $draft = draft();
    $code = DiscountCode::factory()->create(['active' => false]);

    expect(fn () => (new PriceCalculator)->apply($draft, $code))
        ->toThrow(DiscountCodeNotApplicable::class, 'inactive');
});

it('throws when per-user usage limit is reached', function () {
    $draft = draft();
    $code = DiscountCode::factory()->create(['max_uses_per_user' => 1]);

    $order = Order::factory()->create(['user_id' => $draft->buyer->getKey()]);
    DiscountCodeUsage::factory()->create([
        'discount_code_id' => $code->id,
        'order_id' => $order->id,
        'user_id' => $draft->buyer->getKey(),
    ]);

    expect(fn () => (new PriceCalculator)->apply($draft, $code))
        ->toThrow(DiscountCodeNotApplicable::class, 'per_user_limit');
});

it('clamps discount to the subtotal so totals never go negative', function () {
    $draft = draft('USD', 1_000, 1); // 1,000 subtotal
    $code = DiscountCode::factory()->flatAmount(5_000, 'USD')->create([
        'application_scope' => DiscountApplicationScope::Order,
    ]);

    $breakdown = (new PriceCalculator)->apply($draft, $code);

    expect($breakdown->discountMinor)->toBe(1_000);
    expect($breakdown->totalMinor)->toBe(0);
});

it('throws when event subset scope does not include the draft event', function () {
    $draft = draft();
    $code = DiscountCode::factory()->scopedToEventsSubset()->create();

    expect(fn () => (new PriceCalculator)->apply($draft, $code))
        ->toThrow(DiscountCodeNotApplicable::class, 'scope_mismatch');
});

it('returns subtotal and zero discount when no code is supplied', function () {
    $draft = draft();

    $breakdown = (new PriceCalculator)->apply($draft);

    expect($breakdown->subtotalMinor)->toBe(20_000);
    expect($breakdown->discountMinor)->toBe(0);
    expect($breakdown->totalMinor)->toBe(20_000);
});
