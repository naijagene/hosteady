<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\Organization;
use App\Models\WorkflowPackage;
use App\Models\WorkflowPackageDependency;
use App\Models\WorkflowPackageVersion;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowPackageExporter;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage as WorkflowPackageDto;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageStatus;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageType;
use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowPackageVisibility;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageException;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowPackageExporterService implements WorkflowPackageExporter
{
    public function __construct(
        private readonly WorkflowPackageValidatorService $validator,
        private readonly WorkflowPackageProviderService $provider,
        private readonly WorkflowMarketplaceAuditRecorder $auditRecorder,
        private readonly WorkflowMarketplaceSearchIndexer $searchIndexer,
    ) {
    }

    public function export(EnterpriseScope $scope, string $packagePublicId): array
    {
        $model = $this->findPackage($scope, $packagePublicId);
        $version = $model->versions()
            ->where('status', WorkflowPackageStatus::Published)
            ->orderByDesc('published_at')
            ->first()
            ?? $model->versions()->orderByDesc('created_at')->first();

        if ($version === null) {
            throw new WorkflowPackageException('Package has no versions to export.');
        }

        $payload = [
            'format' => 'heos_package',
            'version' => '1.0',
            'package' => [
                'package_key' => $model->package_key,
                'name' => $model->name,
                'description' => $model->description,
                'author' => $model->author,
                'license' => $model->license,
                'module_key' => $model->module_key,
                'visibility' => $model->visibility->value,
                'type' => $model->type->value,
                'tags' => $model->tags ?? [],
                'metadata' => $model->metadata ?? [],
            ],
            'manifest' => $version->manifest_json,
            'package_json' => $version->package_json,
        ];

        $this->auditRecorder->recordExported($model);

        return $payload;
    }

    public function import(
        EnterpriseScope $scope,
        array $payload,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowPackageDto {
        $manifestData = is_array($payload['manifest'] ?? null) ? $payload['manifest'] : $payload;
        $manifest = $this->provider->normalizeManifest(WorkflowPackageManifest::fromArray($manifestData));
        $this->validator->assertValid($manifest);

        $organization = Organization::query()->where('public_id', $scope->organizationPublicId)->firstOrFail();
        $workspaceId = null;

        if ($scope->workspacePublicId !== null) {
            $workspaceId = Workspace::query()
                ->where('public_id', $scope->workspacePublicId)
                ->where('organization_id', $organization->id)
                ->value('id');
        }

        $packageMeta = is_array($payload['package'] ?? null) ? $payload['package'] : [];

        return DB::transaction(function () use (
            $scope,
            $manifest,
            $payload,
            $organization,
            $workspaceId,
            $packageMeta,
            $userId,
            $membershipId,
        ) {
            $packageKey = $this->resolveUniqueKey(
                $organization->id,
                $workspaceId,
                (string) ($packageMeta['package_key'] ?? $manifest->key),
            );

            $model = \App\Models\WorkflowPackage::query()->create([
                'organization_id' => $organization->id,
                'workspace_id' => $workspaceId,
                'module_key' => $packageMeta['module_key'] ?? $manifest->moduleKey ?? $scope->moduleKey,
                'package_key' => $packageKey,
                'name' => (string) ($packageMeta['name'] ?? $manifest->name),
                'description' => $packageMeta['description'] ?? $manifest->description,
                'author' => $packageMeta['author'] ?? $manifest->author,
                'license' => $packageMeta['license'] ?? $manifest->license,
                'visibility' => WorkflowPackageVisibility::tryFrom((string) ($packageMeta['visibility'] ?? 'organization'))
                    ?? WorkflowPackageVisibility::Organization,
                'type' => WorkflowPackageType::tryFrom((string) ($packageMeta['type'] ?? 'solution'))
                    ?? WorkflowPackageType::Solution,
                'status' => WorkflowPackageStatus::Draft,
                'tags' => $packageMeta['tags'] ?? $manifest->tags,
                'metadata' => $packageMeta['metadata'] ?? $manifest->metadata,
                'created_by_user_id' => $userId,
                'created_by_membership_id' => $membershipId,
            ]);

            $packageJson = is_array($payload['package_json'] ?? null)
                ? $payload['package_json']
                : ['manifest' => $manifest->toArray()];

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
            $this->auditRecorder->recordImported($model);
            $this->searchIndexer->indexPackageBestEffort($model->fresh(['versions', 'dependencies']));

            return WorkflowPackageMapper::toDto($model->fresh(['versions', 'dependencies']));
        });
    }

    public function buildManifestFromPayload(array $payload): WorkflowPackageManifest
    {
        $manifestData = is_array($payload['manifest'] ?? null) ? $payload['manifest'] : $payload;

        return $this->provider->normalizeManifest(WorkflowPackageManifest::fromArray($manifestData));
    }

    private function syncDependencies(\App\Models\WorkflowPackage $model, WorkflowPackageManifest $manifest): void
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

        while (\App\Models\WorkflowPackage::query()
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

    private function findPackage(EnterpriseScope $scope, string $packagePublicId): \App\Models\WorkflowPackage
    {
        $organizationId = Organization::query()->where('public_id', $scope->organizationPublicId)->value('id');

        $package = \App\Models\WorkflowPackage::query()
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
