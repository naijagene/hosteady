<?php

namespace App\Modules\Sdk\Rules\Enums;

enum RuleActionType: string
{
    case AddViolation = 'add_violation';
    case SetValue = 'set_value';
    case CalculateValue = 'calculate_value';
    case RequireField = 'require_field';
    case ShowField = 'show_field';
    case HideField = 'hide_field';
    case SendNotification = 'send_notification';
    case StartWorkflow = 'start_workflow';
    case CreateTask = 'create_task';
    case Approve = 'approve';
    case Reject = 'reject';
    case AttachDocument = 'attach_document';
    case TagRecord = 'tag_record';
    case EmitEvent = 'emit_event';
    case Noop = 'noop';
}
