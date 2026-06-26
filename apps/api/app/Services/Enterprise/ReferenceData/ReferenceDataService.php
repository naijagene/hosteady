<?php

namespace App\Services\Enterprise\ReferenceData;

use App\Modules\Sdk\Enterprise\Contracts\ReferenceDataPort;
use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Modules\Sdk\Enterprise\Data\ReferenceItemData;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class ReferenceDataService
{
    public function __construct(
        private readonly ReferenceDataPort $referenceDataPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
    ) {
    }

    public function catalog(TenantContext $context, string $catalogKey): ?ReferenceCatalogData
    {
        $this->assertEnabled($context);

        return $this->referenceDataPort->catalog($catalogKey);
    }

    /**
     * @return list<ReferenceItemData>
     */
    public function listItems(TenantContext $context, string $catalogKey, bool $activeOnly = true): array
    {
        $this->assertEnabled($context);

        return $this->referenceDataPort->listItems($catalogKey, $activeOnly);
    }

    public function findItem(TenantContext $context, string $catalogKey, string $code): ?ReferenceItemData
    {
        $this->assertEnabled($context);

        return $this->referenceDataPort->findItem($catalogKey, $code);
    }

    private function assertEnabled(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'reference_data');
    }
}
