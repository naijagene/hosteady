<?php

namespace App\Services\Application;

use App\Modules\Sdk\Application\Contracts\ApplicationLifecycle;
use App\Modules\Sdk\Application\Contracts\ApplicationProvider;
use App\Modules\Sdk\Application\Contracts\ApplicationRegistry;
use App\Modules\Sdk\Application\Contracts\ApplicationRuntime;
use App\Modules\Sdk\Application\Data\ApplicationDefinition;
use App\Support\Tenant\TenantContext;

class EnterpriseApplicationService implements ApplicationProvider
{
    public function __construct(
        private readonly ApplicationRuntimeRegistryService $registryService,
        private readonly ApplicationRuntimeService $runtimeService,
    ) {
    }

    public function application(TenantContext $context, string $applicationKey): ApplicationDefinition
    {
        foreach ($this->registryService->list($context->organization->id, $context->workspace?->id) as $application) {
            if ($application->applicationKey === $applicationKey) {
                return $application;
            }
        }

        throw new \App\Modules\Sdk\Application\Exceptions\ApplicationRuntimeException(
            sprintf('Application [%s] was not found.', $applicationKey),
        );
    }
}
