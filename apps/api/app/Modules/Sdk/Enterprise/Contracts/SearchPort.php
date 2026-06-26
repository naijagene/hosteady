<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SavedSearchReference;
use App\Modules\Sdk\Enterprise\Data\SearchRequest;
use App\Modules\Sdk\Enterprise\Data\SearchResult;

interface SearchPort
{
    public function search(SearchRequest $request): SearchResult;

    /**
     * @return list<string>
     */
    public function suggestions(SearchRequest $request, int $limit = 10): array;

    /**
     * @return list<array{query: ?string, module_key: ?string, result_count: int, created_at: string}>
     */
    public function recent(EnterpriseScope $scope, string $membershipPublicId, int $limit = 10): array;

    public function saveSearch(
        EnterpriseScope $scope,
        string $membershipPublicId,
        string $name,
        ?string $query,
        ?array $filters = null,
        ?string $moduleKey = null,
    ): SavedSearchReference;

    /**
     * @return list<SavedSearchReference>
     */
    public function listSavedSearches(EnterpriseScope $scope, string $membershipPublicId): array;

    public function deleteSavedSearch(EnterpriseScope $scope, string $membershipPublicId, string $savedSearchPublicId): void;
}
