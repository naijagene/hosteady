<?php

namespace App\Services\Table;

use App\Modules\Sdk\Table\Data\TableDefinition;
use App\Modules\Sdk\Table\Data\TableHealthReport;
use App\Modules\Sdk\Table\Data\TableQueryRequest;
use App\Modules\Sdk\Table\Data\TableQueryResult;
use App\Modules\Sdk\Table\Data\TableStatistics;
use App\Modules\Sdk\Table\Data\TableView;
use App\Modules\Sdk\Table\Exceptions\TableNotFoundException;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DynamicTableDevelopmentService
{
    public function __construct(
        private readonly DynamicTableRegistryService $registryService,
        private readonly DynamicTableDefinitionService $definitionService,
        private readonly DynamicTableGeneratorService $generatorService,
        private readonly DynamicTableRendererService $rendererService,
        private readonly DynamicTableQueryService $queryService,
        private readonly DynamicTableViewService $viewService,
        private readonly DynamicTableActivityService $activityService,
        private readonly DynamicTableHealthService $healthService,
        private readonly DynamicTableStatisticsService $statisticsService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<TableDefinition>
     */
    public function listDefinitions(TenantContext $context, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->list($moduleKey);
    }

    public function showDefinition(TenantContext $context, string $moduleKey, string $tableKey): TableDefinition
    {
        return $this->findDefinition($context, $moduleKey, $tableKey);
    }

    public function findDefinition(TenantContext $context, string $moduleKey, string $tableKey): TableDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->find($moduleKey, $tableKey);

        if ($definition === null) {
            throw new TableNotFoundException(sprintf('Table [%s.%s] was not found.', $moduleKey, $tableKey));
        }

        return $definition;
    }

    /**
     * @return list<TableDefinition>
     */
    public function listByEntity(TenantContext $context, string $moduleKey, string $entityKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->findByEntity($moduleKey, $entityKey);
    }

    /**
     * @param  TableDefinition|array<string, mixed>  $source
     */
    public function registerDefinition(TenantContext $context, mixed $source): TableDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register($source);
    }

    public function updateDefinition(TenantContext $context, TableDefinition $definition): TableDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->update($definition);
    }

    public function deleteDefinition(TenantContext $context, string $moduleKey, string $tableKey): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->definitionService->delete($moduleKey, $tableKey);
    }

    /**
     * @param  list<array<string, mixed>>  $tables
     * @return list<TableDefinition>
     */
    public function registerFromManifestTables(TenantContext $context, array $tables, string $moduleKey): array
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->registerFromManifestTables($tables, $moduleKey);
    }

    public function generateListTable(TenantContext $context, string $moduleKey, string $entityKey): TableDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->generatorService->generateListTable($moduleKey, $entityKey);
    }

    /**
     * @return array<string, mixed>
     */
    public function renderTable(TenantContext $context, TableDefinition $definition, array $renderContext = []): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->rendererService->render($definition, $renderContext);
    }

    public function queryTable(
        TenantContext $context,
        TableQueryRequest $request,
        ?TableDefinition $definition = null,
    ): TableQueryResult {
        $this->requireCapability($context);
        $this->assertQuery($context);

        $definition ??= $this->findDefinition($context, $request->moduleKey, $request->tableKey);

        return $this->queryService->execute($request, $definition);
    }

    /**
     * @return list<TableView>
     */
    public function listViews(TenantContext $context, string $moduleKey, string $tableKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $this->findDefinition($context, $moduleKey, $tableKey);

        return $this->viewService->listViews(
            $moduleKey,
            $tableKey,
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    public function saveView(TenantContext $context, TableView $view): TableView
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $view = new TableView(
            moduleKey: $view->moduleKey,
            tableKey: $view->tableKey,
            name: $view->name,
            publicId: $view->publicId,
            organizationId: $context->organization->id,
            workspaceId: $context->workspace?->id,
            tableDefinitionId: $view->tableDefinitionId,
            columns: $view->columns,
            filters: $view->filters,
            sorts: $view->sorts,
            isDefault: $view->isDefault,
            metadata: $view->metadata,
        );

        return $this->viewService->saveView($view);
    }

    public function deleteViewByPublicId(TenantContext $context, string $viewPublicId): void
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        $this->viewService->deleteView(
            $viewPublicId,
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(TenantContext $context, string $tablePublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->listForTable(
            $context->organization->id,
            $context->workspace?->id,
            $tablePublicId,
        );
    }

    public function health(TenantContext $context): TableHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): TableStatistics
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->statisticsService->statisticsForScope(
            $context->organization,
            $context->workspace,
        );
    }

    private function requireCapability(TenantContext $context): void
    {
        $this->runtimeBridge->requireCapability($context, 'tables');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'tables.read')) {
            throw new HttpException(403, 'You do not have permission to read tables.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'tables.manage')) {
            throw new HttpException(403, 'You do not have permission to manage tables.');
        }
    }

    private function assertQuery(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'tables.query')) {
            throw new HttpException(403, 'You do not have permission to query tables.');
        }
    }
}
