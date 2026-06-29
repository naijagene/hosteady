<?php

namespace App\Modules\Sdk\Personalization\Contracts;

use App\Modules\Sdk\Personalization\Data\FavoriteItem;
use App\Support\Tenant\TenantContext;

interface PersonalizationFavoriteStore
{
    /** @return list<FavoriteItem> */
    public function list(TenantContext $context): array;

    public function add(TenantContext $context, string $subjectType, string $subjectPublicId, ?string $label = null): FavoriteItem;

    public function remove(TenantContext $context, string $favoritePublicId): void;
}
