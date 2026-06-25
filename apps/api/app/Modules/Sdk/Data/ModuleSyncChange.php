<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleSyncChange
{
    public function __construct(
        public string $entity,
        public string $action,
        public string $key,
        public ?string $moduleKey = null,
    ) {
    }
}
