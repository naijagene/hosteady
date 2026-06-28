<?php

namespace App\Services\DataRepository;

use App\Models\EnterpriseEntityRecord;
use App\Models\EnterpriseEntityRecordActivity;
use App\Models\EnterpriseEntityRecordLink;
use App\Models\EnterpriseEntityRecordVersion;
use App\Modules\Sdk\DataRepository\Data\EntityRecordHealthReport;
use App\Modules\Sdk\DataRepository\Data\EntityRecordStatistics;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class EnterpriseEntityRecordStatisticsService
{
    public function statisticsForScope(?object $organization = null, ?object $workspace = null): EntityRecordStatistics
    {
        $recordsQuery = EnterpriseEntityRecord::query();
        $versionsQuery = EnterpriseEntityRecordVersion::query();
        $linksQuery = EnterpriseEntityRecordLink::query();
        $activityQuery = EnterpriseEntityRecordActivity::query();

        if ($organization !== null) {
            $recordsQuery->where('organization_id', $organization->id);
            $versionsQuery->where('organization_id', $organization->id);
            $linksQuery->where('organization_id', $organization->id);
            $activityQuery->where('organization_id', $organization->id);
        }

        if ($workspace !== null) {
            $recordsQuery->where(function ($q) use ($workspace) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id);
            });
            $versionsQuery->where(function ($q) use ($workspace) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id);
            });
            $linksQuery->where(function ($q) use ($workspace) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id);
            });
            $activityQuery->where(function ($q) use ($workspace) {
                $q->whereNull('workspace_id')->orWhere('workspace_id', $workspace->id);
            });
        }

        $recordsByEntity = $recordsQuery->get()
            ->groupBy(fn (EnterpriseEntityRecord $record) => $record->module_key.'.'.$record->entity_key)
            ->map(fn ($group) => $group->count())
            ->all();

        $registeredModules = $recordsQuery->get()
            ->pluck('module_key')
            ->unique()
            ->values()
            ->all();

        return new EntityRecordStatistics(
            records: EnterpriseEntityRecord::query()->when($organization !== null, fn ($q) => $q->where('organization_id', $organization->id))->count(),
            versions: $versionsQuery->count(),
            links: $linksQuery->count(),
            activityLogs: $activityQuery->count(),
            recordsByEntity: $recordsByEntity,
            registeredModules: $registeredModules,
        );
    }
}
