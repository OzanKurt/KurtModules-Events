<?php

declare(strict_types=1);

use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;

it('binds the Events facade-service as a singleton', function () {
    $a = app(EventsService::class);
    $b = app(EventsService::class);
    expect($a === $b)->toBeTrue();
});

it('binds the QrTokenSigner with the app key', function () {
    $signer = app(QrTokenSigner::class);
    $token = $signer->sign(1, 1);
    expect($signer->verify($token))->toMatchArray(['ticket_id' => 1, 'event_id' => 1]);
});

it('publishes the config under events key', function () {
    expect(config('events.currency'))->toBe('USD');
});
