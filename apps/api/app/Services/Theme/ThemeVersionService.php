<?php

namespace App\Services\Theme;

use App\Models\ThemeVersion;
use App\Modules\Sdk\Theme\Contracts\ThemeVersionManager;
use App\Modules\Sdk\Theme\Data\ThemeVersion as ThemeVersionDto;
use App\Modules\Sdk\Theme\Enums\ThemeVersionStatus;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class ThemeVersionService implements ThemeVersionManager
{
    public function __construct(
        private readonly ThemeRegistryService $registryService,
        private readonly ThemeTableHealthSupport $tableHealthSupport,
    ) {
    }

    /** @return list<ThemeVersionDto> */
    public function listVersions(TenantContext $context, string $themeKey, ?string $moduleKey = null): array
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_versions')
            || ! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return [];
        }

        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        return ThemeVersion::query()
            ->where('theme_definition_id', $definition->id)
            ->orderByDesc('version_number')
            ->get()
            ->map(fn (ThemeVersion $model) => ThemeMapper::toVersion($model))
            ->all();
    }

    public function findVersion(TenantContext $context, string $versionPublicId): ThemeVersionDto
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_versions')) {
            throw new \App\Modules\Sdk\Theme\Exceptions\ThemeValidationException('Theme versions table is not available.');
        }

        $query = ThemeVersion::query()->where('public_id', $versionPublicId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return ThemeMapper::toVersion($query->firstOrFail());
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function createDraft(TenantContext $context, string $themeDefinitionPublicId, array $snapshot, ?string $changeSummary = null): ThemeVersionDto
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_versions')
            || ! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            throw new \App\Modules\Sdk\Theme\Exceptions\ThemeValidationException('Theme versions table is not available.');
        }

        $definition = $this->registryService->resolveModelByPublicId(
            $context->organization->id,
            $context->workspace?->id,
            $themeDefinitionPublicId,
        );

        $nextVersion = ((int) ThemeVersion::query()->where('theme_definition_id', $definition->id)->max('version_number')) + 1;

        $created = ThemeVersion::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'theme_definition_id' => $definition->id,
            'version_number' => $nextVersion,
            'status' => ThemeVersionStatus::Draft->value,
            'snapshot_json' => $snapshot,
            'change_summary' => $changeSummary,
            'metadata' => [],
        ]);

        return ThemeMapper::toVersion($created);
    }

    public function findPublishedVersion(TenantContext $context, string $themeKey, ?string $moduleKey = null): ?ThemeVersionDto
    {
        if (! $this->tableHealthSupport->isTablePresent('theme_versions')
            || ! $this->tableHealthSupport->isTablePresent('theme_definitions')) {
            return null;
        }

        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $themeKey,
        );

        $version = ThemeVersion::query()
            ->where('theme_definition_id', $definition->id)
            ->where('status', ThemeVersionStatus::Published->value)
            ->orderByDesc('version_number')
            ->first();

        return $version !== null ? ThemeMapper::toVersion($version) : null;
    }
}
