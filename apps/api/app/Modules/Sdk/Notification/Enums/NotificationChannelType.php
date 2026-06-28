<?php

namespace App\Modules\Sdk\Notification\Enums;

enum NotificationChannelType: string
{
    case Email = 'email';
    case Sms = 'sms';
    case Push = 'push';
    case InApp = 'in_app';
    case Whatsapp = 'whatsapp';
    case Slack = 'slack';
    case Teams = 'teams';
    case Webhook = 'webhook';
}
