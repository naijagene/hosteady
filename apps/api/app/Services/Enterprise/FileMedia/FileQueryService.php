<?php

namespace App\Services\Enterprise\FileMedia;

use App\Models\Organization;
use App\Models\PlatformFile;
use App\Models\Workspace;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;

class FileQueryService
{
    public function findModel(EnterpriseScope $scope, string $filePublicId): PlatformFile
    {
        return PlatformFile::query()
            ->where('public_id', $filePublicId)
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
