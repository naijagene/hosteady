<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormLayoutType: string
{
    case Default = 'default';
    case Tabs = 'tabs';
    case Sections = 'sections';
    case Wizard = 'wizard';
    case Inline = 'inline';
    case Grid = 'grid';
}
