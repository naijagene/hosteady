<?php

namespace App\Services\Navigation;

use App\Models\NavigationDefinition;
use App\Models\NavigationVersion;
use App\Modules\Sdk\Navigation\Contracts\NavigationPublisher;
use App\Modules\Sdk\Navigation\Data\NavigationDefinition as NavigationDefinitionDto;
use App\Modules\Sdk\Navigation\Enums\NavigationDefinitionStatus;
use App\Modules\Sdk\Navigation\Enums\NavigationVersionStatus;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Modules\Sdk\Navigation\Exceptions\NavigationPublishException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class NavigationPublisherService implements NavigationPublisher
{
    public function __construct(
        private readonly NavigationRegistryService $registryService,
        private readonly NavigationAuditRecorder $auditRecorder,
    ) {
    }

    public function publish(
        TenantContext $context,
        string $navigationKey,
        ?string $versionPublicId = null,
        ?string $moduleKey = null,
    ): NavigationDefinitionDto {
        return DB::transaction(function () use ($context, $navigationKey, $versionPublicId, $moduleKey) {
            $definition = $this->registryService->resolveModelByKey(
                $context->organization->id,
                $context->workspace?->id,
                $moduleKey ?? '',
                $navigationKey,
            );

            $version = $this->resolveTargetVersion($definition, $versionPublicId);

            if ($version === null) {
                throw new NavigationPublishException('No draft version is available to publish.');
            }

            $definition->versions()
                ->where('status', NavigationVersionStatus::Published->value)
                ->update(['status' => NavigationVersionStatus::Archived->value]);

            $version->update([
                'status' => NavigationVersionStatus::Published->value,
                'published_at' => now(),
                'published_by_user_id' => $context->user->id,
                'published_by_membership_id' => $context->membership->id,
            ]);

            $definition->update([
                'status' => NavigationDefinitionStatus::Published->value,
                'current_version_id' => $version->id,
                'structure_json' => is_array($version->structure_json) ? $version->structure_json : [],
                'conditions_json' => is_array($version->conditions_json) ? $version->conditions_json : [],
            ]);

            $published = NavigationMapper::toDefinition($definition->fresh(['currentVersion']));
            $this->auditRecorder->recordPublished($published, NavigationMapper::toVersion($version), $context);

            return $published;
        });
    }

    private function resolveTargetVersion(NavigationDefinition $definition, ?string $versionPublicId): ?NavigationVersion
    {
        if ($versionPublicId !== null && $versionPublicId !== '') {
            $version = $definition->versions()->where('public_id', $versionPublicId)->first();

            if ($version === null) {
                throw new NavigationNotFoundException(sprintf('Navigation version [%s] was not found.', $versionPublicId));
            }

            return $version;
        }

        return $definition->versions()
            ->where('status', NavigationVersionStatus::Draft->value)
            ->orderByDesc('version_number')
            ->first();
    }
}
