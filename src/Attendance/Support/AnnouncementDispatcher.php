<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Attendance\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementAudience;
use Kurt\Modules\Events\Attendance\Enums\AnnouncementRecipientStatus;
use Kurt\Modules\Events\Attendance\Enums\AttendeeStatus;
use Kurt\Modules\Events\Attendance\Events\AnnouncementSent;
use Kurt\Modules\Events\Attendance\Models\Announcement;
use Kurt\Modules\Events\Attendance\Models\AnnouncementRecipient;
use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Notifications\EventAnnouncementPosted;

final class AnnouncementDispatcher
{
    public function __construct(private readonly Repository $config) {}

    public function dispatch(Announcement $announcement): int
    {
        $attendees = $this->audienceFor($announcement);
        $count = 0;

        foreach ($attendees as $attendee) {
            $recipient = AnnouncementRecipient::firstOrCreate(
                ['announcement_id' => $announcement->id, 'attendee_id' => $attendee->id],
                ['status' => AnnouncementRecipientStatus::Pending],
            );

            if ((bool) $this->config->get('events.notifications.enabled', false)) {
                $user = $attendee->user()->first();
                if ($user !== null) {
                    Notification::send($user, new EventAnnouncementPosted($announcement, $recipient));
                }
            }

            $recipient->forceFill([
                'status' => AnnouncementRecipientStatus::Sent,
                'sent_at' => now(),
            ])->save();

            $count++;
        }

        $announcement->forceFill([
            'sent_at' => now(),
            'recipient_count' => $count,
        ])->save();

        AnnouncementSent::dispatch($announcement);

        return $count;
    }

    /** @return Collection<int, Attendee> */
    private function audienceFor(Announcement $a): Collection
    {
        $query = Attendee::query()->where('event_id', $a->event_id);

        return match ($a->audience) {
            AnnouncementAudience::All => $query->get(),
            AnnouncementAudience::Registered => $query->where('status', AttendeeStatus::Registered->value)->get(),
            AnnouncementAudience::CheckedIn => $query->where('status', AttendeeStatus::CheckedIn->value)->get(),
            AnnouncementAudience::ByTicketType => $query->whereHas(
                'ticket',
                fn ($q) => $q->whereIn('ticket_type_id', (array) ($a->audience_filter['ticket_type_ids'] ?? []))
            )->get(),
            AnnouncementAudience::BySession => $query->whereHas(
                'checkIns',
                fn ($q) => $q->whereIn('session_id', (array) ($a->audience_filter['session_ids'] ?? []))
            )->get(),
        };
    }
}
