<?php

namespace App\Services\Navigation;

use App\Models\NavigationPersonalization as NavigationPersonalizationModel;
use App\Modules\Sdk\Navigation\Contracts\NavigationPersonalizationProvider;
use App\Modules\Sdk\Navigation\Data\NavigationPersonalization;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class NavigationPersonalizationService implements NavigationPersonalizationProvider
{
    public function __construct(
        private readonly NavigationAuditRecorder $auditRecorder,
    ) {
    }

    public function get(TenantContext $context, string $navigationDefinitionPublicId): NavigationPersonalization
    {
        $model = $this->resolveModel($context, $navigationDefinitionPublicId);

        if ($model === null) {
            return new NavigationPersonalization(
                publicId: '',
                navigationDefinitionPublicId: $navigationDefinitionPublicId,
                membershipPublicId: $context->membershipPublicId,
                personalization: [],
                metadata: [],
            );
        }

        return NavigationMapper::toPersonalization($model);
    }

    public function update(TenantContext $context, string $navigationDefinitionPublicId, array $personalization): NavigationPersonalization
    {
        $model = $this->resolveModel($context, $navigationDefinitionPublicId);

        if ($model === null) {
            $model = NavigationPersonalizationModel::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'membership_id' => $context->membership->id,
                'navigation_definition_id' => NavigationMapper::resolveDefinitionId($navigationDefinitionPublicId),
                'personalization_json' => $personalization,
                'metadata' => [],
            ]);
        } else {
            $model->fill(['personalization_json' => $personalization])->save();
        }

        $updated = NavigationMapper::toPersonalization($model->fresh());
        $this->auditRecorder->recordPersonalizationUpdated($updated, $context);

        return $updated;
    }

    private function resolveModel(TenantContext $context, string $navigationDefinitionPublicId): ?NavigationPersonalizationModel
    {
        $query = NavigationPersonalizationModel::query()
            ->where('membership_id', $context->membership->id);

        $definitionId = NavigationMapper::resolveDefinitionId($navigationDefinitionPublicId);

        if ($definitionId !== null) {
            $query->where('navigation_definition_id', $definitionId);
        }

        NavigationMapper::applyOrganizationScope($query, $context->organization->id);
        NavigationMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->first();
    }
}
