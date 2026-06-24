<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\InstallApplicationRequest;
use App\Http\Resources\OrganizationApplicationResource;
use App\Models\OrganizationApplication;
use App\Services\Application\ApplicationInstallationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OrganizationApplicationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ApplicationInstallationService $installationService,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OrganizationApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        return OrganizationApplicationResource::collection(
            $this->installationService->listInstalled($context),
        );
    }

    public function store(InstallApplicationRequest $request): JsonResponse
    {
        $this->authorize('install', OrganizationApplication::class);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $installation = $this->installationService->install(
            $context,
            $request->string('application_public_id')->value(),
        );

        return (new OrganizationApplicationResource($installation))
            ->response()
            ->setStatusCode(201);
    }

    public function enable(string $installationPublicId): OrganizationApplicationResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $installation = $this->installationService->resolveInstallation($context, $installationPublicId);

        $this->authorize('configure', $installation);

        return new OrganizationApplicationResource(
            $this->installationService->enable($context, $installationPublicId),
        );
    }

    public function disable(string $installationPublicId): OrganizationApplicationResource
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $installation = $this->installationService->resolveInstallation($context, $installationPublicId);

        $this->authorize('configure', $installation);

        return new OrganizationApplicationResource(
            $this->installationService->disable($context, $installationPublicId),
        );
    }

    public function destroy(string $installationPublicId): Response
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $installation = $this->installationService->resolveInstallation($context, $installationPublicId);

        $this->authorize('uninstall', $installation);

        $this->installationService->uninstall($context, $installationPublicId);

        return response()->noContent();
    }
}
