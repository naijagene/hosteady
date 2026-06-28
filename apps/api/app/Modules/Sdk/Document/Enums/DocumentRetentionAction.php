<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentRetentionAction: string
{
    case None = 'none';
    case Archive = 'archive';
    case Delete = 'delete';
    case LegalHold = 'legal_hold';
}
