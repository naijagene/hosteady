<?php

namespace App\Modules\Sdk\Table\Enums;

enum TableExportFormat: string
{
    case Csv = 'csv';
    case Json = 'json';
    case Xlsx = 'xlsx';
}
