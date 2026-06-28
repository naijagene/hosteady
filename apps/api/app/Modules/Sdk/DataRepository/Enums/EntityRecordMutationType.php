<?php

namespace App\Modules\Sdk\DataRepository\Enums;

enum EntityRecordMutationType: string
{
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Restore = 'restore';
}