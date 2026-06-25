<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleHealthReport
{
    public function __construct(
        public string $status,
        public ?string $message = null,
    ) {
    }

    public static function healthy(?string $message = null): self
    {
        return new self('healthy', $message);
    }
}
