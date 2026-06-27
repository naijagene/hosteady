<?php

namespace App\Services\Entity;

use App\Models\EntityActivityLog;
use App\Models\EntityComment;
use App\Models\EntityDefinition;
use App\Models\EntityRelationship;
use App\Models\EntityTag;
use App\Models\Organization;
use App\Models\Workspace;
use App\Modules\Sdk\Entity\Data\EntityStatistics;

class EnterpriseEntityStatisticsService
{
    public function statistics(
        ?string $organizationId = null,
        ?string $workspaceId = null,
    ): EntityStatistics {
        $definitions = EntityDefinition::query()->count();
        $relationships = EntityRelationship::query()->count();

        $commentQuery = EntityComment::query();
        $tagQuery = EntityTag::query();
        $activityQuery = EntityActivityLog::query();

        if ($organizationId !== null) {
            $commentQuery->where('organization_id', $organizationId);
            $tagQuery->where('organization_id', $organizationId);
            $activityQuery->where('organization_id', $organizationId);
        }

        if ($workspaceId !== null) {
            $commentQuery->where('workspace_id', $workspaceId);
            $tagQuery->where('workspace_id', $workspaceId);
            $activityQuery->where('workspace_id', $workspaceId);
        }

        $registeredModules = EntityDefinition::query()
            ->distinct()
            ->orderBy('module_key')
            ->pluck('module_key')
            ->values()
            ->all();

        return new EntityStatistics(
            definitions: $definitions,
            relationships: $relationships,
            comments: $commentQuery->count(),
            tags: $tagQuery->count(),
            activityLogs: $activityQuery->count(),
            registeredModules: $registeredModules,
        );
    }

    public function statisticsForScope(?Organization $organization = null, ?Workspace $workspace = null): EntityStatistics
    {
        return $this->statistics($organization?->id, $workspace?->id);
    }
}
