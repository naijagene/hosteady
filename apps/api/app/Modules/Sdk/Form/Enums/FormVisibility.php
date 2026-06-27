<?php

namespace App\Modules\Sdk\Form\Enums;

enum FormVisibility: string
{
    case Private = 'private';
    case Organization = 'organization';
    case Workspace = 'workspace';
    case Public = 'public';
}
