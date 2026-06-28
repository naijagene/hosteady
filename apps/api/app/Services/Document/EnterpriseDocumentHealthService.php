<?php

namespace App\Services\Document;

use App\Modules\Sdk\Document\Data\DocumentHealthReport;
use App\Services\Enterprise\Support\EnterpriseTableHealthGuard;
use App\Support\Tenant\TenantContext;

class EnterpriseDocumentHealthService
{
    /**
     * @var list<string>
     */
    private const REQUIRED_TABLES = [
        'enterprise_documents',
        'enterprise_document_versions',
        'enterprise_attachments',
        'enterprise_document_previews',
        'enterprise_document_thumbnails',
        'enterprise_document_scans',
        'enterprise_document_ocr_results',
        'enterprise_document_activity',
    ];

    public function __construct(
        private readonly EnterpriseTableHealthGuard $tableGuard,
        private readonly EnterpriseDocumentStatisticsService $statisticsService,
    ) {
    }

    public function health(?TenantContext $context = null): DocumentHealthReport
    {
        return $this->healthReport($context);
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $enabled = (bool) config('heos.enterprise.documents.enabled', true);

        return $this->tableGuard->assessWhenTablesPresent(
            self::REQUIRED_TABLES,
            fn (): array => $this->assessWithTables($context, $enabled),
            $this->fallbackAssessment($enabled),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );

        return [
            'enabled' => (bool) config('heos.enterprise.documents.enabled', true),
            'documents' => $stats->documents,
            'versions' => $stats->versions,
            'attachments' => $stats->attachments,
            'previews' => $stats->previews,
            'scans' => $stats->scans,
            'ocr_results' => $stats->ocrResults,
            'activity_logs' => $stats->activityLogs,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function assessWithTables(?TenantContext $context, bool $enabled): array
    {
        $report = $this->healthReport($context);
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $report->enabled,
            'documents' => $report->documents,
            'versions' => $report->versions,
            'attachments' => $report->attachments,
            'warnings' => $report->warnings,
            'status' => $report->status,
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackAssessment(bool $enabled): array
    {
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);

        $assessment = [
            'enabled' => $enabled,
            'documents' => 0,
            'versions' => 0,
            'attachments' => 0,
            'warnings' => array_map(
                fn (string $table): string => $this->tableGuard->missingTableWarning($table),
                $missingTables,
            ),
            'status' => 'warning',
        ];

        if ($missingTables !== []) {
            $assessment['missing_tables'] = $missingTables;
        }

        return $assessment;
    }

    private function healthReport(?TenantContext $context): DocumentHealthReport
    {
        $enabled = (bool) config('heos.enterprise.documents.enabled', true);
        $stats = $this->statisticsService->statisticsForScope(
            $context?->organization,
            $context?->workspace,
        );
        $missingTables = $this->tableGuard->missingTables(self::REQUIRED_TABLES);
        $warnings = array_map(
            fn (string $table): string => $this->tableGuard->missingTableWarning($table),
            $missingTables,
        );
        $status = 'healthy';

        if (! $enabled) {
            $warnings[] = 'Enterprise document engine is disabled in configuration.';
            $status = 'warning';
        }

        if ($enabled && $missingTables === [] && $stats->documents === 0) {
            $warnings[] = 'No documents are stored yet.';
            $status = 'warning';
        }

        if ($missingTables !== []) {
            $status = 'warning';
        }

        return new DocumentHealthReport(
            enabled: $enabled,
            documents: $stats->documents,
            versions: $stats->versions,
            attachments: $stats->attachments,
            warnings: $warnings,
            status: $status,
            missingTables: $missingTables,
        );
    }
}
