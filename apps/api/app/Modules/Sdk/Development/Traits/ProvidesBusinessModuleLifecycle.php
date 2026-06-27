<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModuleInstallResult;
use App\Modules\Sdk\Development\Data\BusinessModuleReference;
use App\Modules\Sdk\Development\Enums\BusinessModuleInstallStatus;

trait ProvidesBusinessModuleLifecycle
{
    public function boot(): void
    {
    }

    public function register(): BusinessModuleReference
    {
        return app(\App\Services\Module\Development\BusinessModuleRegistryService::class)
            ->register($this);
    }

    public function install(
        \App\Modules\Sdk\Enterprise\Data\EnterpriseScope $scope,
        array $settings = [],
        array $metadata = [],
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        return app(\App\Services\Module\Development\BusinessModuleInstallerService::class)
            ->installModule(
                $scope,
                $this,
                new \App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest(
                    modulePublicId: '',
                    settings: $settings,
                    metadata: $metadata,
                ),
                $userId,
                $membershipId,
            );
    }

    public function enable(
        \App\Modules\Sdk\Enterprise\Data\EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        return app(\App\Services\Module\Development\BusinessModuleInstallerService::class)
            ->enable($scope, $installationPublicId, $userId, $membershipId);
    }

    public function disable(
        \App\Modules\Sdk\Enterprise\Data\EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        return app(\App\Services\Module\Development\BusinessModuleInstallerService::class)
            ->disable($scope, $installationPublicId, $userId, $membershipId);
    }

    public function uninstall(
        \App\Modules\Sdk\Enterprise\Data\EnterpriseScope $scope,
        string $installationPublicId,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleInstallResult {
        return app(\App\Services\Module\Development\BusinessModuleInstallerService::class)
            ->uninstall($scope, $installationPublicId, $userId, $membershipId);
    }
}
