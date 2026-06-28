<?php

namespace App\Modules\Sdk\Integration\Enums;

enum IntegrationTransformType: string
{
    case PassThrough = 'pass_through';
    case FieldMapping = 'field_mapping';
    case TemplateMapping = 'template_mapping';
    case StaticMapping = 'static_mapping';
}
