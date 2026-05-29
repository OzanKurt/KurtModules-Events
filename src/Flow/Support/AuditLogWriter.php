<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;

final class AuditLogWriter
{
    public function __construct(private readonly Repository $config) {}

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function write(
        string $action,
        ?Model $subject = null,
        ?Model $actor = null,
        ?int $eventId = null,
        ?array $changes = null,
    ): void {
        if (! (bool) $this->config->get('events.audit.enabled', true)) {
            return;
        }

        AuditLogEntry::create([
            'event_id' => $eventId,
            'actor_id' => $actor?->getKey(),
            'actor_type' => $actor !== null ? 'user' : 'system',
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'changes' => $changes,
            'context' => $this->captureContext(),
            'occurred_at' => now(),
        ]);
    }

    /** @return array<string, string|null>|null */
    private function captureContext(): ?array
    {
        if (! (bool) $this->config->get('events.audit.capture_context', true)) {
            return null;
        }

        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return [
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ];
    }
}
