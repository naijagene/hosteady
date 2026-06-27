<?php

namespace App\Modules\Sdk\Table\Enums;

enum TableType: string
{
    case List = 'list';
    case Detail = 'detail';
    case Custom = 'custom';
}
