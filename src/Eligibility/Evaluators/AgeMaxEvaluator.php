<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class AgeMaxEvaluator implements RequirementEvaluator
{
    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        $profile = $attendee->getAttribute('profile') ?? [];
        $dob = is_array($profile) ? ($profile['date_of_birth'] ?? null) : null;

        if (! is_string($dob) || $dob === '') {
            return CheckResult::pending('Date of birth not provided');
        }

        try {
            $age = Carbon::parse($dob)->age;
        } catch (\Throwable) {
            return CheckResult::fail('Invalid date of birth');
        }

        $max = (int) ($payload['max'] ?? PHP_INT_MAX);

        return $age <= $max
            ? CheckResult::pass(['age' => $age])
            : CheckResult::fail("Maximum age is {$max}", ['age' => $age]);
    }
}
