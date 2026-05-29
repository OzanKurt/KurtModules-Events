<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Catalog\Support\IcsExporter;

it('produces a valid ICS string containing VEVENT and DTSTART', function () {
    $event = CatalogEvent::factory()->create([
        'title' => ['en' => 'Demo Event'],
        'location_name' => 'Berlin HQ',
        'starts_at' => now()->addDays(7),
        'ends_at' => now()->addDays(7)->addHour(),
    ]);

    $ics = (new IcsExporter)->forEvent($event);

    expect($ics)->toContain('BEGIN:VCALENDAR');
    expect($ics)->toContain('BEGIN:VEVENT');
    expect($ics)->toContain('END:VEVENT');
    expect($ics)->toContain('END:VCALENDAR');
    expect($ics)->toContain('DTSTART:');
    expect($ics)->toContain('SUMMARY:Demo Event');
    expect($ics)->toContain('LOCATION:Berlin HQ');
});

it('emits an RRULE line when recurrence_rule is set', function () {
    $event = CatalogEvent::factory()->create([
        'recurrence_rule' => [
            'frequency' => 'weekly',
            'interval' => 1,
            'byDay' => ['MO', 'WE'],
        ],
    ]);

    $ics = (new IcsExporter)->forEvent($event);

    expect($ics)->toContain('RRULE:FREQ=WEEKLY');
    expect($ics)->toContain('INTERVAL=1');
    expect($ics)->toContain('BYDAY=MO,WE');
});

it('escapes commas and semicolons in summary/description', function () {
    $event = CatalogEvent::factory()->create([
        'title' => ['en' => 'Tech, Talks; Live'],
    ]);

    $ics = (new IcsExporter)->forEvent($event);

    expect($ics)->toContain('SUMMARY:Tech\, Talks\; Live');
});
