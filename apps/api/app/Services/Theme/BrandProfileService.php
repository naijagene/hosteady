<?php

namespace App\Services\Theme;

use App\Models\BrandProfile;
use App\Modules\Sdk\Theme\Contracts\ThemeBrandProfileProvider;
use App\Modules\Sdk\Theme\Data\BrandProfile as BrandProfileDto;
use App\Modules\Sdk\Theme\Exceptions\ThemeValidationException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class BrandProfileService implements ThemeBrandProfileProvider
{
    public function __construct(
        private readonly ThemeTableHealthSupport $tableHealthSupport,
        private readonly ThemeAuditRecorder $auditRecorder,
    ) {
    }

    public function get(TenantContext $context, string $themeDefinitionPublicId): ?BrandProfileDto
    {
        if (! $this->tableHealthSupport->isTablePresent('brand_profiles')) {
            return null;
        }

        $themeDefinitionId = ThemeMapper::resolveThemeId($themeDefinitionPublicId);
        if ($themeDefinitionId === null) {
            return null;
        }

        $query = BrandProfile::query()->where('theme_definition_id', $themeDefinitionId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        $model = $query->orderByDesc('updated_at')->first();

        return $model !== null ? ThemeMapper::toBrandProfile($model) : null;
    }

    public function update(TenantContext $context, string $themeDefinitionPublicId, array $profile): BrandProfileDto
    {
        if (! $this->tableHealthSupport->isTablePresent('brand_profiles')) {
            throw new ThemeValidationException('Brand profiles table is not available.');
        }

        if (array_key_exists('assets', $profile)) {
            $this->validateAssets(is_array($profile['assets']) ? $profile['assets'] : []);
        }

        $themeDefinitionId = ThemeMapper::resolveThemeId($themeDefinitionPublicId);

        $query = BrandProfile::query()->where('theme_definition_id', $themeDefinitionId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        /** @var BrandProfile|null $existing */
        $existing = $query->first();

        if ($existing === null) {
            $existing = BrandProfile::query()->create([
                'id' => (string) Str::uuid7(),
                'organization_id' => $context->organization->id,
                'workspace_id' => $context->workspace?->id,
                'theme_definition_id' => $themeDefinitionId,
                'name' => (string) ($profile['name'] ?? 'Brand profile'),
                'logo_url' => isset($profile['logo_url']) ? (string) $profile['logo_url'] : null,
                'colors_json' => is_array($profile['colors'] ?? null) ? $profile['colors'] : [],
                'typography_json' => is_array($profile['typography'] ?? null) ? $profile['typography'] : [],
                'assets_json' => is_array($profile['assets'] ?? null) ? $profile['assets'] : [],
                'metadata' => is_array($profile['metadata'] ?? null) ? $profile['metadata'] : [],
            ]);
        } else {
            $existing->fill([
                'name' => (string) ($profile['name'] ?? $existing->name),
                'logo_url' => isset($profile['logo_url']) ? (string) $profile['logo_url'] : $existing->logo_url,
                'colors_json' => is_array($profile['colors'] ?? null) ? $profile['colors'] : (is_array($existing->colors_json) ? $existing->colors_json : []),
                'typography_json' => is_array($profile['typography'] ?? null) ? $profile['typography'] : (is_array($existing->typography_json) ? $existing->typography_json : []),
                'assets_json' => is_array($profile['assets'] ?? null) ? $profile['assets'] : (is_array($existing->assets_json) ? $existing->assets_json : []),
                'metadata' => is_array($profile['metadata'] ?? null) ? $profile['metadata'] : (is_array($existing->metadata) ? $existing->metadata : []),
            ]);
            $existing->save();
        }

        $mapped = ThemeMapper::toBrandProfile($existing->fresh());
        $this->auditRecorder->recordBrandProfileUpdated($mapped->publicId);

        return $mapped;
    }

    /** @return list<BrandProfileDto> */
    public function list(TenantContext $context): array
    {
        if (! $this->tableHealthSupport->isTablePresent('brand_profiles')) {
            return [];
        }

        $query = BrandProfile::query()->orderBy('name');
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return $query->get()->map(fn (BrandProfile $model) => ThemeMapper::toBrandProfile($model))->all();
    }

    public function findByPublicId(TenantContext $context, string $publicId): BrandProfileDto
    {
        if (! $this->tableHealthSupport->isTablePresent('brand_profiles')) {
            throw new ThemeValidationException('Brand profiles table is not available.');
        }

        $query = BrandProfile::query()->where('public_id', $publicId);
        ThemeMapper::applyOrganizationScope($query, $context->organization->id);
        ThemeMapper::applyWorkspaceScope($query, $context->workspace?->id);

        return ThemeMapper::toBrandProfile($query->firstOrFail());
    }

    /**
     * @param  array<int|string, mixed>  $assets
     */
    private function validateAssets(array $assets): void
    {
        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                throw new ThemeValidationException('Brand asset must be an object.');
            }

            if (! isset($asset['type']) || ! is_string($asset['type']) || trim($asset['type']) === '') {
                throw new ThemeValidationException('Brand asset requires type.');
            }

            if (! array_key_exists('alt', $asset) || ! is_string($asset['alt'])) {
                throw new ThemeValidationException('Brand asset requires alt.');
            }
        }
    }
}
