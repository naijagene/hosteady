<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormStatus: string
{
    case Draft = 'draft';
    case Registered = 'registered';
    case Active = 'active';
    case Archived = 'archived';
}
