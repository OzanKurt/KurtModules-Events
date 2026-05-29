<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Events\EventCreatedFromTemplate;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventTemplate;

final class TemplateManager
{
    public function saveAs(Event $source, Model $owner, string $name, ?string $slug = null, bool $public = false): EventTemplate
    {
        return EventTemplate::create([
            'owner_id' => $owner->getKey(),
            'slug' => $slug ?? str($name)->slug()->toString(),
            'name' => $name,
            'is_public' => $public,
            'payload' => $this->snapshot($source),
        ]);
    }

    /** @param array<string, mixed> $overrides */
    public function spawn(EventTemplate $template, Model $organizer, array $overrides = []): Event
    {
        return DB::transaction(function () use ($template, $organizer, $overrides) {
            /** @var array<string, mixed> $payload */
            $payload = (array) $template->payload;

            /** @var array<string, mixed> $eventPayload */
            $eventPayload = (array) ($payload['event'] ?? []);

            $event = Event::create(array_merge($eventPayload, $overrides, [
                'status' => (bool) config('events.publishing.require_approval', false)
                    ? EventStatus::PendingApproval
                    : EventStatus::Draft,
            ]));

            $event->organizers()->create([
                'user_id' => $organizer->getKey(),
                'role' => OrganizerRole::Owner,
            ]);

            /** @var array<int, array<string, mixed>> $sessions */
            $sessions = (array) ($payload['sessions'] ?? []);
            foreach ($sessions as $session) {
                $event->sessions()->create($session);
            }

            /** @var array<int, array<string, mixed>> $ticketTypes */
            $ticketTypes = (array) ($payload['ticket_types'] ?? []);
            foreach ($ticketTypes as $type) {
                $event->ticketTypes()->create($type);
            }

            $template->increment('used_count');
            EventCreatedFromTemplate::dispatch($event, $template);

            return $event;
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(Event $event): array
    {
        $eventKeys = [
            'title', 'description', 'category_id', 'timezone',
            'attendee_list_visibility', 'visibility', 'capacity',
            'location_name', 'location_address',
            'starts_at', 'ends_at',
        ];

        $sessionKeys = ['slug', 'title', 'description', 'starts_at', 'ends_at', 'capacity', 'position'];

        $typeKeys = [
            'slug', 'name', 'description', 'mode', 'price_minor', 'currency',
            'refundable', 'self_cancel_deadline_hours_before_event',
            'capacity', 'sale_starts_at', 'sale_ends_at',
            'max_per_order', 'minimum_price_minor', 'suggested_price_minor',
            'transferable', 'transfer_deadline_hours_before_event',
            'transfer_fee_minor', 'transfer_fee_currency',
            'consumer_protection_exempt', 'position',
        ];

        return [
            'event' => $event->only($eventKeys),
            'sessions' => $event->sessions->map(fn ($s) => $s->only($sessionKeys))->all(),
            'ticket_types' => $event->ticketTypes->map(fn ($t) => $t->only($typeKeys))->all(),
        ];
    }
}
