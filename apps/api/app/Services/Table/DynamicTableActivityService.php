<?php

namespace App\Services\Table;

use App\Models\TableActivityLog;
use App\Models\TableDefinition as TableDefinitionModel;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class DynamicTableActivityService
{
    public function __construct(
        private readonly DynamicTableAuditRecorder $auditRecorder,
        private readonly DynamicTableSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function log(
        EnterpriseScope $scope,
        string $organizationId,
        ?string $workspaceId,
        ?string $tableDefinitionId,
        string $action,
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $actorUserId = null,
        ?string $actorMembershipId = null,
        array $metadata = [],
    ): array {
        $model = TableActivityLog::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'table_definition_id' => $tableDefinitionId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'actor_user_id' => $actorUserId,
            'actor_membership_id' => $actorMembershipId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $this->auditRecorder->recordActivityLogged($action, $tableDefinitionId);
        $this->searchIndexer->indexActivityBestEffort($model, $scope);

        return DynamicTableMapper::toActivityReference($model);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForTable(
        string $organizationId,
        ?string $workspaceId,
        string $tableDefinitionPublicId,
    ): array {
        $table = TableDefinitionModel::query()->where('public_id', $tableDefinitionPublicId)->first();

        if ($table === null) {
            return [];
        }

        $query = TableActivityLog::query()
            ->where('organization_id', $organizationId)
            ->where('table_definition_id', $table->id)
            ->orderByDesc('created_at');

        if ($workspaceId !== null) {
            $query->where('workspace_id', $workspaceId);
        }

        return $query->get()
            ->map(fn (TableActivityLog $model) => DynamicTableMapper::toActivityReference($model))
            ->all();
    }
}
