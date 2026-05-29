<?php

declare(strict_types=1);

use Kurt\Modules\Events\Flow\Enums\SponsorStatus;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorCompTicket;
use Kurt\Modules\Events\Flow\Models\SponsorTier;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

it('creates a sponsor tier with slug', function () {
    $tier = SponsorTier::factory()->create(['name' => 'Platinum Plus']);

    expect($tier->slug)->not->toBeEmpty();
    expect($tier->name)->toBe('Platinum Plus');
});

it('creates a sponsor with status enum + tier relation', function () {
    $tier = SponsorTier::factory()->create();
    $sponsor = Sponsor::factory()->active()->create(['sponsor_tier_id' => $tier->id]);

    expect($sponsor->status)->toBe(SponsorStatus::Active);
    expect($sponsor->tier->id)->toBe($tier->id);
});

it('registers logo media collection', function () {
    $sponsor = Sponsor::factory()->create();
    $collections = $sponsor->getRegisteredMediaCollections();

    expect($collections->first()?->name)->toBe('logo');
});

it('SponsorCompTicket links sponsor and ticket', function () {
    $sponsor = Sponsor::factory()->create();
    $ticket = Ticket::factory()->create();
    $comp = SponsorCompTicket::factory()->create([
        'sponsor_id' => $sponsor->id,
        'ticket_id' => $ticket->id,
    ]);

    expect($comp->sponsor->id)->toBe($sponsor->id);
    expect($comp->ticket->id)->toBe($ticket->id);
});
