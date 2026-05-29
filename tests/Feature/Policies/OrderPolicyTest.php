<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Events\Policies\OrderPolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\Order;

function orderPolicy(): OrderPolicy
{
    return new OrderPolicy;
}

it('allows the buyer to view their order', function () {
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $order = Order::factory()->create(['user_id' => $buyer->id]);

    expect(orderPolicy()->view($buyer, $order))->toBeTrue();
});

it('denies another user from viewing the order', function () {
    $buyer = StubUser::create(['email' => 'b@example.com']);
    $other = StubUser::create(['email' => 'o@example.com']);
    $order = Order::factory()->create(['user_id' => $buyer->id]);

    expect(orderPolicy()->view($other, $order))->toBeFalse();
});

it('allows staff to view any order via canManageEvents gate', function () {
    Gate::define('canManageEvents', fn () => true);
    $other = StubUser::create(['email' => 'staff@example.com']);
    $order = Order::factory()->create(['user_id' => 9999]);

    expect(orderPolicy()->view($other, $order))->toBeTrue();
});

it('only allows staff to refund', function () {
    Gate::define('canManageEvents', fn () => false);
    $user = StubUser::create(['email' => 'b@example.com']);
    $order = Order::factory()->create(['user_id' => $user->id]);

    expect(orderPolicy()->refund($user, $order))->toBeFalse();

    Gate::define('canManageEvents', fn () => true);
    expect(orderPolicy()->refund($user, $order))->toBeTrue();
});
