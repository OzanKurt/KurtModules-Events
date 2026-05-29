<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Catalog\Events\SponsorshipPurchased;
use Kurt\Modules\Events\Flow\Enums\SponsorStatus;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorCompTicket;
use Kurt\Modules\Events\Flow\Models\SponsorTier;
use Kurt\Modules\Events\Ticketing\Enums\OrderStatus;
use Kurt\Modules\Events\Ticketing\Enums\TicketStatus;
use Kurt\Modules\Events\Ticketing\Events\TicketIssued;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItem;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;

final class SponsorCoordinator
{
    public function __construct(private readonly QrTokenSigner $signer) {}

    /**
     * Create a pending sponsor + pending sponsorship Order linking the buyer to the tier.
     *
     * @param  array<string, mixed>  $sponsorData  Optional fields persisted on the Sponsor record (website_url, blurb, ...).
     */
    public function purchaseSponsorship(
        SponsorTier $tier,
        Model $buyer,
        string $companyName,
        array $sponsorData = [],
    ): Sponsor {
        return DB::transaction(function () use ($tier, $buyer, $companyName, $sponsorData) {
            $order = Order::create([
                'event_id' => $tier->event_id,
                'user_id' => $buyer->getKey(),
                'status' => OrderStatus::Pending,
                'subtotal_minor' => $tier->price_minor,
                'discount_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => $tier->price_minor,
                'currency' => $tier->currency,
                'metadata' => ['sponsor_tier_id' => $tier->id],
            ]);

            $sponsor = Sponsor::create(array_merge($sponsorData, [
                'event_id' => $tier->event_id,
                'sponsor_tier_id' => $tier->id,
                'name' => $companyName,
                'contact_user_id' => $buyer->getKey(),
                'status' => SponsorStatus::Pending,
                'order_id' => $order->id,
                'position' => $sponsorData['position'] ?? 0,
            ]));

            SponsorshipPurchased::dispatch($sponsor);

            return $sponsor;
        });
    }

    /**
     * Issue one complimentary ticket against a sponsor's quota.
     *
     * @param  array<string, string>  $assignmentData  Optional 'name'/'email' overrides for the ticket holder.
     */
    public function issueCompTicket(
        Sponsor $sponsor,
        Model $holder,
        array $assignmentData = [],
    ): Ticket {
        $tier = $sponsor->tier()->firstOrFail();

        if ($tier->comp_ticket_type_id === null) {
            throw new \RuntimeException('Sponsor tier has no comp_ticket_type_id configured');
        }

        $issuedCount = $sponsor->compTickets()->count();
        if ($issuedCount >= $tier->comp_ticket_quota) {
            throw new \RuntimeException('Sponsor comp ticket quota exhausted');
        }

        return DB::transaction(function () use ($sponsor, $tier, $holder, $assignmentData) {
            $order = $sponsor->order ?? Order::create([
                'event_id' => $sponsor->event_id,
                'user_id' => $holder->getKey(),
                'status' => OrderStatus::Paid,
                'subtotal_minor' => 0,
                'discount_minor' => 0,
                'tax_minor' => 0,
                'total_minor' => 0,
                'currency' => $tier->currency,
                'paid_at' => now(),
                'metadata' => ['sponsor_id' => $sponsor->id],
            ]);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'ticket_type_id' => $tier->comp_ticket_type_id,
                'quantity' => 1,
                'unit_price_minor' => 0,
                'line_total_minor' => 0,
            ]);

            $ticket = Ticket::create([
                'order_item_id' => $orderItem->id,
                'ticket_type_id' => $tier->comp_ticket_type_id,
                'event_id' => $sponsor->event_id,
                'holder_id' => $holder->getKey(),
                'holder_name' => $assignmentData['name'] ?? (string) ($holder->getAttribute('name') ?? 'Sponsor Comp'),
                'holder_email' => $assignmentData['email'] ?? (string) ($holder->getAttribute('email') ?? ''),
                'status' => TicketStatus::Issued,
                'qr_token' => 'placeholder-'.bin2hex(random_bytes(8)),
                'metadata' => ['comp_for_sponsor_id' => $sponsor->id],
            ]);

            $ticket->forceFill([
                'qr_token' => $this->signer->sign($ticket->id, $sponsor->event_id),
            ])->save();

            SponsorCompTicket::create([
                'sponsor_id' => $sponsor->id,
                'ticket_id' => $ticket->id,
                'issued_at' => now(),
            ]);

            TicketIssued::dispatch($ticket);

            return $ticket;
        });
    }
}
