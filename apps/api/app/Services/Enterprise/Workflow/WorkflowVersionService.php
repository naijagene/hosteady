<?php

namespace App\Services\Enterprise\Workflow;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowDefinitionHistory;
use App\Models\WorkflowVersion;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Modules\Sdk\Workflow\Enums\WorkflowStatus;
use App\Modules\Sdk\Workflow\Enums\WorkflowVersionStatus;
use App\Modules\Sdk\Workflow\Exceptions\WorkflowPublishException;
use Illuminate\Support\Facades\DB;

class WorkflowVersionService
{
    public function __construct(
        private readonly WorkflowValidationService $validationService,
    ) {
    }

    public function createDraft(
        WorkflowDefinition $definition,
        array $definitionJson,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowVersion {
        $nextVersion = ((int) $definition->versions()->max('version_number')) + 1;

        return WorkflowVersion::query()->create([
            'workflow_definition_id' => $definition->id,
            'version_number' => $nextVersion,
            'status' => WorkflowVersionStatus::Draft,
            'definition_json' => $definitionJson,
            'validation_report' => $this->validationService
                ->validateDefinitionJson($definitionJson, $definition->workflow_key)
                ->toArray(),
            'created_by_user_id' => $userId,
            'created_membership_id' => $membershipId,
        ]);
    }

    public function publish(
        WorkflowDefinition $definition,
        ?WorkflowVersion $targetVersion = null,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowVersion {
        return DB::transaction(function () use ($definition, $targetVersion, $userId, $membershipId) {
            $version = $targetVersion ?? $definition->versions()
                ->where('status', WorkflowVersionStatus::Draft)
                ->orderByDesc('version_number')
                ->first();

            if ($version === null) {
                throw new WorkflowPublishException('No draft version is available to publish.');
            }

            $report = WorkflowValidationReport::fromArray(
                $this->validationService
                    ->validateDefinitionJson($version->definition_json, $definition->workflow_key)
                    ->toArray(),
            );

            $version->update(['validation_report' => $report->toArray()]);

            if (! $report->valid) {
                throw new WorkflowPublishException('Workflow definition failed validation and cannot be published.');
            }

            $definition->versions()
                ->where('status', WorkflowVersionStatus::Published)
                ->update([
                    'status' => WorkflowVersionStatus::Archived,
                    'archived_at' => now(),
                ]);

            $version->update([
                'status' => WorkflowVersionStatus::Published,
                'published_at' => now(),
                'archived_at' => null,
            ]);

            $definition->update([
                'status' => WorkflowStatus::Published,
                'current_version_id' => $version->id,
            ]);

            $this->recordHistory($definition, $version, 'published', $userId, $membershipId);

            return $version->fresh();
        });
    }

    public function archiveDefinition(
        WorkflowDefinition $definition,
        ?string $userId = null,
        ?string $membershipId = null,
    ): WorkflowDefinition {
        return DB::transaction(function () use ($definition, $userId, $membershipId) {
            $definition->versions()
                ->whereIn('status', [WorkflowVersionStatus::Draft, WorkflowVersionStatus::Published])
                ->update([
                    'status' => WorkflowVersionStatus::Archived,
                    'archived_at' => now(),
                ]);

            $definition->update([
                'status' => WorkflowStatus::Archived,
                'current_version_id' => null,
            ]);

            $this->recordHistory($definition, null, 'archived', $userId, $membershipId);

            return $definition->fresh(['currentVersion', 'category']);
        });
    }

    private function recordHistory(
        WorkflowDefinition $definition,
        ?WorkflowVersion $version,
        string $action,
        ?string $userId,
        ?string $membershipId,
    ): void {
        WorkflowDefinitionHistory::query()->create([
            'workflow_definition_id' => $definition->id,
            'workflow_version_id' => $version?->id,
            'action' => $action,
            'before_state' => null,
            'after_state' => [
                'status' => $definition->status->value,
                'current_version_id' => $definition->current_version_id,
            ],
            'created_by_user_id' => $userId,
            'created_membership_id' => $membershipId,
            'created_at' => now(),
        ]);
    }
}
