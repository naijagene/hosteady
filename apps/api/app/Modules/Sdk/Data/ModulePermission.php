<?php

namespace App\Modules\Sdk\Data;

readonly class ModulePermission
{
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
    ) {
    }
}
