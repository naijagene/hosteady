<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationSettingDefinitionResource;
use App\Services\Application\ApplicationRegistryService;
use App\Services\Application\ApplicationSettingsRegistry;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationSettingDefinitionController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(
        string $applicationPublicId,
        ApplicationRegistryService $applicationRegistryService,
        ApplicationSettingsRegistry $settingsRegistry,
    ): AnonymousResourceCollection {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        $application = $applicationRegistryService->findByPublicId($applicationPublicId);

        $this->authorize('view', $application);

        return ApplicationSettingDefinitionResource::collection(
            $settingsRegistry->workspaceDefinitionsForApplication($application->id),
        );
    }
}
