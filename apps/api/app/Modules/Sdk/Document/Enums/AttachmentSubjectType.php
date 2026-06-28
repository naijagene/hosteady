<?php

namespace App\Modules\Sdk\Document\Enums;

enum AttachmentSubjectType: string
{
    case EntityRecord = 'entity_record';
    case EntityDefinition = 'entity_definition';
    case WorkflowInstance = 'workflow_instance';
    case WorkflowTask = 'workflow_task';
    case WorkflowDefinition = 'workflow_definition';
    case ReportRun = 'report_run';
    case ReportExport = 'report_export';
    case Dashboard = 'dashboard';
    case FormSubmission = 'form_submission';
    case Generic = 'generic';
}
