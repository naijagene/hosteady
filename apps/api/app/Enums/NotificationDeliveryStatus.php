<?php

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case Delivered = 'delivered';
    case Skipped = 'skipped';
    case Failed = 'failed';
}
