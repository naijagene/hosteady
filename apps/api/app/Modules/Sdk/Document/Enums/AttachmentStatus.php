<?php

namespace App\Modules\Sdk\Document\Enums;

enum AttachmentStatus: string
{
    case Active = 'active';
    case Detached = 'detached';
    case Archived = 'archived';
}
