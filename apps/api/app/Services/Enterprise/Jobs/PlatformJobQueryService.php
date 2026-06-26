<?php

namespace App\Services\Enterprise\Jobs;

use App\Models\Organization;
use App\Models\PlatformJob;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;

class PlatformJobQueryService
{
    public function findModel(EnterpriseScope $scope, string $jobPublicId): PlatformJob
    {
        return PlatformJob::query()
            ->where('public_id', $jobPublicId)
            ->where('organization_id', $this->organizationId($scope))
            ->whereNull('deleted_at')
            ->firstOrFail();
    }

    private function organizationId(EnterpriseScope $scope): string
    {
        return (string) Organization::query()
            ->where('public_id', $scope->organizationPublicId)
            ->value('id');
    }
}
