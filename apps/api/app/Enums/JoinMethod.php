<?php

namespace App\Enums;

enum JoinMethod: string
{
    case Invitation = 'invitation';
    case SelfRegister = 'self_register';
    case System = 'system';
}
