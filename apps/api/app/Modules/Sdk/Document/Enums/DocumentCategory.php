<?php

namespace App\Modules\Sdk\Document\Enums;

enum DocumentCategory: string
{
    case General = 'general';
    case Contract = 'contract';
    case Invoice = 'invoice';
    case Report = 'report';
    case Form = 'form';
    case Attachment = 'attachment';
    case Export = 'export';
    case Other = 'other';
}
