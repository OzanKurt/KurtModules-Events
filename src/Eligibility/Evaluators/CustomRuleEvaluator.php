<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class CustomRuleEvaluator implements RequirementEvaluator
{
    public function __construct(private readonly Container $container) {}

    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        $fqcn = $payload['evaluator'] ?? null;
        if (! is_string($fqcn) || $fqcn === '' || ! class_exists($fqcn)) {
            return CheckResult::fail('Invalid custom evaluator FQCN');
        }

        $impl = $this->container->make($fqcn);
        if (! $impl instanceof RequirementEvaluator) {
            return CheckResult::fail('Class does not implement RequirementEvaluator');
        }

        $config = $payload['config'] ?? [];
        if (! is_array($config)) {
            $config = [];
        }

        return $impl->evaluate($attendee, $config, $context);
    }
}
