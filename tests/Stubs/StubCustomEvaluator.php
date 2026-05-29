<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\RequirementEvaluator;
use Kurt\Modules\Events\Eligibility\Engine\CheckResult;

final class StubCustomEvaluator implements RequirementEvaluator
{
    /** @var array<string, mixed>|null */
    public static ?array $lastPayload = null;

    /** @var array<string, mixed>|null */
    public static ?array $lastContext = null;

    public function evaluate(Model $attendee, array $payload, array $context = []): CheckResult
    {
        self::$lastPayload = $payload;
        self::$lastContext = $context;

        return CheckResult::pass(['echo' => $payload]);
    }
}
