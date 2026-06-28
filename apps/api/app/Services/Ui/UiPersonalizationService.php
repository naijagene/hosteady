<?php

namespace App\Services\Ui;

use App\Models\UiPersonalization as UiPersonalizationModel;
use App\Modules\Sdk\Ui\Contracts\UiPersonalizationProvider;
use App\Modules\Sdk\Ui\Data\UiPersonalization;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class UiPersonalizationService implements UiPersonalizationProvider
{
    public function __construct(
        private readonly UiAuditRecorder $auditRecorder,
    ) {
    }

    public function get(TenantContext $context, string $pagePublicId): UiPersonalization
    {
        $model = $this->resolveModel($context, $pagePublicId);

        if ($model === null) {
            return new UiPersonalization(
                publicId: '',
                pagePublicId: $pagePublicId,
                membershipPublicId: $context->membershipPublicId,
                personalization: [],
                metadata: [],
            );
        }

        return UiMapper::toPersonalization($model);
    }

    public function update(TenantContext $context, string $pagePublicId, array $personalization): UiPersonalization
    {
        $model = $this->resolveModel($context, $pagePublicId);

        if ($model === null) {
            $model = UiPersonalizationModel::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'membership_id' => $context->membership->id,
                'application_id' => null,
                'page_public_id' => $pagePublicId,
                'personalization_json' => $personalization,
                'metadata' => [],
            ]);
        } else {
            $model->fill(['personalization_json' => $personalization])->save();
        }

        $updated = UiMapper::toPersonalization($model->fresh());
        $this->auditRecorder->recordPersonalizationUpdated($updated, $context);

        return $updated;
    }

    private function resolveModel(TenantContext $context, string $pagePublicId): ?UiPersonalizationModel
    {
        $query = UiPersonalizationModel::query()
            ->where('page_public_id', $pagePublicId)
            ->where('membership_id', $context->membership->id);

        UiMapper::applyOrganizationScope($query, $context->organization->id);
        UiMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->first();
    }
}
