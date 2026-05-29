<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Events\Eligibility\Contracts\GroupResolver;

final class StubGroupResolver implements GroupResolver
{
    /** @var array<int, string> */
    public static array $groups = [];

    public function groupsFor(Model $user): array
    {
        return self::$groups;
    }
}
