<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Policies\RefundPolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\Order;

function refundPolicy(): RefundPolicy
{
    return new RefundPolicy;
}

it('allows buyer to request a refund on their order', function () {
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $order = Order::factory()->create(['user_id' => $buyer->id]);

    expect(refundPolicy()->request($buyer, $order))->toBeTrue();
});

it('denies other user from requesting a refund', function () {
    Gate::define('canManageEvents', fn () => false);
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $other = StubUser::create(['email' => 'o@example.com']);
    $order = Order::factory()->create(['user_id' => $buyer->id]);

    expect(refundPolicy()->request($other, $order))->toBeFalse();
});

it('allows staff to process a refund via canManageEventRefunds gate', function () {
    Gate::define('canManageEventRefunds', fn () => true);
    $user = StubUser::create(['email' => 'staff@example.com']);
    $order = Order::factory()->create();
    $refund = Refund::factory()->create(['order_id' => $order->id]);

    expect(refundPolicy()->process($user, $refund))->toBeTrue();
});

it('allows buyer to view their refund', function () {
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $order = Order::factory()->create(['user_id' => $buyer->id]);
    $refund = Refund::factory()->create(['order_id' => $order->id]);

    expect(refundPolicy()->view($buyer, $refund))->toBeTrue();
});
