<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class PlatformJobResult
{
    public function __construct(
        public PlatformJobReference $job,
        public bool $queued,
    ) {
    }
}
