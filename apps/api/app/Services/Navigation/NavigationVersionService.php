<?php

namespace App\Services\Navigation;

use App\Models\NavigationVersion;
use App\Modules\Sdk\Navigation\Contracts\NavigationVersionManager;
use App\Modules\Sdk\Navigation\Data\NavigationVersion as NavigationVersionDto;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Support\Tenant\TenantContext;

class NavigationVersionService implements NavigationVersionManager
{
    public function __construct(
        private readonly NavigationRegistryService $registryService,
    ) {
    }

    /** @return list<NavigationVersionDto> */
    public function listVersions(TenantContext $context, string $navigationKey, ?string $moduleKey = null): array
    {
        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $navigationKey,
        );

        return $definition->versions()
            ->orderByDesc('version_number')
            ->get()
            ->map(fn (NavigationVersion $model) => NavigationMapper::toVersion($model))
            ->all();
    }

    public function findVersion(TenantContext $context, string $versionPublicId): NavigationVersionDto
    {
        $query = NavigationVersion::query()->where('public_id', $versionPublicId);
        NavigationMapper::applyOrganizationScope($query, $context->organization->id);
        NavigationMapper::applyWorkspaceScope($query, $context->workspace?->id);

        $model = $query->first();

        if ($model === null) {
            throw new NavigationNotFoundException(sprintf('Navigation version [%s] was not found.', $versionPublicId));
        }

        return NavigationMapper::toVersion($model);
    }

    public function findPublishedVersion(TenantContext $context, string $navigationKey, ?string $moduleKey = null): ?NavigationVersionDto
    {
        $definition = $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey ?? '',
            $navigationKey,
        );

        if ($definition->current_version_id === null) {
            return null;
        }

        $version = $definition->currentVersion;

        return $version !== null ? NavigationMapper::toVersion($version) : null;
    }
}
