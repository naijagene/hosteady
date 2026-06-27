<?php

namespace App\Services\Module\Development;

use App\Modules\Sdk\Development\Data\BusinessModuleInstallRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleInstallResult;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleReference;
use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldRequest;
use App\Modules\Sdk\Development\Data\BusinessModuleScaffoldResult;
use App\Modules\Sdk\Development\Data\BusinessModuleValidationReport;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessModuleDevelopmentService
{
    public function __construct(
        private readonly BusinessModuleRegistryService $registryService,
        private readonly BusinessModuleInstallerService $installerService,
        private readonly BusinessModuleScaffolderService $scaffolderService,
        private readonly BusinessModuleValidatorService $validatorService,
        private readonly BusinessModuleAuditRecorder $auditRecorder,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<BusinessModuleReference>
     */
    public function listModules(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->all();
    }

    public function showModule(TenantContext $context, string $modulePublicId): BusinessModuleReference
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->show($modulePublicId);
    }

    public function registerModule(TenantContext $context, BusinessModuleManifest $manifest): BusinessModuleReference
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register(
            $manifest,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function validateManifest(BusinessModuleManifest $manifest): BusinessModuleValidationReport
    {
        $report = $this->validatorService->validate($manifest);
        $this->auditRecorder->recordValidated($manifest->moduleKey);

        return $report;
    }

    public function scaffold(BusinessModuleScaffoldRequest $request): BusinessModuleScaffoldResult
    {
        return $this->scaffolderService->scaffold($request);
    }

    /**
     * @return list<BusinessModuleInstallResult>
     */
    public function listInstalled(TenantContext $context): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->installerService->listInstalled($this->scope($context));
    }

    public function install(TenantContext $context, BusinessModuleInstallRequest $request): BusinessModuleInstallResult
    {
        $this->requireCapability($context);
        $this->assertInstall($context);

        return $this->installerService->install(
            $this->scope($context),
            $request,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function enable(TenantContext $context, string $installationPublicId): BusinessModuleInstallResult
    {
        $this->requireCapability($context);
        $this->assertInstall($context);

        return $this->installerService->enable(
            $this->scope($context),
            $installationPublicId,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function disable(TenantContext $context, string $installationPublicId): BusinessModuleInstallResult
    {
        $this->requireCapability($context);
        $this->assertInstall($context);

        return $this->installerService->disable(
            $this->scope($context),
            $installationPublicId,
            $context->user->id,
            $context->membership->id,
        );
    }

    public function uninstall(TenantContext $context, string $installationPublicId): BusinessModuleInstallResult
    {
        $this->requireCapability($context);
        $this->assertInstall($context);

        return $this->installerService->uninstall(
            $this->scope($context),
            $installationPublicId,
            $context->user->id,
            $context->membership->id,
        );
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'business_modules');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'business.modules.read')) {
            throw new HttpException(403, 'You do not have permission to read business modules.');
        }
    }

    private function assertInstall(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'business.modules.install')) {
            throw new HttpException(403, 'You do not have permission to install business modules.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'business.modules.manage')) {
            throw new HttpException(403, 'You do not have permission to manage business modules.');
        }
    }
}
