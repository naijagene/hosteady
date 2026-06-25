<?php

namespace App\Services\Module;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Data\ModuleValidationReport;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Services\Module\Data\ModuleDoctorReport;
use App\Services\Module\Data\ModuleDocumentationResult;

class ModuleDeveloperAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordValidationExecuted(ModuleValidationReport $report): void
    {
        $this->recordPlatformEvent(
            action: AuditAction::ModuleValidationExecuted,
            summary: sprintf(
                'Module validation executed (%d issue(s))',
                count($report->issues),
            ),
            metadata: [
                'valid' => $report->isValid(),
                'issue_count' => count($report->issues),
            ],
        );
    }

    public function recordDoctorExecuted(ModuleDoctorReport $report): void
    {
        $this->recordPlatformEvent(
            action: AuditAction::ModuleDoctorExecuted,
            summary: sprintf(
                'Module doctor executed (exit code %d)',
                $report->exitCode,
            ),
            metadata: [
                'exit_code' => $report->exitCode,
                'error_count' => count($report->errors),
                'warning_count' => count($report->warnings),
                'module_count' => count($report->modules),
            ],
        );
    }

    public function recordDocumentationGenerated(ModuleDocumentationResult $result): void
    {
        $this->recordPlatformEvent(
            action: AuditAction::ModuleDocumentationGenerated,
            summary: sprintf(
                'Module documentation generated for %d module(s)',
                $result->moduleCount,
            ),
            metadata: [
                'output_directory' => $result->outputDirectory,
                'generated_file_count' => count($result->generatedFiles),
                'module_count' => $result->moduleCount,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordPlatformEvent(
        AuditAction $action,
        string $summary,
        array $metadata = [],
    ): void {
        try {
            $this->auditEventRecorder->record(new AuditEventData(
                action: $action,
                summary: $summary,
                scope: AuditScope::Platform,
                entityType: AuditEntityType::Application,
                entityPublicId: 'heos-platform',
                entityLabel: 'HEOS Platform',
                metadata: $metadata,
                actorType: AuditActorType::System,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
            // Audit failures must never stop developer tooling.
        }
    }
}
