<?php

namespace App\Modules\Sdk\Lifecycle;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRegistryEventDispatcher;
use App\Modules\Sdk\Data\LifecycleExecution;
use App\Modules\Sdk\Data\ModuleLifecycleContext;
use App\Modules\Sdk\Events\ModuleRegistryEvent;

class ModuleLifecycleDispatcher
{
    public function __construct(
        private readonly ModuleRegistryEventDispatcher $events,
    ) {
    }

    public function execute(
        ApplicationModule $module,
        LifecycleOperation $operation,
        ModuleLifecycleContext $context,
        bool $bestEffort = false,
    ): LifecycleExecution {
        $startedAt = microtime(true);
        $payload = [
            'module' => $module,
            'operation' => $operation->value,
            'context' => $context,
        ];

        $this->events->dispatch(ModuleRegistryEvent::BEFORE_LIFECYCLE, $payload);

        if ($operation->beforeEvent() !== null) {
            $this->events->dispatch($operation->beforeEvent(), $payload);
        }

        $exceptionMessage = null;
        $status = 'success';

        try {
            $this->invokeModuleHook($module, $operation, $context);
        } catch (\Throwable $exception) {
            $exceptionMessage = $exception->getMessage();
            $status = 'failed';

            if ($bestEffort) {
                $status = 'warning';
            } else {
                throw $exception;
            }
        }

        if ($operation->afterEvent() !== null) {
            $this->events->dispatch($operation->afterEvent(), $payload);
        }

        $this->events->dispatch(ModuleRegistryEvent::AFTER_LIFECYCLE, $payload);

        $finishedAt = microtime(true);

        return new LifecycleExecution(
            moduleKey: $module->key(),
            operation: $operation,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            durationMs: round(($finishedAt - $startedAt) * 1000, 3),
            status: $status,
            exceptionMessage: $exceptionMessage,
        );
    }

    private function invokeModuleHook(
        ApplicationModule $module,
        LifecycleOperation $operation,
        ModuleLifecycleContext $context,
    ): void {
        match ($operation) {
            LifecycleOperation::Install => $module->onInstall($context),
            LifecycleOperation::Uninstall => $module->onUninstall($context),
            LifecycleOperation::EnableWorkspace => $module->onWorkspaceEnable($context),
            LifecycleOperation::DisableWorkspace => $module->onWorkspaceDisable($context),
            LifecycleOperation::SettingsUpdated => $module->onSettingsUpdated($context),
            LifecycleOperation::BeforeRuntimeResolved => $module->beforeRuntimeResolved($context),
            LifecycleOperation::AfterRuntimeResolved => $module->afterRuntimeResolved($context),
        };
    }
}
