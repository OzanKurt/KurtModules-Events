<?php

declare(strict_types=1);

use Kurt\Modules\Events\Attendance\Models\Attendee;
use Kurt\Modules\Events\Catalog\Models\Event;
use Kurt\Modules\Events\Eligibility\Enums\CheckStatus;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Evaluators\DocumentEvaluator;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;
use Kurt\Modules\Events\Eligibility\Models\DocumentVerification;
use Kurt\Modules\Events\Eligibility\Models\Requirement;
use Kurt\Modules\Events\Tests\Stubs\StubUser;

it('returns pending when no requirement_id is passed', function () {
    $user = StubUser::create(['email' => 'doc-no-req@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);

    $result = (new DocumentEvaluator)->evaluate($attendee, []);

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('Awaiting upload');
});

it('returns pending when no upload exists', function () {
    $user = StubUser::create(['email' => 'doc-no-upload@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);
    $requirement = Requirement::factory()->create(['event_id' => $event->id]);

    $result = (new DocumentEvaluator)->evaluate(
        $attendee,
        [],
        ['requirement_id' => $requirement->id],
    );

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('No document uploaded');
});

it('returns pending when verification absent or pending', function () {
    $user = StubUser::create(['email' => 'doc-pending@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);
    $requirement = Requirement::factory()->create(['event_id' => $event->id]);
    DocumentUpload::factory()->create([
        'attendee_id' => $attendee->id,
        'requirement_id' => $requirement->id,
    ]);

    $result = (new DocumentEvaluator)->evaluate(
        $attendee,
        [],
        ['requirement_id' => $requirement->id],
    );

    expect($result->status)->toBe(CheckStatus::Pending);
    expect($result->message)->toBe('Awaiting review');
});

it('passes when latest verification is verified', function () {
    $user = StubUser::create(['email' => 'doc-passed@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);
    $requirement = Requirement::factory()->create(['event_id' => $event->id]);
    $upload = DocumentUpload::factory()->create([
        'attendee_id' => $attendee->id,
        'requirement_id' => $requirement->id,
    ]);
    DocumentVerification::factory()->create([
        'document_upload_id' => $upload->id,
        'status' => VerificationStatus::Verified,
    ]);

    $result = (new DocumentEvaluator)->evaluate(
        $attendee,
        [],
        ['requirement_id' => $requirement->id],
    );

    expect($result->status)->toBe(CheckStatus::Passed);
    expect($result->data['document_upload_id'])->toBe($upload->id);
});

it('fails when latest verification is rejected', function () {
    $user = StubUser::create(['email' => 'doc-rejected@example.com']);
    $event = Event::factory()->create();
    $attendee = Attendee::factory()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
    ]);
    $requirement = Requirement::factory()->create(['event_id' => $event->id]);
    $upload = DocumentUpload::factory()->create([
        'attendee_id' => $attendee->id,
        'requirement_id' => $requirement->id,
    ]);
    DocumentVerification::factory()->create([
        'document_upload_id' => $upload->id,
        'status' => VerificationStatus::Rejected,
    ]);

    $result = (new DocumentEvaluator)->evaluate(
        $attendee,
        [],
        ['requirement_id' => $requirement->id],
    );

    expect($result->status)->toBe(CheckStatus::Failed);
    expect($result->message)->toBe('Document rejected');
    expect($result->data['document_upload_id'])->toBe($upload->id);
});
