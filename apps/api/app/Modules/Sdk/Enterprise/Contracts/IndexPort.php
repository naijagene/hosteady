<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\SearchIndexReference;
use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;

interface IndexPort
{
    public function upsert(SearchIndexUpsertRequest $request): SearchIndexReference;

    public function remove(EnterpriseScope $scope, string $entityType, string $entityPublicId, ?string $moduleKey = null): void;

    public function registerModule(string $moduleKey, array $entityTypes): void;

    /**
     * @return list<string>
     */
    public function supportedModules(): array;
}
