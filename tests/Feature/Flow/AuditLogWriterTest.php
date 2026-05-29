<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Illuminate\Http\Request;
use Kurt\Modules\Events\Catalog\Models\Event as CatalogEvent;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;
use Kurt\Modules\Events\Flow\Support\AuditLogWriter;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('writes a row with action, actor, subject, and request context', function () {
    config()->set('events.audit.enabled', true);
    config()->set('events.audit.capture_context', true);

    $fake = Request::create('/test', server: ['REMOTE_ADDR' => '198.51.100.42', 'HTTP_USER_AGENT' => 'TestAgent/1.0']);
    app()->instance('request', $fake);

    $actor = StubUser::create(['email' => 'actor@x.com']);
    $event = CatalogEvent::factory()->create();
    $subject = $event;

    /** @var Repository $config */
    $config = app('config');
    $writer = new AuditLogWriter($config);

    $writer->write('event.published', $subject, $actor, $event->id, ['from' => 'draft', 'to' => 'published']);

    $row = AuditLogEntry::query()->latest('id')->firstOrFail();
    expect($row->action)->toBe('event.published');
    expect($row->actor_id)->toBe($actor->id);
    expect($row->actor_type)->toBe('user');
    expect($row->subject_type)->toBe(CatalogEvent::class);
    expect($row->subject_id)->toBe($event->id);
    expect($row->event_id)->toBe($event->id);
    expect($row->changes)->toBe(['from' => 'draft', 'to' => 'published']);
    expect($row->context)->toBe(['ip' => '198.51.100.42', 'user_agent' => 'TestAgent/1.0']);
});

it('skips writing when audit.enabled is false', function () {
    config()->set('events.audit.enabled', false);

    /** @var Repository $config */
    $config = app('config');
    (new AuditLogWriter($config))->write('skipped.action');

    expect(AuditLogEntry::query()->count())->toBe(0);
});

it('marks actor_type as system when no actor is supplied', function () {
    config()->set('events.audit.enabled', true);
    config()->set('events.audit.capture_context', false);

    /** @var Repository $config */
    $config = app('config');
    (new AuditLogWriter($config))->write('system.action');

    $row = AuditLogEntry::query()->latest('id')->firstOrFail();
    expect($row->actor_id)->toBeNull();
    expect($row->actor_type)->toBe('system');
    expect($row->context)->toBeNull();
});

it('keeps context null when audit.capture_context is false even with a request bound', function () {
    config()->set('events.audit.enabled', true);
    config()->set('events.audit.capture_context', false);

    $fake = Request::create('/x', server: ['REMOTE_ADDR' => '127.0.0.1']);
    app()->instance('request', $fake);

    /** @var Repository $config */
    $config = app('config');
    (new AuditLogWriter($config))->write('no.context');

    expect(AuditLogEntry::query()->latest('id')->firstOrFail()->context)->toBeNull();
});
