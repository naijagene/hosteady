<?php

namespace App\Services\Rules;

use App\Models\Organization;
use App\Models\RuleDefinition as RuleDefinitionModel;
use App\Models\RuleEvaluation;
use App\Models\RuleExecution;
use App\Models\RuleSet;
use App\Models\Workspace;
use App\Modules\Sdk\Rules\Data\RuleStatistics;

class RuleStatisticsService
{
    public function statisticsForScope(?Organization $organization, ?Workspace $workspace): RuleStatistics
    {
        if ($organization === null) {
            return new RuleStatistics(0, 0, 0, 0, 0);
        }

        $workspaceId = $workspace?->id;

        return new RuleStatistics(
            ruleSets: $this->countScoped(RuleSet::query(), $organization->id, $workspaceId),
            ruleDefinitions: $this->countScoped(RuleDefinitionModel::query(), $organization->id, $workspaceId),
            evaluations: $this->countScoped(RuleEvaluation::query(), $organization->id, $workspaceId),
            executions: $this->countScoped(RuleExecution::query(), $organization->id, $workspaceId),
            violations: RuleEvaluation::query()->where('organization_id', $organization->id)->where('allowed', false)->count(),
        );
    }

    private function countScoped($query, string $organizationId, ?string $workspaceId): int
    {
        $query->where('organization_id', $organizationId);
        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        return (int) $query->count();
    }
}
