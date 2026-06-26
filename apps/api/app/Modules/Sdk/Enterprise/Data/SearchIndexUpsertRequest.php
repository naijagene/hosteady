<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class SearchIndexUpsertRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public EnterpriseScope $scope,
        public string $entityType,
        public string $entityPublicId,
        public string $displayName,
        public ?string $keywords = null,
        public array $metadata = [],
        public ?EntityReference $entityReference = null,
        public string $visibility = 'organization',
        public ?string $searchVector = null,
    ) {
    }
}
