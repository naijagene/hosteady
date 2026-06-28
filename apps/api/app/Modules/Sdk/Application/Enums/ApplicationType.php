<?php

namespace App\Modules\Sdk\Application\Enums;

enum ApplicationType: string
{
    case Core = 'core';
    case Business = 'business';
    case Custom = 'custom';
    case Module = 'module';
}
