<?php

namespace App\Modules\Sdk\Notification\Enums;

enum NotificationScope: string
{
    case User = 'user';
    case Users = 'users';
    case Role = 'role';
    case Workspace = 'workspace';
    case Organization = 'organization';
    case Broadcast = 'broadcast';
}
