<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleValidationIssue
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $moduleKey = null,
    ) {
    }
}
