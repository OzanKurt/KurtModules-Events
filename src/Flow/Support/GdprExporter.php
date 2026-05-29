<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Flow\Support;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;
use Kurt\Modules\Events\Flow\Models\AuditLogEntry;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Flow\Models\SaleQueueEntry;
use Kurt\Modules\Events\Flow\Models\WaitlistEntry;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\OrderItemAssignment;
use Kurt\Modules\Events\Ticketing\Models\Ticket;

final class GdprExporter
{
    /** @return array<string, mixed> */
    public function export(Model $user): array
    {
        $userId = $user->getKey();

        return [
            'user_id' => $userId,
            'attendees' => Attendee::query()->where('user_id', $userId)->get()->toArray(),
            'applications' => Application::query()->where('applicant_id', $userId)->get()->toArray(),
            'orders' => Order::query()->where('user_id', $userId)->get()->toArray(),
            'order_item_assignments' => OrderItemAssignment::query()->where('holder_user_id', $userId)->get()->toArray(),
            'tickets' => Ticket::query()->where('holder_id', $userId)->get()->toArray(),
            'refunds_as_requester' => Refund::query()->where('requested_by', $userId)->get()->toArray(),
            'document_uploads' => DocumentUpload::query()
                ->whereHas('attendee', fn ($q) => $q->where('user_id', $userId))
                ->get()->toArray(),
            'audit_log_as_actor' => AuditLogEntry::query()->where('actor_id', $userId)->get()->toArray(),
            'sale_queue_entries' => SaleQueueEntry::query()->where('user_id', $userId)->get()->toArray(),
            'waitlist_entries' => WaitlistEntry::query()->where('user_id', $userId)->get()->toArray(),
        ];
    }
}
