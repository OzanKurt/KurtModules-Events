<?php

declare(strict_types=1);

namespace Database\Factories\Kurt\Modules\Events\Flow;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kurt\Modules\Events\Flow\Models\Sponsor;
use Kurt\Modules\Events\Flow\Models\SponsorCompTicket;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

/**
 * @extends Factory<SponsorCompTicket>
 */
class SponsorCompTicketFactory extends Factory
{
    /** @var class-string<SponsorCompTicket> */
    protected $model = SponsorCompTicket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sponsor_id' => Sponsor::factory(),
            'ticket_id' => Ticket::factory(),
            'issued_at' => now(),
        ];
    }
}
