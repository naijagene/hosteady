<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormType: string
{
    case Create = 'create';
    case Edit = 'edit';
    case View = 'view';
    case Search = 'search';
    case Filter = 'filter';
    case Wizard = 'wizard';
    case Custom = 'custom';
}
