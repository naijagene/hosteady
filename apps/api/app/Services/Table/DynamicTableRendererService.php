<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Contracts\TableRenderer;
use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableReference;
use App\Support\Tenant\TenantContext;

class DynamicTableRendererService implements TableRenderer
{
    public function __construct(
        private readonly DynamicTableColumnResolver $columnResolver,
        private readonly DynamicTableActionService $actionService,
        private readonly DynamicTableAuditRecorder $auditRecorder,
    ) {
    }

    public function render(TableDefinition $definition, array $context = []): array
    {
        $permissions = is_array($context['permissions'] ?? null) ? $context['permissions'] : $this->defaultPermissions();
        $runtimeContext = $this->buildRuntimeContext($context);
        $columns = $this->columnResolver->resolve($definition, $context);
        $actions = $this->actionService->resolve($definition, $context);
        $pagination = $definition->pagination !== [] ? $definition->pagination : [
            'page' => 1,
            'per_page' => 25,
        ];

        $payload = [
            'metadata' => array_merge($definition->metadata, [
                'module_key' => $definition->moduleKey,
                'table_key' => $definition->tableKey,
                'public_id' => $definition->publicId,
                'name' => $definition->name,
                'description' => $definition->description,
                'type' => $definition->type,
                'status' => $definition->status,
                'visibility' => $definition->visibility,
            ]),
            'columns' => array_map(fn ($column) => $column->toArray(), $columns),
            'filters' => array_map(fn ($filter) => $filter->toArray(), $definition->filters),
            'sorts' => array_map(fn ($sort) => $sort->toArray(), $definition->sorts),
            'default_sort' => $definition->defaultSort?->toArray(),
            'actions' => array_map(fn ($action) => $action->toArray(), $actions),
            'pagination' => $pagination,
            'runtime_context' => $runtimeContext,
            'permissions' => $permissions,
            'entity_reference' => $this->entityReference($definition),
        ];

        $this->auditRecorder->recordRendered($definition);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRuntimeContext(array $context): array
    {
        $runtime = [
            'view_id' => $context['view_id'] ?? null,
            'mode' => $context['mode'] ?? 'default',
        ];

        if (app()->bound(TenantContext::class)) {
            $tenant = app(TenantContext::class);
            $runtime['organization_public_id'] = $tenant->organizationPublicId;
            $runtime['workspace_public_id'] = $tenant->workspacePublicId;
            $runtime['user_public_id'] = $tenant->user->public_id ?? null;
        }

        return array_merge($runtime, is_array($context['runtime'] ?? null) ? $context['runtime'] : []);
    }

    /**
     * @return array<string, bool>
     */
    private function defaultPermissions(): array
    {
        return [
            'read' => true,
            'query' => true,
            'export' => true,
            'manage' => false,
        ];
    }

    private function entityReference(TableDefinition $definition): ?array
    {
        if ($definition->entityKey === null) {
            return null;
        }

        return (new TableReference(
            moduleKey: $definition->moduleKey,
            tableKey: $definition->tableKey,
            publicId: $definition->publicId,
            entityKey: $definition->entityKey,
            label: $definition->name,
            organizationId: $definition->organizationId,
            workspaceId: $definition->workspaceId,
        ))->toArray();
    }
}
