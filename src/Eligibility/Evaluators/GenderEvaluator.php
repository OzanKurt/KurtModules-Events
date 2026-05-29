<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class GenderEvaluator implements RequirementEvaluator
{
    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        $profile = $attendee->getAttribute('profile') ?? [];
        $gender = is_array($profile) ? ($profile['gender'] ?? null) : null;

        if (! is_string($gender) || $gender === '') {
            return CheckResult::pending('Gender not provided');
        }

        $allowed = $payload['allowed'] ?? [];
        $allowedList = is_array($allowed) ? $allowed : [$allowed];

        $normalized = array_values(array_filter($allowedList, 'is_string'));

        return in_array($gender, $normalized, true)
            ? CheckResult::pass(['gender' => $gender])
            : CheckResult::fail('Gender not permitted', ['gender' => $gender]);
    }
}
