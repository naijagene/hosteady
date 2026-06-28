<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecord;
use App\Modules\Sdk\Dashboard\Data\DashboardWidget;

class EnterpriseEntityRecordDashboardBridge
{
    public function entityCount(
        string $organizationId,
        ?string $workspaceId,
        DashboardWidget $widget,
    ): int {
        $moduleKey = (string) ($widget->dataSourceConfig['module_key'] ?? '');
        $entityKey = (string) ($widget->dataSourceConfig['entity_key'] ?? '');

        if ($moduleKey === '' || $entityKey === '') {
            return 0;
        }

        $query = EnterpriseEntityRecord::query()
            ->where('organization_id', $organizationId)
            ->where('module_key', $moduleKey)
            ->where('entity_key', $entityKey);

        if ($workspaceId !== null) {
            $query->where(function ($q) use ($workspaceId) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        return $query->count();
    }
}
