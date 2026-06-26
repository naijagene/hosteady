<?php

namespace App\Services\Enterprise\Workflow;

use App\Models\WorkflowCategory;
use App\Models\WorkflowDefinition;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Data\WorkflowStatistics;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;

class WorkflowStatisticsService
{
    public function statistics(EnterpriseScope $scope, string $organizationId, ?string $workspaceId = null): WorkflowStatistics
    {
        $definitionsQuery = WorkflowDefinition::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $definitionsQuery->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $categoriesQuery = WorkflowCategory::query()
            ->where('organization_id', $organizationId)
            ->whereNull('deleted_at');

        if ($workspaceId !== null) {
            $categoriesQuery->where(function ($builder) use ($workspaceId) {
                $builder->whereNull('workspace_id')->orWhere('workspace_id', $workspaceId);
            });
        }

        $definitions = (clone $definitionsQuery)->count();
        $published = (clone $definitionsQuery)->where('status', WorkflowStatus::Published)->count();
        $drafts = (clone $definitionsQuery)->where('status', WorkflowStatus::Draft)->count();
        $archived = (clone $definitionsQuery)->where('status', WorkflowStatus::Archived)->count();
        $categories = (clone $categoriesQuery)->count();

        return new WorkflowStatistics(
            definitions: $definitions,
            published: $published,
            drafts: $drafts,
            archived: $archived,
            categories: $categories,
        );
    }

    public function invalidPublishedCount(string $organizationId): int
    {
        return WorkflowDefinition::query()
            ->where('organization_id', $organizationId)
            ->where('status', WorkflowStatus::Published)
            ->whereNull('deleted_at')
            ->whereHas('currentVersion', function ($query) {
                $query->where('status', WorkflowVersionStatus::Published)
                    ->where(function ($nested) {
                        $nested->whereNull('validation_report')
                            ->orWhereRaw("json_extract(validation_report, '$.valid') = false");
                    });
            })
            ->count();
    }
}
