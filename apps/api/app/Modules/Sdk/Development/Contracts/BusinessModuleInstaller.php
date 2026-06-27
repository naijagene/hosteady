<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallResult;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;

interface BusinessModuleInstaller
{
    public function install(
        EnterpriseScope $scope,
        BusinessModuleInstallRequest $request,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult;

    public function enable(
        EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult;

    public function disable(
        EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult;

    public function uninstall(
        EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult;
}
