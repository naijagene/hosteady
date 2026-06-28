<?php

namespace App\Services\Report;

use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportDefinition;
use App\Modules\Sdk\Report\Data\ReportHealthReport;
use App\Modules\Sdk\Report\Data\ReportScheduleDefinition;
use App\Modules\Sdk\Report\Data\ReportStatistics;
use App\Modules\Sdk\Report\Exceptions\ReportNotFoundException;
use App\Services\Authorization\TenantAuthorizationService;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DynamicReportDevelopmentService
{
    public function __construct(
        private readonly DynamicReportRegistryService $registryService,
        private readonly DynamicReportDefinitionService $definitionService,
        private readonly DynamicReportGeneratorService $generatorService,
        private readonly DynamicReportRendererService $rendererService,
        private readonly DynamicReportRunService $runService,
        private readonly DynamicReportExportService $exportService,
        private readonly DynamicReportScheduleService $scheduleService,
        private readonly DynamicReportActivityService $activityService,
        private readonly DynamicReportHealthService $healthService,
        private readonly DynamicReportStatisticsService $statisticsService,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly TenantAuthorizationService $authorizationService,
    ) {
    }

    /**
     * @return list<ReportDefinition>
     */
    public function listDefinitions(TenantContext $context, ?string $moduleKey = null): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->registryService->list($moduleKey);
    }

    public function showDefinition(TenantContext $context, string $moduleKey, string $reportKey): ReportDefinition
    {
        return $this->findDefinition($context, $moduleKey, $reportKey);
    }

    public function findDefinition(TenantContext $context, string $moduleKey, string $reportKey): ReportDefinition
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $definition = $this->registryService->find($moduleKey, $reportKey);

        if ($definition === null) {
            throw new ReportNotFoundException(sprintf('Report [%s.%s] was not found.', $moduleKey, $reportKey));
        }

        return $definition;
    }

    /**
     * @param  ReportDefinition|array<string, mixed>  $source
     */
    public function registerDefinition(TenantContext $context, mixed $source): ReportDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->registryService->register($source);
    }

    public function generateEntityReport(TenantContext $context, string $moduleKey, string $entityKey, string $reportType = 'list'): ReportDefinition
    {
        $this->requireCapability($context);
        $this->assertManage($context);

        return $this->generatorService->generateEntityReport($moduleKey, $entityKey, $reportType);
    }

    /**
     * @param  array<string, mixed>  $renderContext
     * @return array<string, mixed>
     */
    public function renderReport(TenantContext $context, ReportDefinition $definition, array $renderContext = []): array
    {
        $this->requireCapability($context);
        $this->assertRun($context);

        return $this->rendererService->render($definition, $renderContext);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function runReport(TenantContext $context, string $moduleKey, string $reportKey, array $parameters = []): \App\Modules\Sdk\Report\Data\ReportRunResult
    {
        $this->requireCapability($context);
        $this->assertRun($context);

        return $this->runService->start(
            $moduleKey,
            $reportKey,
            $parameters,
            $context->organization->id,
            $context->workspace?->id,
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function exportReport(
        TenantContext $context,
        string $moduleKey,
        string $reportKey,
        string $exportFormat,
        array $parameters = [],
    ): \App\Modules\Sdk\Report\Data\ReportExportResult {
        $this->requireCapability($context);
        $this->assertExport($context);

        return $this->exportService->requestExport($moduleKey, $reportKey, $exportFormat, $parameters);
    }

    /**
     * @return list<\App\Modules\Sdk\Report\Data\ReportRunResult>
     */
    public function listRuns(TenantContext $context, string $moduleKey, string $reportKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);
        $this->findDefinition($context, $moduleKey, $reportKey);

        return $this->runService->listForReport($moduleKey, $reportKey);
    }

    public function showRun(TenantContext $context, string $runPublicId): \App\Modules\Sdk\Report\Data\ReportRunResult
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $run = $this->runService->findByPublicId($runPublicId);

        if ($run === null) {
            throw new ReportNotFoundException(sprintf('Report run [%s] was not found.', $runPublicId));
        }

        return $run;
    }

    /**
     * @return list<\App\Modules\Sdk\Report\Data\ReportExportResult>
     */
    public function listExports(TenantContext $context, string $moduleKey, string $reportKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);
        $this->findDefinition($context, $moduleKey, $reportKey);

        return $this->exportService->listForReport($moduleKey, $reportKey);
    }

    public function showExport(TenantContext $context, string $exportPublicId): \App\Modules\Sdk\Report\Data\ReportExportResult
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        $export = $this->exportService->findByPublicId($exportPublicId);

        if ($export === null) {
            throw new ReportNotFoundException(sprintf('Report export [%s] was not found.', $exportPublicId));
        }

        return $export;
    }

    public function createSchedule(TenantContext $context, string $moduleKey, string $reportKey, ReportScheduleDefinition $schedule): ReportScheduleDefinition
    {
        $this->requireCapability($context);
        $this->assertSchedule($context);
        $this->findDefinition($context, $moduleKey, $reportKey);

        return $this->scheduleService->create(new ReportScheduleDefinition(
            moduleKey: $moduleKey,
            reportKey: $reportKey,
            name: $schedule->name,
            cronExpression: $schedule->cronExpression,
            runAt: $schedule->runAt,
            timezone: $schedule->timezone,
            exportFormats: $schedule->exportFormats,
            recipients: $schedule->recipients,
            parameters: $schedule->parameters,
            metadata: $schedule->metadata,
        ));
    }

    /**
     * @return list<ReportScheduleDefinition>
     */
    public function listSchedules(TenantContext $context, string $moduleKey, string $reportKey): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);
        $this->findDefinition($context, $moduleKey, $reportKey);

        return $this->scheduleService->list($moduleKey, $reportKey);
    }

    public function pauseSchedule(TenantContext $context, string $schedulePublicId): ReportScheduleDefinition
    {
        $this->requireCapability($context);
        $this->assertSchedule($context);

        return $this->scheduleService->pause($schedulePublicId);
    }

    public function resumeSchedule(TenantContext $context, string $schedulePublicId): ReportScheduleDefinition
    {
        $this->requireCapability($context);
        $this->assertSchedule($context);

        return $this->scheduleService->resume($schedulePublicId);
    }

    public function deleteSchedule(TenantContext $context, string $schedulePublicId): void
    {
        $this->requireCapability($context);
        $this->assertSchedule($context);

        $this->scheduleService->delete($schedulePublicId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActivity(TenantContext $context, string $reportPublicId): array
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->activityService->listForReport(
            $context->organization->id,
            $context->workspace?->id,
            $reportPublicId,
        );
    }

    public function health(TenantContext $context): ReportHealthReport
    {
        $this->requireCapability($context);
        $this->assertRead($context);

        return $this->healthService->health($context);
    }

    public function statistics(TenantContext $context): ReportStatistics
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
        $this->runtimeBridge->requireCapability($context, 'reports');
    }

    private function assertRead(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'reports.read')) {
            throw new HttpException(403, 'You do not have permission to read reports.');
        }
    }

    private function assertManage(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'reports.manage')) {
            throw new HttpException(403, 'You do not have permission to manage reports.');
        }
    }

    private function assertRun(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'reports.run')) {
            throw new HttpException(403, 'You do not have permission to run reports.');
        }
    }

    private function assertExport(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'reports.export')) {
            throw new HttpException(403, 'You do not have permission to export reports.');
        }
    }

    private function assertSchedule(TenantContext $context): void
    {
        if (! $this->authorizationService->allows($context, 'reports.schedule')) {
            throw new HttpException(403, 'You do not have permission to schedule reports.');
        }
    }
}
