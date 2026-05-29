<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;
use Kurt\Modules\Events\Eligibility\Enums\VerificationStatus;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;

final class DocumentEvaluator implements RequirementEvaluator
{
    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        $requirementId = $context['requirement_id'] ?? null;
        if (! is_int($requirementId) && ! (is_string($requirementId) && $requirementId !== '')) {
            return CheckResult::pending('Awaiting upload');
        }

        $upload = DocumentUpload::query()
            ->where('attendee_id', $attendee->getKey())
            ->where('requirement_id', $requirementId)
            ->latest('id')
            ->first();

        if ($upload === null) {
            return CheckResult::pending('No document uploaded');
        }

        $verification = $upload->verifications()->latest('id')->first();
        if ($verification === null || $verification->status === VerificationStatus::Pending) {
            return CheckResult::pending('Awaiting review');
        }

        return $verification->status === VerificationStatus::Verified
            ? CheckResult::pass(['document_upload_id' => $upload->id])
            : CheckResult::fail('Document rejected', ['document_upload_id' => $upload->id]);
    }
}
