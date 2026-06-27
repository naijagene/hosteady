<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\WorkflowPackageCompatibilityResource;
use App\Http\Resources\WorkflowPackageExportResource;
use App\Http\Resources\WorkflowPackageImportResource;
use App\Http\Resources\WorkflowPackageInstallResource;
use App\Http\Resources\WorkflowPackageInstallResultResource;
use App\Http\Resources\WorkflowPackageResource;
use App\Http\Resources\WorkflowPackageStatisticsResource;
use App\Http\Resources\WorkflowPackageVersionResource;
use App\Models\WorkflowPackage as WorkflowPackageModel;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowInstallRequest;
use App\Services\Enterprise\Workflow\Marketplace\WorkflowMarketplaceService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class WorkflowMarketplaceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly WorkflowMarketplaceService $marketplaceService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return WorkflowPackageResource::collection($this->marketplaceService->listPackages($context));
    }

    public function show(string $packagePublicId): WorkflowPackageResource
    {
        $this->authorize('viewAny', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return new WorkflowPackageResource($this->marketplaceService->showPackage($context, $packagePublicId));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', WorkflowPackageModel::class);
        $validated = $request->validate([
            'package_key' => ['nullable', 'string', 'max:128'],
            'key' => ['nullable', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:128'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'visibility' => ['nullable', 'string', 'in:public,organization,private'],
            'type' => ['nullable', 'string', 'in:solution,template,bundle'],
            'tags' => ['nullable', 'array'],
            'requires' => ['nullable', 'array'],
            'workflow' => ['required', 'array'],
            'canvas' => ['nullable', 'array'],
            'variables' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new WorkflowPackageResource(
            $this->marketplaceService->createPackage($context, $validated),
        ))->response()->setStatusCode(201);
    }

    public function publishVersion(Request $request, string $packagePublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('publish', WorkflowPackageModel::class);
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:32'],
            'manifest' => ['nullable', 'array'],
            'workflow' => ['nullable', 'array'],
            'canvas' => ['nullable', 'array'],
            'requires' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new WorkflowPackageVersionResource(
            $this->marketplaceService->publishVersion($context, $packagePublicId, $validated),
        ))->response()->setStatusCode(201);
    }

    public function export(string $packagePublicId): WorkflowPackageExportResource
    {
        $this->authorize('export', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return new WorkflowPackageExportResource($this->marketplaceService->exportPackage($context, $packagePublicId));
    }

    public function import(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('manage', WorkflowPackageModel::class);
        $validated = $request->validate([
            'format' => ['nullable', 'string'],
            'manifest' => ['nullable', 'array'],
            'package' => ['nullable', 'array'],
            'package_json' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new WorkflowPackageImportResource(
            $this->marketplaceService->importPackage($context, $validated),
        ))->response()->setStatusCode(201);
    }

    public function install(Request $request, string $packagePublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('install', WorkflowPackageModel::class);
        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string'],
            'target_version' => ['nullable', 'string', 'max:32'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new WorkflowPackageInstallResultResource(
            $this->marketplaceService->installPackage($context, new WorkflowInstallRequest(
                packagePublicId: $packagePublicId,
                versionPublicId: $validated['version_public_id'] ?? null,
                targetVersion: $validated['target_version'] ?? null,
                metadata: $validated['metadata'] ?? [],
            )),
        ))->response()->setStatusCode(201);
    }

    public function upgrade(Request $request, string $installPublicId): WorkflowPackageInstallResultResource
    {
        $this->authorize('install', WorkflowPackageModel::class);
        $validated = $request->validate([
            'version_public_id' => ['nullable', 'string'],
            'target_version' => ['nullable', 'string', 'max:32'],
        ]);

        $context = app(TenantContext::class);

        return new WorkflowPackageInstallResultResource(
            $this->marketplaceService->upgradeInstall($context, $installPublicId, $validated),
        );
    }

    public function rollback(string $installPublicId): WorkflowPackageInstallResultResource
    {
        $this->authorize('install', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return new WorkflowPackageInstallResultResource(
            $this->marketplaceService->rollbackInstall($context, $installPublicId),
        );
    }

    public function destroy(string $installPublicId): WorkflowPackageInstallResultResource
    {
        $this->authorize('install', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return new WorkflowPackageInstallResultResource(
            $this->marketplaceService->uninstallPackage($context, $installPublicId),
        );
    }

    public function installed(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return WorkflowPackageInstallResource::collection($this->marketplaceService->listInstalled($context));
    }

    public function updates(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return WorkflowPackageResource::collection($this->marketplaceService->listUpdates($context));
    }

    public function compatibility(string $packagePublicId): WorkflowPackageCompatibilityResource
    {
        $this->authorize('viewAny', WorkflowPackageModel::class);
        $context = app(TenantContext::class);

        return new WorkflowPackageCompatibilityResource(
            $this->marketplaceService->checkCompatibility($context, $packagePublicId),
        );
    }
}
