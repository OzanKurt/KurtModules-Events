<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Application;
use Kurt\Modules\Events\Catalog\Enums\OrganizerRole;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Catalog\Models\EventOrganizer;
use Kurt\Modules\Events\Policies\ApplicationPolicy;
use Kurt\Modules\Events\Tests\Stubs\StubUser;
use Kurt\Modules\Events\Ticketing\Models\TicketType;

function applicationPolicy(): ApplicationPolicy
{
    return new ApplicationPolicy;
}

it('allows the applicant to view + withdraw', function () {
    $applicant = StubUser::create(['email' => 'a@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $application = Application::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'applicant_id' => $applicant->id,
    ]);

    expect(applicationPolicy()->view($applicant, $application))->toBeTrue();
    expect(applicationPolicy()->withdraw($applicant, $application))->toBeTrue();
});

it('denies a stranger from viewing or withdrawing', function () {
    $applicant = StubUser::create(['email' => 'a@example.com']);
    $other = StubUser::create(['email' => 'o@example.com']);
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $application = Application::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'applicant_id' => $applicant->id,
    ]);

    expect(applicationPolicy()->view($other, $application))->toBeFalse();
    expect(applicationPolicy()->withdraw($other, $application))->toBeFalse();
});

it('allows organizer manager to approve and reject', function () {
    $manager = StubUser::create(['email' => 'mgr@example.com']);
    $applicant = StubUser::create(['email' => 'a@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $manager->id, 'role' => OrganizerRole::Manager]);
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $application = Application::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'applicant_id' => $applicant->id,
    ]);

    expect(applicationPolicy()->approve($manager, $application))->toBeTrue();
    expect(applicationPolicy()->reject($manager, $application))->toBeTrue();
});

it('denies scanner from approving', function () {
    $scanner = StubUser::create(['email' => 'scan@example.com']);
    $applicant = StubUser::create(['email' => 'a@example.com']);
    $event = Event::factory()->create();
    EventOrganizer::create(['event_id' => $event->id, 'user_id' => $scanner->id, 'role' => OrganizerRole::Scanner]);
    $type = TicketType::factory()->create(['event_id' => $event->id]);
    $application = Application::factory()->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'applicant_id' => $applicant->id,
    ]);

    expect(applicationPolicy()->approve($scanner, $application))->toBeFalse();
});
