<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowExecutionVariable;
use App\Models\WorkflowInstance;
use App\Models\WorkflowVariable;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowVariableSnapshot;

class WorkflowExecutionVariableResolver
{
    /**
     * @param  array<string, mixed>|null  $inputPayload
     * @return array<string, mixed>
     */
    public function resolve(
        WorkflowDefinition $definition,
        WorkflowExecutionContext $context,
        ?array $inputPayload = null,
    ): array {
        $variables = [];

        foreach ($definition->variables as $variable) {
            $variables[$variable->variable_key] = $variable->default_value;
        }

        if (is_array($inputPayload)) {
            foreach ($inputPayload as $key => $value) {
                $variables[(string) $key] = $value;
            }
        }

        $variables['organization_public_id'] = $context->organizationPublicId;
        $variables['workspace_public_id'] = $context->workspacePublicId;
        $variables['user_public_id'] = $context->userPublicId;
        $variables['membership_public_id'] = $context->membershipPublicId;

        if ($context->entityReference !== null) {
            $variables['entity_reference'] = $context->entityReference->toArray();
        }

        foreach ($context->metadata as $key => $value) {
            $variables['context.'.$key] = $value;
        }

        return $variables;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return list<WorkflowVariableSnapshot>
     */
    public function snapshot(WorkflowInstance $instance, array $variables, string $source = 'runtime'): array
    {
        $snapshots = [];
        $now = now()->toIso8601String();

        foreach ($variables as $key => $value) {
            WorkflowExecutionVariable::query()->create([
                'workflow_instance_id' => $instance->id,
                'variable_key' => (string) $key,
                'value' => ['value' => $value],
                'source' => $source,
                'snapshot_at' => now(),
            ]);

            $snapshots[] = new WorkflowVariableSnapshot(
                key: (string) $key,
                value: $value,
                source: $source,
                snapshotAt: $now,
            );
        }

        return $snapshots;
    }

    /**
     * @return array<string, mixed>
     */
    public function loadSnapshot(WorkflowInstance $instance): array
    {
        $variables = [];

        foreach ($instance->variables as $variable) {
            $variables[$variable->variable_key] = is_array($variable->value)
                ? ($variable->value['value'] ?? $variable->value)
                : $variable->value;
        }

        return $variables;
    }
}
