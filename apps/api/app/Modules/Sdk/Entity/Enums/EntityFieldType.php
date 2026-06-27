<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityFieldType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case Datetime = 'datetime';
    case Json = 'json';
    case Uuid = 'uuid';
    case Enum = 'enum';
    case Reference = 'reference';
}
