<?php

namespace App\Enums;

enum FileVisibility: string
{
    case Private = 'private';
    case Workspace = 'workspace';
    case Organization = 'organization';
    case Public = 'public';
}
