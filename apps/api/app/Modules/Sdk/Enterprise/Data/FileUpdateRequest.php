<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class FileUpdateRequest
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $filePublicId,
        public ?string $displayName = null,
        public ?string $visibility = null,
        public ?EntityReference $entityReference = null,
        public ?array $metadata = null,
    ) {
    }
}
