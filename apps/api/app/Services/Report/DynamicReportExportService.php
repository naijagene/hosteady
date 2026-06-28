<?php

namespace App\Services\Report;

use App\Models\ReportExport as ReportExportModel;
use App\Modules\Sdk\Report\Contracts\ReportExporter;
use App\Modules\Sdk\Report\Data\ReportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportDefinition;
use App\Modules\Sdk\Report\Data\ReportExportResult;
use App\Modules\Sdk\Report\Enums\ReportExportFormat;
use App\Modules\Sdk\Report\Exceptions\ReportExportException;
use App\Modules\Sdk\Report\Exceptions\ReportNotFoundException;
use App\Services\Document\EnterpriseDocumentDevelopmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;

class DynamicReportExportService implements ReportExporter
{
    public function __construct(
        private readonly DynamicReportRegistryService $registryService,
        private readonly DynamicReportAuditRecorder $auditRecorder,
        private readonly EnterpriseDocumentDevelopmentService $documentDevelopmentService,
    ) {
    }

    public function export(ReportDefinition $definition, ReportExportDefinition $exportDefinition): ReportExportResult
    {
        $this->assertValidFormat($exportDefinition->exportFormat);

        $model = $this->registryService->findModel($definition->moduleKey, $definition->reportKey);

        if ($model === null) {
            throw new ReportNotFoundException(sprintf(
                'Report [%s.%s] was not found.',
                $definition->moduleKey,
                $definition->reportKey,
            ));
        }

        $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;

        return DB::transaction(function () use ($definition, $exportDefinition, $model, $context) {
            $this->auditRecorder->recordExportRequested($definition, $exportDefinition->exportFormat);

            $export = new ReportExportModel([
                'organization_id' => $context?->organization->id ?? $model->organization_id,
                'workspace_id' => $context?->workspace->id ?? $model->workspace_id,
                'report_definition_id' => $model->id,
                'export_format' => $exportDefinition->exportFormat,
                'status' => 'completed',
                'file_reference' => [
                    'placeholder' => true,
                    'format' => $exportDefinition->exportFormat,
                    'path' => sprintf('reports/%s/%s.%s', $definition->moduleKey, $definition->reportKey, $exportDefinition->exportFormat),
                ],
                'metadata' => array_merge($exportDefinition->metadata, ['placeholder' => true]),
            ]);

            if (
                $context !== null
                && filter_var($exportDefinition->metadata['store_as_document'] ?? false, FILTER_VALIDATE_BOOL)
            ) {
                $document = $this->documentDevelopmentService->createPlaceholder(
                    $context,
                    sprintf('%s.%s export', $definition->moduleKey, $definition->reportKey),
                    $definition->moduleKey,
                    [
                        'report_key' => $definition->reportKey,
                        'export_format' => $exportDefinition->exportFormat,
                        'source' => 'report_export',
                    ],
                );

                $export->file_reference = array_merge($export->file_reference ?? [], [
                    'document_public_id' => $document->publicId,
                    'placeholder' => true,
                ]);
                $export->metadata = array_merge($export->metadata ?? [], [
                    'document_public_id' => $document->publicId,
                ]);
            }

            $export->save();

            $this->auditRecorder->recordExportCompleted($definition, $export->public_id);

            try {
                if (app()->bound(\App\Support\Tenant\TenantContext::class)) {
                    app(\App\Services\Notification\NotificationReportBridge::class)->notifyExportReadyBestEffort(
                        app(\App\Support\Tenant\TenantContext::class),
                        $definition->moduleKey,
                        $definition->reportKey,
                        $export->public_id,
                    );
                }
            } catch (\Throwable) {
            }

            return DynamicReportMapper::toExportResult($export->fresh());
        });
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function requestExport(
        string $moduleKey,
        string $reportKey,
        string $exportFormat,
        array $parameters = [],
    ): ReportExportResult {
        $definition = $this->registryService->find($moduleKey, $reportKey);

        if ($definition === null) {
            throw new ReportNotFoundException(sprintf('Report [%s.%s] was not found.', $moduleKey, $reportKey));
        }

        try {
            return $this->export($definition, ReportExportDefinition::fromArray([
                'export_format' => $exportFormat,
                'parameters' => $parameters,
            ]));
        } catch (\Throwable $exception) {
            $this->auditRecorder->recordExportFailed($definition, $exportFormat);
            throw $exception;
        }
    }

    public function findByPublicId(string $publicId): ?ReportExportResult
    {
        $export = ReportExportModel::query()->where('public_id', $publicId)->first();

        return $export === null ? null : DynamicReportMapper::toExportResult($export);
    }

    /**
     * @return list<ReportExportResult>
     */
    public function listForReport(string $moduleKey, string $reportKey): array
    {
        $model = $this->registryService->findModel($moduleKey, $reportKey);

        if ($model === null) {
            return [];
        }

        return ReportExportModel::query()
            ->where('report_definition_id', $model->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReportExportModel $export) => DynamicReportMapper::toExportResult($export))
            ->all();
    }

    private function assertValidFormat(string $format): void
    {
        $valid = array_map(fn (ReportExportFormat $f) => $f->value, ReportExportFormat::cases());

        if (! in_array($format, $valid, true)) {
            throw new ReportExportException(sprintf('Unsupported export format [%s].', $format));
        }
    }
}
