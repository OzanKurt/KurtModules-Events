<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Support;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Catalog\Models\Event;

final readonly class DraftOrder
{
    /** @param array<int, array{ticket_type_id: int, quantity: int, unit_price_minor: int}> $items */
    public function __construct(
        public Event $event,
        public Model $buyer,
        public string $currency,
        public array $items,
    ) {}

    public function subtotalMinor(): int
    {
        return array_sum(array_map(fn ($i) => $i['quantity'] * $i['unit_price_minor'], $this->items));
    }

    public function totalQuantity(): int
    {
        return array_sum(array_column($this->items, 'quantity'));
    }
}
