<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationHealthResource;
use App\Http\Resources\ApplicationRuntimeResource;
use App\Http\Resources\ApplicationStatisticsResource;
use App\Http\Resources\NavigationResource;
use App\Http\Resources\ApplicationWorkspaceResource;
use App\Models\ApplicationRuntime\ApplicationRuntimeApp;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Services\Application\ApplicationDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseApplicationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationDevelopmentService $developmentService,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ApplicationRuntimeApp::class);

        return ApplicationRuntimeResource::collection(
            $this->developmentService->listApplications(app(TenantContext::class)),
        );
    }

    public function show(string $publicId): ApplicationRuntimeResource
    {
        $this->authorize('view', ApplicationRuntimeApp::class);

        return new ApplicationRuntimeResource(
            $this->developmentService->findApplication(app(TenantContext::class), $publicId),
        );
    }

    public function register(Request $request): JsonResponse
    {
        $this->authorize('create', ApplicationRuntimeApp::class);
        $validated = $request->validate([
            'application_key' => ['required', 'string', 'max:128'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'application_type' => ['nullable', 'string'],
            'visibility' => ['nullable', 'string'],
            'module_key' => ['nullable', 'string', 'max:64'],
            'manifest' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $created = $this->developmentService->register(
            app(TenantContext::class),
            ApplicationDefinition::fromArray($validated),
        );

        return (new ApplicationRuntimeResource($created))->response()->setStatusCode(201);
    }

    public function enable(string $publicId): ApplicationRuntimeResource
    {
        $this->authorize('update', ApplicationRuntimeApp::class);

        return new ApplicationRuntimeResource(
            $this->developmentService->enable(app(TenantContext::class), $publicId),
        );
    }

    public function disable(string $publicId): ApplicationRuntimeResource
    {
        $this->authorize('update', ApplicationRuntimeApp::class);

        return new ApplicationRuntimeResource(
            $this->developmentService->disable(app(TenantContext::class), $publicId),
        );
    }

    public function navigation(): AnonymousResourceCollection
    {
        $this->authorize('viewNavigation', ApplicationRuntimeApp::class);

        return NavigationResource::collection(
            $this->developmentService->navigation(app(TenantContext::class)),
        );
    }

    public function workspaces(): AnonymousResourceCollection
    {
        $this->authorize('viewWorkspaces', ApplicationRuntimeApp::class);

        return ApplicationWorkspaceResource::collection(
            $this->developmentService->workspaces(app(TenantContext::class)),
        );
    }

    public function health(): ApplicationHealthResource
    {
        $this->authorize('viewAny', ApplicationRuntimeApp::class);

        return new ApplicationHealthResource(
            $this->developmentService->health(app(TenantContext::class)),
        );
    }

    public function statistics(): ApplicationStatisticsResource
    {
        $this->authorize('viewAny', ApplicationRuntimeApp::class);

        return new ApplicationStatisticsResource(
            $this->developmentService->statistics(app(TenantContext::class)),
        );
    }
}
