<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Attendance\Support\AnnouncementDispatcher;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Support\EventCloner;
use Kurt\Modules\Events\Catalog\Support\IcsExporter;
use Kurt\Modules\Events\Catalog\Support\RecurrenceExpander;
use Kurt\Modules\Events\Catalog\Support\TemplateManager;
use Kurt\Modules\Events\Console\Commands\DemoCommand;
use Kurt\Modules\Events\Console\Commands\DispatchAnnouncementsCommand;
use Kurt\Modules\Events\Console\Commands\DispatchRemindersCommand;
use Kurt\Modules\Events\Console\Commands\EnforceRetentionCommand;
use Kurt\Modules\Events\Console\Commands\ExpirePendingOrdersCommand;
use Kurt\Modules\Events\Console\Commands\ExpireWaitlistClaimsCommand;
use Kurt\Modules\Events\Console\Commands\GenerateOccurrencesCommand;
use Kurt\Modules\Events\Console\Commands\PruneQueueCommand;
use Kurt\Modules\Events\Console\Commands\ReleaseQueueCommand;
use Kurt\Modules\Events\Eligibility\Engine\RequirementEngine;
use Kurt\Modules\Events\Flow\Models\Refund;
use Kurt\Modules\Events\Flow\Support\AuditLogWriter;
use Kurt\Modules\Events\Flow\Support\GdprAnonymizer;
use Kurt\Modules\Events\Flow\Support\GdprExporter;
use Kurt\Modules\Events\Flow\Support\PayoutAccruer;
use Kurt\Modules\Events\Flow\Support\QueuePruner;
use Kurt\Modules\Events\Flow\Support\QueueReleaser;
use Kurt\Modules\Events\Flow\Support\RefundCoordinator;
use Kurt\Modules\Events\Flow\Support\SponsorCoordinator;
use Kurt\Modules\Events\Flow\Support\WaitlistPromoter;
use Kurt\Modules\Events\Policies\ApplicationPolicy;
use Kurt\Modules\Events\Policies\EventPolicy;
use Kurt\Modules\Events\Policies\OrderPolicy;
use Kurt\Modules\Events\Policies\RefundPolicy;
use Kurt\Modules\Events\Policies\TicketTypePolicy;
use Kurt\Modules\Events\Support\Events as EventsService;
use Kurt\Modules\Events\Ticketing\Models\Order;
use Kurt\Modules\Events\Ticketing\Models\Ticket;
use Kurt\Modules\Events\Ticketing\Models\TicketType;
use Kurt\Modules\Events\Ticketing\Observers\OrderObserver;
use Kurt\Modules\Events\Ticketing\Observers\TicketObserver;
use Kurt\Modules\Events\Ticketing\Support\QrTokenSigner;
use Spatie\LaravelPackageTools\Package;

