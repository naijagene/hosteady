<?php

namespace App\Modules\Sdk\DataRepository\Contracts;

use App\Modules\Sdk\DataRepository\Data\EntityRecord;
use App\Support\Tenant\TenantContext;

interface EntityRecordLifecycleDispatcher
{
    public function dispatchCreated(TenantContext $context, EntityRecord $record, array $beforeState = []): void;

    public function dispatchUpdated(TenantContext $context, EntityRecord $record, array $beforeState, array $afterState): void;

    public function dispatchDeleted(TenantContext $context, EntityRecord $record, array $beforeState): void;

    public function dispatchRestored(TenantContext $context, EntityRecord $record, array $beforeState): void;

    public function dispatchVersioned(TenantContext $context, EntityRecord $record, int $versionNumber): void;

    public function dispatchLinked(TenantContext $context, array $link): void;

    public function dispatchUnlinked(TenantContext $context, array $link): void;

    public function dispatchActivityLogged(TenantContext $context, array $activity): void;

    public function dispatchQueried(TenantContext $context, string $moduleKey, string $entityKey, int $total): void;
}
