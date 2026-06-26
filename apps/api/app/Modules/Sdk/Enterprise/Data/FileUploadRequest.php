<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class FileUploadRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $originalFilename,
        public string $mimeType,
        public int $sizeBytes,
        public string $contents,
        public string $visibility = 'private',
        public ?EntityReference $entityReference = null,
        public ?string $displayName = null,
        public array $metadata = [],
        public ?string $uploadedMembershipPublicId = null,
    ) {
    }
}
