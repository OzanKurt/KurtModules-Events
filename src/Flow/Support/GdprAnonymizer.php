<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

final class GdprAnonymizer
{
    public function __construct(private readonly Repository $config) {}

    public function anonymize(Model $user): void
    {
        $userId = $user->getKey();
        $hash = static fn (?string $v): ?string => $v === null || $v === '' ? null : 'gdpr-'.substr(hash('sha256', $v), 0, 16);
        $anonymizedAt = ['anonymized_at' => now()->toIso8601String()];

        DB::transaction(function () use ($userId, $hash, $anonymizedAt) {
            Ticket::query()->where('holder_id', $userId)->update([
                'holder_name' => $hash('holder_name'),
                'holder_email' => $hash('holder_email'),
                'metadata' => json_encode($anonymizedAt),
            ]);

            OrderItemAssignment::query()->where('holder_user_id', $userId)->update([
                'holder_name' => $hash('holder_name'),
                'holder_email' => $hash('holder_email'),
                'holder_metadata' => json_encode($anonymizedAt),
            ]);

            Attendee::query()->where('user_id', $userId)->update([
                'profile' => json_encode($anonymizedAt),
            ]);

            if ((bool) $this->config->get('events.gdpr.anonymize_audit_log_actor', true)) {
                AuditLogEntry::query()->where('actor_id', $userId)->update(['actor_id' => null]);
            }
        });
    }
}
