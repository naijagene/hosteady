<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleSyncOptions
{
    public function __construct(
        public bool $dryRun = false,
        public ?string $moduleKey = null,
    ) {
    }
}
