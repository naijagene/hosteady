<?php

namespace App\Services\Personalization;

use App\Models\PersonalizationProfile;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class PersonalizationRegistryService
{
    public function __construct(
        private readonly PersonalizationTableHealthSupport $tableHealthSupport,
        private readonly PersonalizationAuditRecorder $auditRecorder,
        private readonly PersonalizationSearchIndexer $searchIndexer,
    ) {
    }

    public function ensureProfile(TenantContext $context, string $scope = 'membership', string $profileKey = 'default'): ?PersonalizationProfile
    {
        if (! $this->tableHealthSupport->isTablePresent('personalization_profiles')) {
            return null;
        }

        $query = PersonalizationProfile::query()
            ->where('scope', $scope)
            ->where('name', $profileKey);
        PersonalizationMapper::applyOrganizationScope($query, $context->organization->id);
        PersonalizationMapper::applyWorkspaceScope($query, $context->workspace?->id);
        PersonalizationMapper::applyMembershipScope($query, $context);

        /** @var PersonalizationProfile|null $existing */
        $existing = $query->first();

        if ($existing !== null) {
            return $existing;
        }

        $created = PersonalizationProfile::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'membership_id' => $context->membership->id,
            'user_id' => $context->user->id,
            'scope' => $scope,
            'name' => $profileKey,
            'is_default' => true,
            'metadata' => [],
        ]);

        $this->auditRecorder->recordProfileCreated($created->public_id);
        $this->searchIndexer->indexProfileBestEffort($created->public_id, $context->organization->id, $context->workspace?->id);

        return $created;
    }
}
