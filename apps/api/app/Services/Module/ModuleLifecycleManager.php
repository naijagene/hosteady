<?php

namespace App\Services\Module;

use App\Enums\ApplicationStatus;
use App\Enums\OrganizationApplicationStatus;
use App\Enums\WorkspaceApplicationStatus;
use App\Models\WorkspaceApplication;
use App\Modules\Sdk\Data\LifecycleResult;
use App\Modules\Sdk\Data\ModuleLifecycleContext;
use App\Modules\Sdk\Exceptions\LifecycleException;
use App\Modules\Sdk\Lifecycle\LifecycleOperation;
use App\Modules\Sdk\Lifecycle\ModuleLifecycleDispatcher;
use App\Modules\Sdk\ModuleRegistry;
use App\Services\Audit\ModuleLifecycleAuditRecorder;
use App\Services\Runtime\RuntimeCacheInvalidator;
use App\Services\WorkspaceApplication\Data\WorkspaceRuntimeContext;
use App\Support\Tenant\TenantContext;

class ModuleLifecycleManager
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleLifecycleDispatcher $dispatcher,
        private readonly ModuleLifecycleAuditRecorder $auditRecorder,
        private readonly RuntimeCacheInvalidator $runtimeCacheInvalidator,
    ) {
    }

    public function install(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->runTransactional(LifecycleOperation::Install, $context, $moduleKey);
    }

    public function uninstall(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->runTransactional(LifecycleOperation::Uninstall, $context, $moduleKey);
    }

    public function enableWorkspace(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->runTransactional(LifecycleOperation::EnableWorkspace, $context, $moduleKey);
    }

    public function disableWorkspace(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->runTransactional(LifecycleOperation::DisableWorkspace, $context, $moduleKey);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function settingsUpdated(TenantContext $context, string $moduleKey, array $metadata = []): LifecycleResult
    {
        return $this->runTransactional(LifecycleOperation::SettingsUpdated, $context, $moduleKey, $metadata);
    }

    /**
     * @return array{runtime: WorkspaceRuntimeContext, results: list<LifecycleResult>}
     */
    public function runtimeResolved(TenantContext $context, callable $generator): array
    {
        $results = [];
        $moduleKeys = $this->activeModuleKeys($context);

        foreach ($moduleKeys as $moduleKey) {
            $results[] = $this->runRuntimePhase(
                LifecycleOperation::BeforeRuntimeResolved,
                $context,
                $moduleKey,
            );
        }

        $runtime = $generator();

        foreach ($moduleKeys as $moduleKey) {
            $results[] = $this->runRuntimePhase(
                LifecycleOperation::AfterRuntimeResolved,
                $context,
                $moduleKey,
                ['runtime_version' => $runtime->runtimeVersion],
            );
        }

        return [
            'runtime' => $runtime,
            'results' => $results,
        ];
    }

    /**
     * @throws LifecycleException
     */
    public function runInstallHooks(TenantContext $context, string $moduleKey): void
    {
        $this->runHooks(LifecycleOperation::Install, $context, $moduleKey);
    }

    public function completeInstall(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->finalize(LifecycleOperation::Install, $context, $moduleKey);
    }

    /**
     * @throws LifecycleException
     */
    public function runUninstallHooks(TenantContext $context, string $moduleKey): void
    {
        $this->runHooks(LifecycleOperation::Uninstall, $context, $moduleKey);
    }

    public function completeUninstall(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->finalize(LifecycleOperation::Uninstall, $context, $moduleKey);
    }

    /**
     * @throws LifecycleException
     */
    public function runEnableWorkspaceHooks(TenantContext $context, string $moduleKey): void
    {
        $this->runHooks(LifecycleOperation::EnableWorkspace, $context, $moduleKey);
    }

    public function completeEnableWorkspace(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->finalize(LifecycleOperation::EnableWorkspace, $context, $moduleKey);
    }

    /**
     * @throws LifecycleException
     */
    public function runDisableWorkspaceHooks(TenantContext $context, string $moduleKey): void
    {
        $this->runHooks(LifecycleOperation::DisableWorkspace, $context, $moduleKey);
    }

    public function completeDisableWorkspace(TenantContext $context, string $moduleKey): LifecycleResult
    {
        return $this->finalize(LifecycleOperation::DisableWorkspace, $context, $moduleKey);
    }

    /**
     * @param  array<string, mixed>  $metadata
     *
     * @throws LifecycleException
     */
    public function runSettingsUpdatedHooks(TenantContext $context, string $moduleKey, array $metadata = []): void
    {
        $this->runHooks(LifecycleOperation::SettingsUpdated, $context, $moduleKey, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function completeSettingsUpdated(TenantContext $context, string $moduleKey, array $metadata = []): LifecycleResult
    {
        return $this->finalize(LifecycleOperation::SettingsUpdated, $context, $moduleKey, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     *
     * @throws LifecycleException
     */
    private function runHooks(
        LifecycleOperation $operation,
        TenantContext $context,
        string $moduleKey,
        array $metadata = [],
    ): void {
        $module = $this->registry->findByKey($moduleKey);

        if ($module === null) {
            return;
        }

        $lifecycleContext = $this->buildContext($context, $moduleKey, $metadata);

        try {
            $this->dispatcher->execute($module, $operation, $lifecycleContext);
        } catch (\Throwable $exception) {
            throw new LifecycleException(
                LifecycleResult::failed($moduleKey, $operation, $exception),
                $exception,
            );
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function runTransactional(
        LifecycleOperation $operation,
        TenantContext $context,
        string $moduleKey,
        array $metadata = [],
    ): LifecycleResult {
        $this->runHooks($operation, $context, $moduleKey, $metadata);

        return $this->finalize($operation, $context, $moduleKey, $metadata);
    }

    private function runRuntimePhase(
        LifecycleOperation $operation,
        TenantContext $context,
        string $moduleKey,
        array $metadata = [],
    ): LifecycleResult {
        $module = $this->registry->findByKey($moduleKey);

        if ($module === null) {
            return LifecycleResult::skipped($moduleKey, $operation);
        }

        $lifecycleContext = $this->buildContext($context, $moduleKey, $metadata);
        $warnings = [];

        try {
            $execution = $this->dispatcher->execute(
                module: $module,
                operation: $operation,
                context: $lifecycleContext,
                bestEffort: true,
            );

            if ($execution->exceptionMessage !== null) {
                $warnings[] = $execution->exceptionMessage;
            }
        } catch (\Throwable $exception) {
            $warnings[] = $exception->getMessage();

            return LifecycleResult::failed($moduleKey, $operation, $exception);
        }

        $this->recordAuditBestEffort($operation, $context, $moduleKey, $metadata);

        return LifecycleResult::success($moduleKey, $operation, $execution, $warnings);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function finalize(
        LifecycleOperation $operation,
        TenantContext $context,
        string $moduleKey,
        array $metadata = [],
    ): LifecycleResult {
        if ($this->registry->findByKey($moduleKey) === null) {
            return LifecycleResult::skipped($moduleKey, $operation);
        }

        $this->recordAuditBestEffort($operation, $context, $moduleKey, $metadata);
        $this->invalidateRuntimeBestEffort($context, $operation, $moduleKey);

        return LifecycleResult::success(
            moduleKey: $moduleKey,
            operation: $operation,
            execution: new \App\Modules\Sdk\Data\LifecycleExecution(
                moduleKey: $moduleKey,
                operation: $operation,
                startedAt: microtime(true),
                finishedAt: microtime(true),
                durationMs: 0.0,
                status: 'success',
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function buildContext(
        TenantContext $context,
        string $moduleKey,
        array $metadata = [],
    ): ModuleLifecycleContext {
        return new ModuleLifecycleContext(
            moduleKey: $moduleKey,
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            applicationKey: $moduleKey,
            applicationPublicId: $metadata['application_public_id'] ?? null,
            metadata: $metadata,
        );
    }

    /**
     * @return list<string>
     */
    private function activeModuleKeys(TenantContext $context): array
    {
        $keys = WorkspaceApplication::query()
            ->with('application')
            ->where('workspace_id', $context->workspace->id)
            ->where('organization_id', $context->organization->id)
            ->where('status', WorkspaceApplicationStatus::Active)
            ->whereNull('deleted_at')
            ->whereHas('organizationApplication', fn ($query) => $query
                ->where('status', OrganizationApplicationStatus::Active)
                ->whereNull('deleted_at'))
            ->whereHas('application', fn ($query) => $query->where('status', ApplicationStatus::Active))
            ->get()
            ->pluck('application.key')
            ->filter(fn (?string $key) => is_string($key) && $key !== '')
            ->unique()
            ->values()
            ->all();

        sort($keys);

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordAuditBestEffort(
        LifecycleOperation $operation,
        TenantContext $context,
        string $moduleKey,
        array $metadata = [],
    ): void {
        try {
            match ($operation) {
                LifecycleOperation::Install => $this->auditRecorder->recordInstallCompleted($context, $moduleKey),
                LifecycleOperation::Uninstall => $this->auditRecorder->recordUninstallCompleted($context, $moduleKey),
                LifecycleOperation::EnableWorkspace => $this->auditRecorder->recordWorkspaceEnabled($context, $moduleKey),
                LifecycleOperation::DisableWorkspace => $this->auditRecorder->recordWorkspaceDisabled($context, $moduleKey),
                LifecycleOperation::SettingsUpdated => $this->auditRecorder->recordSettingsUpdated($context, $moduleKey, $metadata),
                LifecycleOperation::BeforeRuntimeResolved => $this->auditRecorder->recordRuntimeBefore($context, $moduleKey, $metadata),
                LifecycleOperation::AfterRuntimeResolved => $this->auditRecorder->recordRuntimeAfter($context, $moduleKey, $metadata),
            };
        } catch (\Throwable) {
            // Audit failures must never stop lifecycle.
        }
    }

    private function invalidateRuntimeBestEffort(
        TenantContext $context,
        LifecycleOperation $operation,
        string $moduleKey,
    ): void {
        if ($operation->isRuntimePhase()) {
            return;
        }

        try {
            $this->runtimeCacheInvalidator->invalidateTenantContext($context);
        } catch (\Throwable) {
            // Cache invalidation is best effort.
        }
    }
}
