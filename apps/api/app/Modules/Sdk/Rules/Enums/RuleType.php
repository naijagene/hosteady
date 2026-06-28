<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleType: string
{
    case Validation = 'validation';
    case Calculation = 'calculation';
    case Visibility = 'visibility';
    case Requirement = 'requirement';
    case Notification = 'notification';
    case WorkflowDecision = 'workflow_decision';
    case ApprovalPolicy = 'approval_policy';
    case DocumentPolicy = 'document_policy';
    case RetentionPolicy = 'retention_policy';
    case Automation = 'automation';
    case Custom = 'custom';
}
