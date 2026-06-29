<?php

namespace App\Services\Navigation;

use App\Models\NavigationDefinition;
use App\Models\NavigationVersion;
use App\Modules\Sdk\Navigation\Contracts\NavigationDraftManager;
use App\Modules\Sdk\Navigation\Data\NavigationVersion as NavigationVersionDto;
use App\Modules\Sdk\Navigation\Enums\NavigationVersionStatus;
use App\Modules\Sdk\Navigation\Exceptions\NavigationNotFoundException;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class NavigationDraftService implements NavigationDraftManager
{
    public function __construct(
        private readonly NavigationRegistryService $registryService,
        private readonly NavigationAuditRecorder $auditRecorder,
    ) {
    }

    public function saveDraft(
        TenantContext $context,
        string $navigationKey,
        array $structure,
        ?string $moduleKey = null,
    ): NavigationVersionDto {
        $definition = $this->resolveDefinition($context, $moduleKey ?? '', $navigationKey);
        $draft = $this->findDraftModel($definition);

        if ($draft !== null) {
            $before = NavigationMapper::toVersion($draft)->toArray();
            $draft->update([
                'structure_json' => $structure,
                'metadata' => array_merge(is_array($draft->metadata) ? $draft->metadata : [], [
                    'saved_at' => now()->toIso8601String(),
                ]),
            ]);
            $updated = NavigationMapper::toVersion($draft->fresh());
            $this->auditRecorder->recordDraftSaved($updated, $before, $context);

            return $updated;
        }

        $versionNumber = ((int) $definition->versions()->max('version_number')) + 1;

        $draft = NavigationVersion::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace?->id,
            'navigation_definition_id' => $definition->id,
            'version_number' => $versionNumber,
            'status' => NavigationVersionStatus::Draft->value,
            'structure_json' => $structure,
            'conditions_json' => is_array($definition->conditions_json) ? $definition->conditions_json : [],
            'metadata' => ['saved_at' => now()->toIso8601String()],
        ]);

        $created = NavigationMapper::toVersion($draft);
        $this->auditRecorder->recordDraftSaved($created, null, $context);

        return $created;
    }

    public function loadDraft(
        TenantContext $context,
        string $navigationKey,
        ?string $moduleKey = null,
    ): ?NavigationVersionDto {
        $definition = $this->resolveDefinition($context, $moduleKey ?? '', $navigationKey);
        $draft = $this->findDraftModel($definition);

        return $draft !== null ? NavigationMapper::toVersion($draft) : null;
    }

    public function discardDraft(
        TenantContext $context,
        string $navigationKey,
        ?string $moduleKey = null,
    ): void {
        $definition = $this->resolveDefinition($context, $moduleKey ?? '', $navigationKey);
        $draft = $this->findDraftModel($definition);

        if ($draft === null) {
            return;
        }

        $before = NavigationMapper::toVersion($draft)->toArray();
        $draft->delete();
        $this->auditRecorder->recordDraftDiscarded($definition->public_id, $before, $context);
    }

    private function resolveDefinition(TenantContext $context, string $moduleKey, string $navigationKey): NavigationDefinition
    {
        return $this->registryService->resolveModelByKey(
            $context->organization->id,
            $context->workspace?->id,
            $moduleKey,
            $navigationKey,
        );
    }

    private function findDraftModel(NavigationDefinition $definition): ?NavigationVersion
    {
        return $definition->versions()
            ->where('status', NavigationVersionStatus::Draft->value)
            ->orderByDesc('version_number')
            ->first();
    }

    public function findDraftByPublicId(string $versionPublicId): NavigationVersionDto
    {
        $model = NavigationVersion::query()->where('public_id', $versionPublicId)->first();

        if ($model === null) {
            throw new NavigationNotFoundException(sprintf('Navigation draft [%s] was not found.', $versionPublicId));
        }

        return NavigationMapper::toVersion($model);
    }
}
