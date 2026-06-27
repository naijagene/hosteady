<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormFieldType: string
{
    case String = 'string';
    case Text = 'text';
    case Textarea = 'textarea';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case Datetime = 'datetime';
    case Time = 'time';
    case Json = 'json';
    case Uuid = 'uuid';
    case Enum = 'enum';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Checkbox = 'checkbox';
    case Radio = 'radio';
    case File = 'file';
    case Hidden = 'hidden';
    case Password = 'password';
    case Email = 'email';
    case Url = 'url';
    case Phone = 'phone';
    case Reference = 'reference';
    case Group = 'group';
}
