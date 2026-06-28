<?php

namespace App\Services\Dashboard;

use App\Models\DashboardActivityLog;
use App\Models\DashboardDefinition as DashboardDefinitionModel;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use Illuminate\Support\Str;

class DynamicDashboardActivityService
{
    public function __construct(
        private readonly DynamicDashboardAuditRecorder $auditRecorder,
        private readonly DynamicDashboardSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function log(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        ?string $dashboardDefinitionId,
        string $action,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $actorUserId = null,
        ?string $actorMembershipId = null,
        array $metadata = [],
    ): array {
        $model = DashboardActivityLog::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'dashboard_definition_id' => $dashboardDefinitionId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'actor_user_id' => $actorUserId,
            'actor_membership_id' => $actorMembershipId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $this->auditRecorder->recordActivityLogged($action, $dashboardDefinitionId);
        $this->searchIndexer->indexActivityBestEffort($model, $scope);

        return DynamicDashboardMapper::toActivityReference($model);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForDashboard(
        string $organizationId,
        ?string $workspaceId,
        string $dashboardDefinitionPublicId,
    ): array {
        $dashboard = DashboardDefinitionModel::query()->where('public_id', $dashboardDefinitionPublicId)->first();

        if ($dashboard === null) {
            return [];
        }

        $query = DashboardActivityLog::query()
            ->where('organization_id', $organizationId)
            ->where('dashboard_definition_id', $dashboard->id)
            ->orderByDesc('created_at');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (DashboardActivityLog $model) => DynamicDashboardMapper::toActivityReference($model))
            ->all();
    }
}
