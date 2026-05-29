<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Support;

use Carbon\Carbon;
use Kurt\Modules\Events\Catalog\Enums\EventStatus;
use Kurt\Modules\Events\Catalog\Models\Event;

final class RecurrenceExpander
{
    public function expand(Event $parent, int $windowDays): int
    {
        if (empty($parent->recurrence_rule)) {
            return 0;
        }

        $rule = (array) $parent->recurrence_rule;
        $frequency = strtolower((string) ($rule['frequency'] ?? 'weekly'));
        $interval = max(1, (int) ($rule['interval'] ?? 1));
        $until = isset($rule['until']) ? Carbon::parse((string) $rule['until']) : null;
        $count = isset($rule['count']) ? (int) $rule['count'] : null;

        $windowEnd = now()->addDays($windowDays);
        $generated = 0;
        $current = $parent->starts_at->copy();
        $duration = (int) $parent->starts_at->diffInSeconds($parent->ends_at, true);

        while (true) {
            $current = match ($frequency) {
                'daily' => $current->copy()->addDays($interval),
                'weekly' => $current->copy()->addWeeks($interval),
                'monthly' => $current->copy()->addMonths($interval),
                'yearly' => $current->copy()->addYears($interval),
                default => null,
            };

            if ($current === null || $current->gt($windowEnd)) {
                break;
            }
            if ($until !== null && $current->gt($until)) {
                break;
            }
            if ($count !== null && $generated >= $count) {
                break;
            }

            $exists = Event::query()
                ->where('parent_event_id', $parent->id)
                ->where('starts_at', $current)
                ->exists();
            if ($exists) {
                continue;
            }

            $occurrence = $parent->replicate([
                'slug',
                'tickets_sold_count',
                'attendees_count',
                'applications_pending_count',
                'recurrence_rule',
            ]);
            $occurrence->parent_event_id = $parent->id;
            $occurrence->starts_at = $current;
            $occurrence->ends_at = $current->copy()->addSeconds($duration);
            $occurrence->status = EventStatus::Published;
            $occurrence->save();

            $generated++;
        }

        return $generated;
    }
}
