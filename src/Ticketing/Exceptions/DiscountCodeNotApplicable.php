<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Ticketing\Exceptions;

final class DiscountCodeNotApplicable extends \RuntimeException
{
    public static function code(string $reason): self
    {
        return new self($reason);
    }
}
