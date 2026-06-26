<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class ReferenceItemData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $catalogKey,
        public string $code,
        public string $label,
        public array $metadata = [],
        public int $sortOrder = 0,
        public bool $active = true,
    ) {
    }
}
