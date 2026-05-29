<?php

declare(strict_types=1);

namespace Kurt\Modules\Events\Eligibility\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Events\Eligibility\Models\DocumentUpload;

final class DocumentUploaded
{
    use Dispatchable;

    public function __construct(public readonly DocumentUpload $upload) {}
}
