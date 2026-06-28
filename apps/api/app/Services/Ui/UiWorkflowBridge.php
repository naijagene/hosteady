<?php

namespace App\Services\Ui;

use App\Models\WorkflowDefinition;
use App\Support\Tenant\TenantContext;

class UiWorkflowBridge
{
    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function resolveReferenceBestEffort(?string $moduleKey, ?string $resourceKey, array $config = []): ?array
    {
        try {
            if (! app()->bound(TenantContext::class)) {
                return null;
            }

            if (! (bool) config('heos.enterprise.workflow.enabled', true)) {
                return null;
            }

            $context = app(TenantContext::class);
            $publicId = (string) ($config['public_id'] ?? '');
            $moduleKey = $moduleKey ?? (string) ($config['module_key'] ?? '');
            $workflowKey = $resourceKey ?? (string) ($config['workflow_key'] ?? $config['resource_key'] ?? '');

            $query = WorkflowDefinition::query();
            UiMapper::applyOrganizationScope($query, $context->organization->id);
            UiMapper::applyWorkspaceScope($query, $context->workspace?->id);

            if ($publicId !== '') {
                $query->where('public_id', $publicId);
            } elseif ($moduleKey !== '' && $workflowKey !== '') {
                $query->where('module_key', $moduleKey)->where('workflow_key', $workflowKey);
            } else {
                return null;
            }

            $model = $query->first();

            if ($model === null) {
                return null;
            }

            return [
                'public_id' => $model->public_id,
                'module_key' => $model->module_key,
                'workflow_key' => $model->workflow_key,
                'name' => $model->name,
                'description' => $model->description,
                'status' => $model->status,
                'metadata' => is_array($model->metadata) ? $model->metadata : [],
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
