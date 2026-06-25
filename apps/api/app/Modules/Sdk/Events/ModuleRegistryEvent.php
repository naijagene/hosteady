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

    public const BEFORE_LIFECYCLE = 'beforeLifecycle';

    public const AFTER_LIFECYCLE = 'afterLifecycle';

    public const BEFORE_INSTALL = 'beforeInstall';

    public const AFTER_INSTALL = 'afterInstall';

    public const BEFORE_UNINSTALL = 'beforeUninstall';

    public const AFTER_UNINSTALL = 'afterUninstall';

    public const BEFORE_WORKSPACE_ENABLE = 'beforeWorkspaceEnable';

    public const AFTER_WORKSPACE_ENABLE = 'afterWorkspaceEnable';

    public const BEFORE_WORKSPACE_DISABLE = 'beforeWorkspaceDisable';

    public const AFTER_WORKSPACE_DISABLE = 'afterWorkspaceDisable';

    public const BEFORE_SETTINGS_UPDATED = 'beforeSettingsUpdated';

    public const AFTER_SETTINGS_UPDATED = 'afterSettingsUpdated';

    private function __construct()
    {
    }
}
