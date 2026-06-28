<?php

namespace App\Services\Rules;

use App\Models\RuleDefinition as RuleDefinitionModel;
use App\Models\RuleSet;
use App\Modules\Sdk\Rules\Data\RuleDefinition;
use App\Modules\Sdk\Rules\Enums\RuleStatus;
use App\Modules\Sdk\Rules\Exceptions\RuleNotFoundException;
use Illuminate\Support\Str;

class RuleDefinitionService
{
    public function __construct(
        private readonly RuleValidationService $validationService,
        private readonly RuleAuditRecorder $auditRecorder,
        private readonly RuleActivityService $activityService,
        private readonly RuleSearchIndexer $searchIndexer,
    ) {
    }

    /** @return list<RuleDefinition> */
    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = RuleDefinitionModel::query()
            ->where('organization_id', $organizationId)
            ->orderBy('priority')
            ->orderByDesc('created_at')
            ->limit($limit);

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (RuleDefinitionModel $model) => RuleMapper::toRuleDefinition($model))->all();
    }

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?RuleDefinition
    {
        $query = RuleDefinitionModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId);

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? RuleMapper::toRuleDefinition($model) : null;
    }

    public function create(string $organizationId, ?string $workspaceId, RuleDefinition $definition): RuleDefinition
    {
        $this->validationService->validateRuleDefinition($definition);

        $ruleSet = RuleSet::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $definition->ruleSetPublicId)
            ->first();

        if ($ruleSet === null) {
            throw new RuleNotFoundException(sprintf('Rule set [%s] was not found.', $definition->ruleSetPublicId));
        }

        $model = RuleDefinitionModel::query()->create([
            'id' => (string) Str::uuid7(),
            'rule_set_id' => $ruleSet->id,
            'rule_set_public_id' => $ruleSet->public_id,
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'name' => $definition->name,
            'description' => $definition->description,
            'type' => $definition->type !== '' ? $definition->type : 'validation',
            'scope' => $definition->scope !== '' ? $definition->scope : 'organization',
            'status' => $definition->status !== '' ? $definition->status : 'draft',
            'trigger_type' => $definition->triggerType !== '' ? $definition->triggerType : 'manual',
            'priority' => $definition->priority,
            'conditions_json' => $definition->conditions,
            'actions_json' => $definition->actions,
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey,
            'metadata' => $definition->metadata,
        ]);

        $created = RuleMapper::toRuleDefinition($model);
        $this->auditRecorder->recordRuleDefinitionCreated($created);
        $this->activityService->logRuleDefinition($model, 'created');
        $this->searchIndexer->indexDefinition($model);

        return $created;
    }

    public function update(string $organizationId, ?string $workspaceId, RuleDefinition $definition): RuleDefinition
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $definition->publicId);
        $before = RuleMapper::toRuleDefinition($model);

        $model->fill([
            'name' => $definition->name,
            'description' => $definition->description,
            'type' => $definition->type !== '' ? $definition->type : 'validation',
            'scope' => $definition->scope !== '' ? $definition->scope : 'organization',
            'status' => $definition->status !== '' ? $definition->status : 'draft',
            'trigger_type' => $definition->triggerType !== '' ? $definition->triggerType : 'manual',
            'priority' => $definition->priority,
            'conditions_json' => $definition->conditions,
            'actions_json' => $definition->actions,
            'module_key' => $definition->moduleKey,
            'entity_key' => $definition->entityKey,
            'metadata' => $definition->metadata,
        ])->save();

        $updated = RuleMapper::toRuleDefinition($model->fresh());
        $this->auditRecorder->recordRuleDefinitionUpdated($before, $updated);
        $this->activityService->logRuleDefinition($model, 'updated', $before->toArray(), $updated->toArray());
        $this->searchIndexer->indexDefinition($model);

        return $updated;
    }

    public function enable(string $organizationId, ?string $workspaceId, string $publicId): RuleDefinition
    {
        return $this->setStatus($organizationId, $workspaceId, $publicId, RuleStatus::Enabled, 'enabled');
    }

    public function disable(string $organizationId, ?string $workspaceId, string $publicId): RuleDefinition
    {
        return $this->setStatus($organizationId, $workspaceId, $publicId, RuleStatus::Disabled, 'disabled');
    }

    private function setStatus(string $organizationId, ?string $workspaceId, string $publicId, RuleStatus $status, string $action): RuleDefinition
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $publicId);
        $before = RuleMapper::toRuleDefinition($model);
        $model->status = $status;
        $model->save();
        $updated = RuleMapper::toRuleDefinition($model->fresh());

        if ($action === 'enabled') {
            $this->auditRecorder->recordRuleDefinitionEnabled($updated);
        } else {
            $this->auditRecorder->recordRuleDefinitionDisabled($updated);
        }

        $this->activityService->logRuleDefinition($model, $action, $before->toArray(), $updated->toArray());

        return $updated;
    }

    private function resolveModel(string $organizationId, ?string $workspaceId, string $publicId): RuleDefinitionModel
    {
        $query = RuleDefinitionModel::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId);

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new RuleNotFoundException(sprintf('Rule definition [%s] was not found.', $publicId));
        }

        return $model;
    }
}
