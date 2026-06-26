<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case InApp = 'in_app';
    case LogEmail = 'log_email';
}
