<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleSyncError
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $moduleKey = null,
    ) {
    }
}
