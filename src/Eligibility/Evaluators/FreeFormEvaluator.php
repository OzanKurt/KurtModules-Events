<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class FreeFormEvaluator implements RequirementEvaluator
{
    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        return CheckResult::pending('Awaiting reviewer');
    }
}
