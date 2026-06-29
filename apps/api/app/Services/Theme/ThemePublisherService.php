<?php

namespace App\Services\Theme;

use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Contracts\ThemePublisher;
use App\Modules\Sdk\Theme\Data\ThemeDefinition as ThemeDefinitionDto;
use App\Modules\Sdk\Theme\Enums\ThemeDefinitionStatus;
use App\Modules\Sdk\Theme\Enums\ThemeVersionStatus;
use App\Modules\Sdk\Theme\Exceptions\ThemePublishException;
use App\Support\Tenant\TenantContext;

class ThemePublisherService implements ThemePublisher
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeVersionService $versionService,
        private readonly BrandProfileService $brandProfileService,
        private readonly ThemeAuditRecorder $auditRecorder,
    ) {
    }

    public function publish(TenantContext $context, string $themeKey, ?string $versionPublicId = null, ?string $moduleKey = null): ThemeDefinitionDto
    {
        $definitionModel = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        $version = $versionPublicId !== null && $versionPublicId !== ''
            ? $this->versionService->findVersion($context, $versionPublicId)
            : $this->versionService->createDraft($context, $definitionModel->public_id, [
                'tokens' => is_array($definitionModel->tokens_json) ? $definitionModel->tokens_json : [],
                'brand_profile' => $this->brandProfileService->get($context, $definitionModel->public_id)?->toArray() ?? [],
                'theme_key' => $definitionModel->theme_key,
                'module_key' => $definitionModel->module_key,
            ], 'Auto snapshot publish');

        $versionId = ThemeMapper::resolveVersionId($version->publicId);
        if ($versionId === null) {
            throw new ThemePublishException('Unable to resolve theme version for publish.');
        }

        ThemeVersion::query()
            ->where('id', $versionId)
            ->update([
                'status' => ThemeVersionStatus::Published->value,
                'published_at' => now(),
                'published_by_user_id' => $context->user->id,
                'published_by_membership_id' => $context->membership->id,
            ]);

        $definitionModel->fill([
            'status' => ThemeDefinitionStatus::Published->value,
            'current_version_id' => $versionId,
        ]);
        $definitionModel->save();

        $published = ThemeMapper::toDefinition($definitionModel->fresh());
        $this->auditRecorder->recordPublished($published);

        return $published;
    }
}
