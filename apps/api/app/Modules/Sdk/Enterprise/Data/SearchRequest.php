<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class SearchRequest
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public EnterpriseScope $scope,
        public ?string $query = null,
        public ?string $moduleKey = null,
        public ?string $entityType = null,
        public ?string $membershipPublicId = null,
        public array $filters = [],
        public int $limit = 25,
    ) {
    }
}
