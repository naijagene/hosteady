<?php

namespace App\Modules\Sdk\Ui\Enums;

enum UiBindingType: string
{
    case Form = 'form';
    case Table = 'table';
    case Dashboard = 'dashboard';
    case Report = 'report';
    case Entity = 'entity';
    case Workflow = 'workflow';
    case Document = 'document';
    case Notification = 'notification';
    case Static = 'static';
    case Custom = 'custom';
}
