<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationEndpointType: string
{
    case InternalHandler = 'internal_handler';
    case OutboundWebhook = 'outbound_webhook';
    case InboundWebhook = 'inbound_webhook';
    case NotificationChannel = 'notification_channel';
    case WorkflowTrigger = 'workflow_trigger';
    case RuleAction = 'rule_action';
    case DocumentAction = 'document_action';
    case DataAction = 'data_action';
    case Custom = 'custom';
}
