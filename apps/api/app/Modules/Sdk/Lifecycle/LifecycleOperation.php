<?php

namespace App\Modules\Sdk\Lifecycle;

use App\Modules\Sdk\Events\ModuleRegistryEvent;

enum LifecycleOperation: string
{
    case Install = 'install';
    case Uninstall = 'uninstall';
    case EnableWorkspace = 'enable_workspace';
    case DisableWorkspace = 'disable_workspace';
    case SettingsUpdated = 'settings_updated';
    case BeforeRuntimeResolved = 'before_runtime_resolved';
    case AfterRuntimeResolved = 'after_runtime_resolved';

    public function beforeEvent(): ?string
    {
        return match ($this) {
            self::Install => ModuleRegistryEvent::BEFORE_INSTALL,
            self::Uninstall => ModuleRegistryEvent::BEFORE_UNINSTALL,
            self::EnableWorkspace => ModuleRegistryEvent::BEFORE_WORKSPACE_ENABLE,
            self::DisableWorkspace => ModuleRegistryEvent::BEFORE_WORKSPACE_DISABLE,
            self::SettingsUpdated => ModuleRegistryEvent::BEFORE_SETTINGS_UPDATED,
            self::BeforeRuntimeResolved => ModuleRegistryEvent::BEFORE_RUNTIME_RESOLVED,
            self::AfterRuntimeResolved => null,
        };
    }

    public function afterEvent(): ?string
    {
        return match ($this) {
            self::Install => ModuleRegistryEvent::AFTER_INSTALL,
            self::Uninstall => ModuleRegistryEvent::AFTER_UNINSTALL,
            self::EnableWorkspace => ModuleRegistryEvent::AFTER_WORKSPACE_ENABLE,
            self::DisableWorkspace => ModuleRegistryEvent::AFTER_WORKSPACE_DISABLE,
            self::SettingsUpdated => ModuleRegistryEvent::AFTER_SETTINGS_UPDATED,
            self::BeforeRuntimeResolved => null,
            self::AfterRuntimeResolved => ModuleRegistryEvent::AFTER_RUNTIME_RESOLVED,
        };
    }

    public function moduleMethod(): string
    {
        return match ($this) {
            self::Install => 'onInstall',
            self::Uninstall => 'onUninstall',
            self::EnableWorkspace => 'onWorkspaceEnable',
            self::DisableWorkspace => 'onWorkspaceDisable',
            self::SettingsUpdated => 'onSettingsUpdated',
            self::BeforeRuntimeResolved => 'beforeRuntimeResolved',
            self::AfterRuntimeResolved => 'afterRuntimeResolved',
        };
    }

    public function isRuntimePhase(): bool
    {
        return in_array($this, [self::BeforeRuntimeResolved, self::AfterRuntimeResolved], true);
    }
}
