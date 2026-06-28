<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleTriggerType: string
{
    case Manual = 'manual';
    case EntityCreating = 'entity_creating';
    case EntityCreated = 'entity_created';
    case EntityUpdating = 'entity_updating';
    case EntityUpdated = 'entity_updated';
    case EntityDeleting = 'entity_deleting';
    case EntityDeleted = 'entity_deleted';
    case FormValidating = 'form_validating';
    case FormSubmitted = 'form_submitted';
    case WorkflowStarted = 'workflow_started';
    case WorkflowCompleted = 'workflow_completed';
    case ApprovalRequested = 'approval_requested';
    case ApprovalDecided = 'approval_decided';
    case DocumentUploaded = 'document_uploaded';
    case DocumentScanned = 'document_scanned';
    case ReportCompleted = 'report_completed';
    case Scheduled = 'scheduled';
}
