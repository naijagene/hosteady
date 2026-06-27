<?php

namespace App\Modules\Sdk\Entity\Enums;

enum EntityLifecycleEventType: string
{
    case Creating = 'creating';
    case Created = 'created';
    case Updating = 'updating';
    case Updated = 'updated';
    case Deleting = 'deleting';
    case Deleted = 'deleted';
    case Restoring = 'restoring';
    case Restored = 'restored';
    case Commented = 'commented';
    case Tagged = 'tagged';
    case Untagged = 'untagged';
    case Attached = 'attached';
    case Detached = 'detached';
}
