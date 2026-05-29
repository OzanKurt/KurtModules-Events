<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Catalog\Support;

use Carbon\Carbon;
use Kurt\Modules\Events\Catalog\Models\Event;

final class IcsExporter
{
    public function forEvent(Event $event): string
    {
        $uid = "event-{$event->id}@kurtmodules-events";
        $now = now()->utc()->format('Ymd\THis\Z');
        $start = $event->starts_at->copy()->utc()->format('Ymd\THis\Z');
        $end = $event->ends_at->copy()->utc()->format('Ymd\THis\Z');
        $title = (string) $event->getTranslation('title', app()->getLocale());
        $description = strip_tags((string) $event->getTranslation('description', app()->getLocale(), false));
        $location = $event->location_name ?? $event->location_address ?? '';

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//KurtModules//Events//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            'SUMMARY:'.$this->escape($title),
            'DESCRIPTION:'.$this->escape($description),
            'LOCATION:'.$this->escape($location),
        ];

        if (! empty($event->recurrence_rule)) {
            $lines[] = 'RRULE:'.$this->buildRrule((array) $event->recurrence_rule);
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    /** @param array<string, mixed> $rule */
    private function buildRrule(array $rule): string
    {
        $parts = ['FREQ='.strtoupper((string) ($rule['frequency'] ?? 'WEEKLY'))];

        if (isset($rule['interval'])) {
            $parts[] = 'INTERVAL='.(int) $rule['interval'];
        }
        if (isset($rule['count'])) {
            $parts[] = 'COUNT='.(int) $rule['count'];
        }
        if (isset($rule['until'])) {
            $parts[] = 'UNTIL='.Carbon::parse((string) $rule['until'])->utc()->format('Ymd\THis\Z');
        }
        if (isset($rule['byDay']) && is_array($rule['byDay'])) {
            $parts[] = 'BYDAY='.implode(',', array_map(strval(...), $rule['byDay']));
        }
        if (isset($rule['byMonthDay']) && is_array($rule['byMonthDay'])) {
            $parts[] = 'BYMONTHDAY='.implode(',', array_map(strval(...), $rule['byMonthDay']));
        }

        return implode(';', $parts);
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $value);
    }
}
