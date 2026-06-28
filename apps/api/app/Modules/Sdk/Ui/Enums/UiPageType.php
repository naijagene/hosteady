<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiPageType: string
{
    case ModuleHome = 'module_home';
    case EntityList = 'entity_list';
    case EntityDetail = 'entity_detail';
    case EntityCreate = 'entity_create';
    case EntityEdit = 'entity_edit';
    case Dashboard = 'dashboard';
    case Report = 'report';
    case Workflow = 'workflow';
    case Document = 'document';
    case Settings = 'settings';
    case Custom = 'custom';
}
