<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class PlatformEventResult
{
    public function __construct(
        public string $eventPublicId,
        public string $status,
        public bool $async,
    ) {
    }
}
