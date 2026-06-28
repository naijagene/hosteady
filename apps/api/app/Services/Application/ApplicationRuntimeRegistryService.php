<?php

namespace App\Services\Application;

use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Modules\Sdk\Application\Contracts\ApplicationLifecycle;
use App\Modules\Sdk\Application\Contracts\ApplicationRegistry;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Modules\Sdk\Application\Enums\ApplicationStatus;
use App\Modules\Sdk\Application\Exceptions\ApplicationRegistrationException;
use App\Modules\Sdk\Application\Exceptions\ApplicationRuntimeException;
use Illuminate\Support\Str;

class ApplicationRuntimeRegistryService implements ApplicationRegistry, ApplicationLifecycle
{
    public function __construct(
        private readonly ApplicationAuditRecorder $auditRecorder,
        private readonly ApplicationSearchIndexer $searchIndexer,
    ) {
    }

    public function register(string $organizationId, ?string $workspaceId, ApplicationDefinition $definition): ApplicationDefinition
    {
        $existing = ApplicationRuntimeApp::query()
            ->where('organization_id', $organizationId)
            ->where('application_key', $definition->applicationKey);

        ApplicationRuntimeMapper::applyWorkspaceScope($existing, $workspaceId);

        if ($existing->first() !== null) {
            throw new ApplicationRegistrationException(sprintf('Application [%s] is already registered.', $definition->applicationKey));
        }

        $model = ApplicationRuntimeApp::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'application_key' => $definition->applicationKey,
            'name' => $definition->name !== '' ? $definition->name : $definition->applicationKey,
            'description' => $definition->description,
            'application_type' => $definition->applicationType !== '' ? $definition->applicationType : 'business',
            'status' => ApplicationStatus::Registered->value,
            'visibility' => $definition->visibility !== '' ? $definition->visibility : 'workspace',
            'module_key' => $definition->moduleKey,
            'manifest_json' => $definition->manifest,
            'metadata' => $definition->metadata,
        ]);

        $created = ApplicationRuntimeMapper::toDefinition($model);
        $this->auditRecorder->recordRegistered($created);
        $this->searchIndexer->indexApplicationBestEffort($created, $organizationId, $workspaceId);

        return $created;
    }

    /** @return list<ApplicationDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = ApplicationRuntimeApp::query()
            ->orderBy('name')
            ->limit($limit);

        ApplicationRuntimeMapper::applyOrganizationScope($query, $organizationId);
        ApplicationRuntimeMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (ApplicationRuntimeApp $model) => ApplicationRuntimeMapper::toDefinition($model))->all();
    }

    public function findByPublicId(string $organizationId, ?string $workspaceId, string $publicId): ApplicationDefinition
    {
        $query = ApplicationRuntimeApp::query()
            ->where('public_id', $publicId);

        ApplicationRuntimeMapper::applyOrganizationScope($query, $organizationId);
        ApplicationRuntimeMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new ApplicationRuntimeException(sprintf('Application [%s] was not found.', $publicId));
        }

        return ApplicationRuntimeMapper::toDefinition($model);
    }

    public function enable(string $organizationId, ?string $workspaceId, string $publicId): ApplicationDefinition
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $publicId);
        $model->fill(['status' => ApplicationStatus::Enabled->value])->save();
        $definition = ApplicationRuntimeMapper::toDefinition($model->fresh());
        $this->auditRecorder->recordEnabled($definition);

        return $definition;
    }

    public function disable(string $organizationId, ?string $workspaceId, string $publicId): ApplicationDefinition
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $publicId);
        $model->fill(['status' => ApplicationStatus::Disabled->value])->save();
        $definition = ApplicationRuntimeMapper::toDefinition($model->fresh());
        $this->auditRecorder->recordDisabled($definition);

        return $definition;
    }

    private function resolveModel(string $organizationId, ?string $workspaceId, string $publicId): ApplicationRuntimeApp
    {
        $query = ApplicationRuntimeApp::query()->where('public_id', $publicId);
        ApplicationRuntimeMapper::applyOrganizationScope($query, $organizationId);
        ApplicationRuntimeMapper::applyWorkspaceScope($query, $workspaceId);
        $model = $query->first();

        if ($model === null) {
            throw new ApplicationRuntimeException(sprintf('Application [%s] was not found.', $publicId));
        }

        return $model;
    }
}