final class EventsServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'events';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-events')
            ->hasConfigFile('events')
            ->hasTranslations()
            ->hasViews('events')
            ->hasMigrations([
                '2026_05_29_000010_create_events_categories_table',
                '2026_05_29_000020_create_events_tags_table',
                '2026_05_29_000030_create_events_events_table',
                '2026_05_29_000040_create_events_event_tag_table',
                '2026_05_29_000050_create_events_event_organizers_table',
                '2026_05_29_000055_create_events_event_templates_table',
                '2026_05_29_000060_create_events_sessions_table',
                '2026_05_29_000070_create_events_attendance_forms_table',
                '2026_05_29_000080_create_events_ticket_types_table',
                '2026_05_29_000085_create_events_ticket_type_session_table',
                '2026_05_29_000090_create_events_price_tiers_table',
                '2026_05_29_000100_create_events_ticket_add_ons_table',
                '2026_05_29_000105_create_events_sponsor_tiers_table',
                '2026_05_29_000110_create_events_referral_links_table',
                '2026_05_29_000115_create_events_discount_codes_table',
                '2026_05_29_000118_create_events_discount_code_event_table',
                '2026_05_29_000120_create_events_orders_table',
                '2026_05_29_000130_create_events_order_items_table',
                '2026_05_29_000135_create_events_order_item_assignments_table',
                '2026_05_29_000140_create_events_tickets_table',
                '2026_05_29_000145_create_events_session_check_ins_table',
                '2026_05_29_000150_create_events_ticket_add_on_purchases_table',
                '2026_05_29_000160_create_events_applications_table',
                '2026_05_29_000170_create_events_attendees_table',
                '2026_05_29_000175_create_events_attendance_responses_table',
                '2026_05_29_000180_create_events_announcements_table',
                '2026_05_29_000185_create_events_announcement_recipients_table',
                '2026_05_29_000190_create_events_requirements_table',
                '2026_05_29_000200_create_events_requirement_checks_table',
                '2026_05_29_000210_create_events_document_uploads_table',
                '2026_05_29_000220_create_events_document_verifications_table',
                '2026_05_29_000230_create_events_discount_code_usages_table',
                '2026_05_29_000240_create_events_sponsors_table',
                '2026_05_29_000245_create_events_sponsor_comp_tickets_table',
                '2026_05_29_000250_create_events_sale_queue_entries_table',
                '2026_05_29_000260_create_events_waitlist_entries_table',
                '2026_05_29_000270_create_events_refunds_table',
                '2026_05_29_000280_create_events_payout_ledger_table',
                '2026_05_29_000290_create_events_check_in_attempts_table',
                '2026_05_29_000300_create_events_audit_log_table',
            ])
            ->hasCommands([
                ReleaseQueueCommand::class,
                PruneQueueCommand::class,
                ExpireWaitlistClaimsCommand::class,
                GenerateOccurrencesCommand::class,
                DispatchRemindersCommand::class,
                ExpirePendingOrdersCommand::class,
                DispatchAnnouncementsCommand::class,
                EnforceRetentionCommand::class,
                DemoCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(QrTokenSigner::class, fn () => new QrTokenSigner((string) config('app.key')));
        $this->app->singleton(RequirementEngine::class);
        $this->app->scoped(QueueReleaser::class);
        $this->app->scoped(QueuePruner::class);
        $this->app->scoped(WaitlistPromoter::class);
        $this->app->scoped(RefundCoordinator::class);
        $this->app->scoped(PayoutAccruer::class);
        $this->app->scoped(AuditLogWriter::class);
        $this->app->scoped(SponsorCoordinator::class);
        $this->app->scoped(EventCloner::class);
        $this->app->scoped(TemplateManager::class);
        $this->app->scoped(IcsExporter::class);
        $this->app->scoped(RecurrenceExpander::class);
        $this->app->scoped(GdprExporter::class);
        $this->app->scoped(GdprAnonymizer::class);
        $this->app->scoped(AnnouncementDispatcher::class);

        $this->app->singleton(EventsService::class);
    }

    public function packageBooted(): void
    {
        // Observers: only register what exists in v1. EventObserver is not implemented;
        // event domain events fire from the Support\Events facade explicitly.
        Order::observe(OrderObserver::class);
        Ticket::observe(TicketObserver::class);

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Event::class, EventPolicy::class);
        $gate->policy(TicketType::class, TicketTypePolicy::class);
        $gate->policy(Order::class, OrderPolicy::class);
        $gate->policy(Application::class, ApplicationPolicy::class);
        $gate->policy(Refund::class, RefundPolicy::class);

        if ($this->app->runningInConsole() && (bool) config('events.scheduler.enabled', true)) {
            $this->app->booted(function () {
                /** @var Schedule $schedule */
                $schedule = $this->app->make(Schedule::class);
                $schedule->command(ReleaseQueueCommand::class)->everyTenSeconds();
                $schedule->command(PruneQueueCommand::class)->everyMinute();
                $schedule->command(ExpireWaitlistClaimsCommand::class)->everyMinute();
                $schedule->command(ExpirePendingOrdersCommand::class)->everyMinute();
                $schedule->command(DispatchAnnouncementsCommand::class)->everyMinute();
                $schedule->command(DispatchRemindersCommand::class)->everyFiveMinutes();
                $schedule->command(GenerateOccurrencesCommand::class)->daily();
                $schedule->command(EnforceRetentionCommand::class)->daily();
            });
        }
    }
}
