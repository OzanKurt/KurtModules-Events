<?php

declare(strict_types=1);

use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;
use Kurt\Modules\Events\Flow\Support\AuditLogWriter;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

beforeEach(function () {
    config()->set('kurtmodules.user_model', StubUser::class);
});

it('writes audit log entries when AuditLogWriter is invoked', function () {
    $writer = app(AuditLogWriter::class);
    $event = Event::factory()->create();
    $actor = StubUser::create(['email' => 'actor@x.com']);
    $writer->write(
        'event.published',
        subject: $event,
        actor: $actor,
        eventId: $event->id,
        changes: ['status' => ['before' => 'draft', 'after' => 'published']],
    );

    $entries = AuditLogEntry::query()->where('action', 'event.published')->get();
    expect($entries)->toHaveCount(1);
    expect($entries->first()->subject_id)->toBe($event->id);
    expect($entries->first()->actor_id)->toBe($actor->id);
});

it('skips writing when audit.enabled is false', function () {
    config()->set('events.audit.enabled', false);
    $writer = app(AuditLogWriter::class);
    $writer->write('event.published');
    expect(AuditLogEntry::count())->toBe(0);
});

it('persists changes payload as array and includes subject_type', function () {
    $writer = app(AuditLogWriter::class);
    $event = Event::factory()->create();

    $writer->write(
        'event.cancelled',
        subject: $event,
        eventId: $event->id,
        changes: ['reason' => 'force majeure'],
    );

    $entry = AuditLogEntry::query()->where('action', 'event.cancelled')->firstOrFail();
    expect($entry->changes)->toBe(['reason' => 'force majeure']);
    expect($entry->subject_type)->toBe(Event::class);
    expect($entry->actor_type)->toBe('system');
});
