<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Evaluators;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\GroupResolver;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class GroupMembershipEvaluator implements RequirementEvaluator
{
    public function __construct(private readonly Container $container) {}

    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        $resolverClass = config('events.requirements.group_resolver');
        if (! is_string($resolverClass) || $resolverClass === '') {
            return CheckResult::pending('No group resolver configured');
        }

        $resolver = $this->container->make($resolverClass);
        if (! $resolver instanceof GroupResolver) {
            return CheckResult::fail('Configured group resolver does not implement GroupResolver');
        }

        $user = $attendee->getAttribute('user');
        if (! $user instanceof Model) {
            return CheckResult::pending('Attendee has no associated user');
        }

        $userGroups = $resolver->groupsFor($user);

        $required = $payload['group'] ?? [];
        $requiredList = is_array($required) ? $required : [$required];
        /** @var array<int, string> $normalized */
        $normalized = array_values(array_filter($requiredList, 'is_string'));

        $matched = array_values(array_intersect($userGroups, $normalized));

        return $matched !== []
            ? CheckResult::pass(['matched_groups' => $matched])
            : CheckResult::fail('Not a member of required group');
    }
}
