<?php

namespace App\Enums;

enum SearchVisibility: string
{
    case Private = 'private';
    case Workspace = 'workspace';
    case Organization = 'organization';
}
