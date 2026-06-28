<?php

namespace App\Modules\Sdk\Notification\Enums;

enum NotificationTemplateType: string
{
    case System = 'system';
    case Module = 'module';
    case Custom = 'custom';
    case Digest = 'digest';
}
