<?php

namespace App\Services\Rules;

use App\Models\RuleSet;
use App\Modules\Sdk\Rules\Contracts\RuleSetProvider;
use App\Modules\Sdk\Rules\Data\RuleSetDefinition;
use App\Modules\Sdk\Rules\Enums\RuleStatus;
use App\Modules\Sdk\Rules\Exceptions\RuleNotFoundException;
use Illuminate\Support\Str;

class RuleSetService implements RuleSetProvider
{
    public function __construct(
        private readonly RuleValidationService $validationService,
        private readonly RuleAuditRecorder $auditRecorder,
        private readonly RuleActivityService $activityService,
    ) {
    }

    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array
    {
        $query = RuleSet::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit($limit);

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        return $query->get()->map(fn (RuleSet $model) => RuleMapper::toRuleSet($model))->all();
    }

    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?RuleSetDefinition
    {
        $query = RuleSet::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId);

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        return $model ? RuleMapper::toRuleSet($model) : null;
    }

    public function create(string $organizationId, ?string $workspaceId, RuleSetDefinition $definition): RuleSetDefinition
    {
        $this->validationService->validateRuleSet($definition);

        $model = RuleSet::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'name' => $definition->name,
            'description' => $definition->description,
            'scope' => $definition->scope !== '' ? $definition->scope : 'organization',
            'status' => $definition->status !== '' ? $definition->status : 'draft',
            'module_key' => $definition->moduleKey,
            'metadata' => $definition->metadata,
        ]);

        $created = RuleMapper::toRuleSet($model);
        $this->auditRecorder->recordRuleSetCreated($created);
        $this->activityService->logRuleSet($model, 'created');

        return $created;
    }

    public function update(string $organizationId, ?string $workspaceId, RuleSetDefinition $definition): RuleSetDefinition
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $definition->publicId);
        $before = RuleMapper::toRuleSet($model);

        $model->fill([
            'name' => $definition->name,
            'description' => $definition->description,
            'scope' => $definition->scope !== '' ? $definition->scope : 'organization',
            'status' => $definition->status !== '' ? $definition->status : 'draft',
            'module_key' => $definition->moduleKey,
            'metadata' => $definition->metadata,
        ])->save();

        $updated = RuleMapper::toRuleSet($model->fresh());
        $this->auditRecorder->recordRuleSetUpdated($before, $updated);
        $this->activityService->logRuleSet($model, 'updated', $before->toArray(), $updated->toArray());

        return $updated;
    }

    public function enable(string $organizationId, ?string $workspaceId, string $publicId): RuleSetDefinition
    {
        return $this->setStatus($organizationId, $workspaceId, $publicId, RuleStatus::Enabled, 'enabled');
    }

    public function disable(string $organizationId, ?string $workspaceId, string $publicId): RuleSetDefinition
    {
        return $this->setStatus($organizationId, $workspaceId, $publicId, RuleStatus::Disabled, 'disabled');
    }

    private function setStatus(string $organizationId, ?string $workspaceId, string $publicId, RuleStatus $status, string $action): RuleSetDefinition
    {
        $model = $this->resolveModel($organizationId, $workspaceId, $publicId);
        $before = RuleMapper::toRuleSet($model);
        $model->status = $status;
        $model->save();
        $updated = RuleMapper::toRuleSet($model->fresh());

        if ($action === 'enabled') {
            $this->auditRecorder->recordRuleSetEnabled($updated);
        } else {
            $this->auditRecorder->recordRuleSetDisabled($updated);
        }

        $this->activityService->logRuleSet($model, $action, $before->toArray(), $updated->toArray());

        return $updated;
    }

    private function resolveModel(string $organizationId, ?string $workspaceId, string $publicId): RuleSet
    {
        $query = RuleSet::query()
            ->where('organization_id', $organizationId)
            ->where('public_id', $publicId);

        RuleMapper::applyWorkspaceScope($query, $workspaceId);

        $model = $query->first();

        if ($model === null) {
            throw new RuleNotFoundException(sprintf('Rule set [%s] was not found.', $publicId));
        }

        return $model;
    }
}
