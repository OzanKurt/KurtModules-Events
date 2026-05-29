<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class AgeMinEvaluator implements RequirementEvaluator
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

        $min = (int) ($payload['min'] ?? 0);

        return $age >= $min
            ? CheckResult::pass(['age' => $age])
            : CheckResult::fail("Minimum age is {$min}", ['age' => $age]);
    }
}
