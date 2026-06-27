<?php

namespace App\Modules\Sdk\Table\Enums;

enum TableColumnType: string
{
    case Text = 'text';
    case Number = 'number';
    case Boolean = 'boolean';
    case Date = 'date';
    case Datetime = 'datetime';
    case Enum = 'enum';
    case Reference = 'reference';
    case Json = 'json';
    case Uuid = 'uuid';
    case Display = 'display';
}
