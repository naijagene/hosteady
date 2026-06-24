<?php

namespace App\Http\Controllers\Api\V1\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Services\Application\ApplicationRegistryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationCatalogController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(ApplicationRegistryService $registryService): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Application::class);

        return ApplicationResource::collection($registryService->listAvailableForInstall());
    }
}
