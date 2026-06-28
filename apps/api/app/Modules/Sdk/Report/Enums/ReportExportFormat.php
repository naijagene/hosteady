<?php

namespace App\Modules\Sdk\Report\Enums;

enum ReportExportFormat: string
{
    case Pdf = 'pdf';
    case Excel = 'excel';
    case Csv = 'csv';
    case Json = 'json';
}
