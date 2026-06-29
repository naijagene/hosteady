<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationProfile;
use App\Support\Tenant\TenantContext;

class PersonalizationProfileService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<PersonalizationProfile> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_profiles')) {
            return [];
        }

        $query = PersonalizationProfile::query();
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        PersonalizationMapper::applyMembershipScope($query, $context);

        return $query->get()->all();
    }
}
