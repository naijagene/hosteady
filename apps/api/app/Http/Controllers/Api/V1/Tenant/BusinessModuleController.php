<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessModuleInstallResultResource;
use App\Http\Resources\BusinessModuleInstallationResource;
use App\Http\Resources\BusinessModuleResource;
use App\Models\BusinessModule as BusinessModuleModel;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Services\Module\Development\BusinessModuleDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class BusinessModuleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly BusinessModuleDevelopmentService $developmentService,
    ) {
    }

    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', BusinessModuleModel::class);
        $context = app(TenantContext::class);

        return BusinessModuleResource::collection($this->developmentService->listModules($context));
    }

    public function show(string $modulePublicId): BusinessModuleResource
    {
        $this->authorize('viewAny', BusinessModuleModel::class);
        $context = app(TenantContext::class);

        return new BusinessModuleResource($this->developmentService->showModule($context, $modulePublicId));
    }

    public function installed(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $this->authorize('viewAny', BusinessModuleModel::class);
        $context = app(TenantContext::class);

        return BusinessModuleInstallationResource::collection($this->developmentService->listInstalled($context));
    }

    public function install(Request $request, string $modulePublicId): \Illuminate\Http\JsonResponse
    {
        $this->authorize('install', BusinessModuleModel::class);
        $validated = $request->validate([
            'settings' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $context = app(TenantContext::class);

        return (new BusinessModuleInstallResultResource(
            $this->developmentService->install($context, new BusinessModuleInstallRequest(
                modulePublicId: $modulePublicId,
                settings: $validated['settings'] ?? [],
                metadata: $validated['metadata'] ?? [],
            )),
        ))->response()->setStatusCode(201);
    }

    public function enable(string $installationPublicId): BusinessModuleInstallResultResource
    {
        $this->authorize('install', BusinessModuleModel::class);
        $context = app(TenantContext::class);

        return new BusinessModuleInstallResultResource(
            $this->developmentService->enable($context, $installationPublicId),
        );
    }

    public function disable(string $installationPublicId): BusinessModuleInstallResultResource
    {
        $this->authorize('install', BusinessModuleModel::class);
        $context = app(TenantContext::class);

        return new BusinessModuleInstallResultResource(
            $this->developmentService->disable($context, $installationPublicId),
        );
    }

    public function destroy(string $installationPublicId): BusinessModuleInstallResultResource
    {
        $this->authorize('install', BusinessModuleModel::class);
        $context = app(TenantContext::class);

        return new BusinessModuleInstallResultResource(
            $this->developmentService->uninstall($context, $installationPublicId),
        );
    }
}
