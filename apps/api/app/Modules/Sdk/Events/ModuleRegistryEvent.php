<?php

namespace App\Modules\Sdk\Events;

final class ModuleRegistryEvent
{
    public const BEFORE_REGISTER = 'beforeRegister';

    public const AFTER_REGISTER = 'afterRegister';

    public const BEFORE_SYNC = 'beforeSync';

    public const AFTER_SYNC = 'afterSync';

    public const BEFORE_VALIDATE = 'beforeValidate';

    public const AFTER_VALIDATE = 'afterValidate';

    public const BEFORE_RUNTIME_RESOLVED = 'beforeRuntimeResolved';

    public const AFTER_RUNTIME_RESOLVED = 'afterRuntimeResolved';

    private function __construct()
    {
    }
}
