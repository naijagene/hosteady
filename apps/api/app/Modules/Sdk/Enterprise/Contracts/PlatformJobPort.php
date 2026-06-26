<?php

namespace App\Modules\Sdk\Enterprise\Contracts;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformJobReference;
use App\Modules\Sdk\Enterprise\Data\PlatformJobResult;

interface PlatformJobPort
{
    public function dispatch(PlatformJobDispatchRequest $request): PlatformJobResult;

    public function cancel(EnterpriseScope $scope, string $jobPublicId): PlatformJobReference;

    public function find(EnterpriseScope $scope, string $jobPublicId): ?PlatformJobReference;

    /**
     * @return list<PlatformJobReference>
     */
    public function list(EnterpriseScope $scope, int $limit = 50): array;
}
