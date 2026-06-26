<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class ReferenceCatalogData
{
    public function __construct(
        public string $key,
        public string $name,
        public int $version = 1,
        public ?string $moduleKey = null,
        public ?string $description = null,
    ) {
    }
}
