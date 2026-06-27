<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\Organization;
use App\Models\WorkflowPackage;
use App\Models\WorkflowPackageDependency;
use App\Models\WorkflowPackageVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage as WorkflowPackageDto;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageVersion as WorkflowPackageVersionDto;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageStatus;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageType;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageVisibility;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageException;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowPackageService
{
    public function __construct(
        private readonly WorkflowPackageValidatorService $validator,
        private readonly WorkflowPackageProviderService $provider,
        private readonly WorkflowMarketplaceAuditRecorder $auditRecorder,
        private readonly WorkflowMarketplaceSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackageDto {
        $manifest = $this->provider->normalizeManifest(WorkflowPackageManifest::fromArray($payload));
        $this->validator->assertValid($manifest);

        $organization = Organization::query()->where('public_id', $scope->organizationPublicId)->firstOrFail();
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        return DB::transaction(function () use (
            $scope,
            $payload,
            $manifest,
            $organization,
            $workspaceId,
            $userId,
            $membershipId,
        ) {
            $packageKey = (string) ($payload['package_key'] ?? $manifest->key);
            $packageKey = $this->resolveUniqueKey($organization->id, $workspaceId, $packageKey);

            $model = WorkflowPackage::query()->create([
                'organization_id' => $organization->id,
                'workspace_id' => $workspaceId,
                'module_key' => $payload['module_key'] ?? $manifest->moduleKey ?? $scope->moduleKey,
                'package_key' => $packageKey,
                'name' => (string) ($payload['name'] ?? $manifest->name),
                'description' => $payload['description'] ?? $manifest->description,
                'author' => $payload['author'] ?? $manifest->author,
                'license' => $payload['license'] ?? $manifest->license,
                'visibility' => WorkflowPackageVisibility::tryFrom((string) ($payload['visibility'] ?? 'organization'))
                    ?? WorkflowPackageVisibility::Organization,
                'type' => WorkflowPackageType::tryFrom((string) ($payload['type'] ?? 'solution'))
                    ?? WorkflowPackageType::Solution,
                'status' => WorkflowPackageStatus::Draft,
                'tags' => $payload['tags'] ?? $manifest->tags,
                'metadata' => $payload['metadata'] ?? $manifest->metadata,
                'created_by_user_id' => $userId,
                'created_by_membership_id' => $membershipId,
            ]);

            $packageJson = ['manifest' => $manifest->toArray()];
            $checksum = hash('sha256', json_encode($packageJson));

            WorkflowPackageVersion::query()->create([
                'workflow_package_id' => $model->id,
                'version' => $manifest->version,
                'manifest_json' => $manifest->toArray(),
                'package_json' => $packageJson,
                'checksum' => $checksum,
                'status' => WorkflowPackageStatus::Draft,
                'created_by_user_id' => $userId,
                'created_by_membership_id' => $membershipId,
            ]);

            $this->syncDependencies($model, $manifest);
            $this->auditRecorder->recordCreated($model);
            $this->searchIndexer->indexPackageBestEffort($model->fresh(['versions', 'dependencies']));

            return WorkflowPackageMapper::toDto($model->fresh(['versions', 'dependencies']));
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publishVersion(
        EnterpriseScope $scope,
        string $packagePublicId,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackageVersionDto {
        $model = $this->findPackage($scope, $packagePublicId);
        $existingVersion = $model->versions()->orderByDesc('created_at')->first();

        $baseManifest = is_array($existingVersion?->manifest_json) ? $existingVersion->manifest_json : [];

        $manifest = $this->provider->normalizeManifest(WorkflowPackageManifest::fromArray(array_merge(
            $baseManifest,
            is_array($payload['manifest'] ?? null) ? $payload['manifest'] : [],
            $payload,
        )));
        $this->validator->assertValid($manifest);

        return DB::transaction(function () use ($model, $manifest, $payload, $userId, $membershipId) {
            $versionNumber = (string) ($payload['version'] ?? $manifest->version);
            $packageJson = is_array($payload['package_json'] ?? null)
                ? $payload['package_json']
                : ['manifest' => $manifest->toArray()];

            $version = WorkflowPackageVersion::query()->updateOrCreate(
                [
                    'workflow_package_id' => $model->id,
                    'version' => $versionNumber,
                ],
                [
                    'manifest_json' => $manifest->toArray(),
                    'package_json' => $packageJson,
                    'checksum' => hash('sha256', json_encode($packageJson)),
                    'status' => WorkflowPackageStatus::Published,
                    'published_at' => now(),
                    'created_by_user_id' => $userId,
                    'created_by_membership_id' => $membershipId,
                ],
            );

            $model->update(['status' => WorkflowPackageStatus::Published]);
            $this->syncDependencies($model, $manifest);
            $this->auditRecorder->recordVersionPublished($model, $version);
            $this->searchIndexer->indexPackageVersionBestEffort($version->fresh('workflowPackage'));

            return new WorkflowPackageVersionDto(
                publicId: $version->public_id,
                packagePublicId: $model->public_id,
                version: $version->version,
                status: $version->status->value,
                manifest: $version->manifest_json,
                checksum: $version->checksum,
                publishedAt: $version->published_at?->toIso8601String(),
            );
        });
    }

    public function show(EnterpriseScope $scope, string $packagePublicId): WorkflowPackageDto
    {
        return WorkflowPackageMapper::toDto($this->findPackage($scope, $packagePublicId));
    }

    /**
     * @return list<\App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference>
     */
    public function list(EnterpriseScope $scope): array
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organizationId)
                ->value('id');
        }

        return WorkflowPackage::query()
            ->with(['versions'])
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
            })
            ->when($workspaceId !== null, function ($query) use ($workspaceId) {
                $query->where(function ($q) use ($workspaceId) {
                    $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (WorkflowPackage $package) => WorkflowPackageMapper::toReference($package))
            ->all();
    }

    private function syncDependencies(WorkflowPackage $model, WorkflowPackageManifest $manifest): void
    {
        WorkflowPackageDependency::query()->where('workflow_package_id', $model->id)->delete();

        foreach ($manifest->requires as $dependency) {
            WorkflowPackageDependency::query()->create([
                'workflow_package_id' => $model->id,
                'dependency_key' => $dependency->key,
                'dependency_type' => $dependency->type,
                'version_constraint' => $dependency->versionConstraint,
                'required' => $dependency->required,
                'metadata' => $dependency->metadata,
            ]);
        }
    }

    private function resolveUniqueKey(string $organizationId, ?string $workspaceId, string $baseKey): string
    {
        $key = $baseKey;
        $attempt = 0;

        while (WorkflowPackage::query()
            ->where('organization_id', $organizationId)
            ->where('workspace_id', $workspaceId)
            ->where('package_key', $key)
            ->whereNull('deleted_at')
            ->exists()) {
            $attempt++;
            $key = $baseKey.'_'.$attempt;
        }

        return $key;
    }

    private function findPackage(EnterpriseScope $scope, string $packagePublicId): WorkflowPackage
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');

        $package = WorkflowPackage::query()
            ->with(['versions', 'dependencies'])
            ->where('public_id', $packagePublicId)
            ->where(function ($query) use ($organizationId) {
                $query->whereNull('organization_id')->orWhere('organization_id', $organizationId);
            })
            ->first();

        if ($package === null) {
            throw new WorkflowPackageNotFoundException(sprintf('Workflow package [%s] was not found.', $packagePublicId));
        }

        return $package;
    }
}
