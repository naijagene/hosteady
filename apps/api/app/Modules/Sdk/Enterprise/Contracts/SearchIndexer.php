<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\SearchIndexUpsertRequest;

interface SearchIndexer
{
    public function index(SearchIndexUpsertRequest $request): void;

    public function remove(string $entityType, string $entityPublicId): void;
}
