<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;

it('stores in events_audit_log table with action key', function () {
    $event = Event::factory()->create();
    $entry = AuditLogEntry::factory()->create([
        'event_id' => $event->id,
        'action' => 'event.published',
        'changes' => ['before' => ['status' => 'draft'], 'after' => ['status' => 'published']],
        'context' => ['ip' => '127.0.0.1'],
    ]);

    expect($entry->getTable())->toBe('events_audit_log');
    expect($entry->action)->toBe('event.published');
    expect($entry->changes)->toBe(['before' => ['status' => 'draft'], 'after' => ['status' => 'published']]);
    expect($entry->context)->toBe(['ip' => '127.0.0.1']);
    expect($entry->event?->id)->toBe($event->id);
});
