<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationEventSourceType: string
{
    case Module = 'module';
    case Workflow = 'workflow';
    case Rule = 'rule';
    case Notification = 'notification';
    case Document = 'document';
    case Data = 'data';
    case Integration = 'integration';
    case System = 'system';
}
