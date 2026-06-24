<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Services\Application\ApplicationRegistryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationCatalogController extends Controller
{
    public function __invoke(ApplicationRegistryService $registryService): AnonymousResourceCollection
    {
        return ApplicationResource::collection($registryService->listPlatformCatalog());
    }
}
