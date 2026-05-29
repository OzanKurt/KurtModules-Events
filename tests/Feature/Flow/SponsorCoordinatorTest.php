<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Events\Catalog\Events\SponsorshipPurchased;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Enums\SponsorStatus;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorCompTicket;
use Kurt\Modules\Events\Flow\Models\SponsorTier;
use Kurt\Modules\Events\Flow\Support\SponsorCoordinator;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Events\TicketIssued;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;

function signer(): QrTokenSigner
{
    return new QrTokenSigner('sponsor-test-key');
}

it('purchaseSponsorship creates a pending Sponsor and pending Order', function () {
    Event::fake([SponsorshipPurchased::class]);

    $event = CatalogEvent::factory()->create();
    $tier = SponsorTier::factory()->create([
        'event_id' => $event->id,
        'price_minor' => 250_000,
        'currency' => 'USD',
    ]);
    $buyer = StubUser::create(['email' => 'buyer@x.com']);

    $sponsor = (new SponsorCoordinator(signer()))->purchaseSponsorship(
        $tier,
        $buyer,
        'Acme Corp',
        ['website_url' => 'https://acme.test'],
    );

    expect($sponsor)->toBeInstanceOf(Sponsor::class);
    expect($sponsor->status)->toBe(SponsorStatus::Pending);
    expect($sponsor->name)->toBe('Acme Corp');
    expect($sponsor->website_url)->toBe('https://acme.test');
    expect($sponsor->order_id)->not->toBeNull();

    $order = $sponsor->order;
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->total_minor)->toBe(250_000);
    expect($order->currency)->toBe('USD');
    expect($order->metadata['sponsor_tier_id'])->toBe($tier->id);

    Event::assertDispatched(
        SponsorshipPurchased::class,
        fn (SponsorshipPurchased $e) => $e->sponsor->id === $sponsor->id,
    );
});

it('issueCompTicket increments the sponsor quota and issues a signed ticket', function () {
    Event::fake([TicketIssued::class]);

    $event = CatalogEvent::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $tier = SponsorTier::factory()->create([
        'event_id' => $event->id,
        'comp_ticket_quota' => 2,
        'comp_ticket_type_id' => $ticketType->id,
    ]);
    $sponsor = Sponsor::factory()->create([
        'event_id' => $event->id,
        'sponsor_tier_id' => $tier->id,
    ]);

    $holder = StubUser::create(['name' => 'Comp Person', 'email' => 'comp@x.com']);

    $ticket = (new SponsorCoordinator(signer()))->issueCompTicket($sponsor, $holder);

    expect($ticket->ticket_type_id)->toBe($ticketType->id);
    expect($ticket->event_id)->toBe($event->id);
    expect($ticket->holder_name)->toBe('Comp Person');
    expect($ticket->metadata['comp_for_sponsor_id'])->toBe($sponsor->id);

    // qr_token round-trips through signer
    $payload = signer()->verify($ticket->qr_token);
    expect($payload['ticket_id'])->toBe($ticket->id);
    expect($payload['event_id'])->toBe($event->id);

    expect(SponsorCompTicket::query()->where('sponsor_id', $sponsor->id)->count())->toBe(1);
    Event::assertDispatched(TicketIssued::class);
});

it('issueCompTicket throws when the sponsor tier has no comp_ticket_type_id', function () {
    $event = CatalogEvent::factory()->create();
    $tier = SponsorTier::factory()->create([
        'event_id' => $event->id,
        'comp_ticket_quota' => 5,
        'comp_ticket_type_id' => null,
    ]);
    $sponsor = Sponsor::factory()->create(['event_id' => $event->id, 'sponsor_tier_id' => $tier->id]);
    $holder = StubUser::create(['email' => 'h@x.com']);

    expect(fn () => (new SponsorCoordinator(signer()))->issueCompTicket($sponsor, $holder))
        ->toThrow(RuntimeException::class, 'no comp_ticket_type_id');
});

it('issueCompTicket throws when the quota is exhausted', function () {
    $event = CatalogEvent::factory()->create();
    $ticketType = TicketType::factory()->create(['event_id' => $event->id]);
    $tier = SponsorTier::factory()->create([
        'event_id' => $event->id,
        'comp_ticket_quota' => 1,
        'comp_ticket_type_id' => $ticketType->id,
    ]);
    $sponsor = Sponsor::factory()->create(['event_id' => $event->id, 'sponsor_tier_id' => $tier->id]);
    $holder1 = StubUser::create(['email' => 'first@x.com']);
    $holder2 = StubUser::create(['email' => 'second@x.com']);

    $coordinator = new SponsorCoordinator(signer());
    $coordinator->issueCompTicket($sponsor, $holder1);

    expect(fn () => $coordinator->issueCompTicket($sponsor, $holder2))
        ->toThrow(RuntimeException::class, 'quota exhausted');
});
