<?php

namespace App\Enums;

enum AuditScope: string
{
    case Platform = 'platform';
    case Organization = 'organization';
}
