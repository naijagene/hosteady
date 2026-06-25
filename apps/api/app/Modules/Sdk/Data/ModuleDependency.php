<?php

namespace App\Modules\Sdk\Data;

readonly class ModuleDependency
{
    public function __construct(
        public string $key,
        public ?string $versionRange = null,
    ) {
    }
}
