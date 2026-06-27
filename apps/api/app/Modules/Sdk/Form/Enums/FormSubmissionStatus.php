<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormSubmissionStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';
    case Validated = 'validated';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
