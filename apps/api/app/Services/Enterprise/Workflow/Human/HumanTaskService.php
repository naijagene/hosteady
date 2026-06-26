<?php

namespace App\Services\Enterprise\Workflow\Human;

use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Human\Contracts\HumanTaskPort;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use App\Modules\Sdk\Workflow\Human\Data\TaskComment;
use App\Modules\Sdk\Workflow\Human\Data\TaskStatistics;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class HumanTaskService
{
    public function __construct(
        private readonly HumanTaskPort $humanTaskPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly HumanTaskIntegrations $integrations,
        private readonly WorkflowHumanTaskCompletionHandler $completionHandler,
    ) {
    }

    /**
     * @return list<HumanTaskReference>
     */
    public function list(TenantContext $context, ?string $status = null): array
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->list($this->scope($context), $status);
    }

    public function show(TenantContext $context, string $publicId): HumanTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->get($this->scope($context), $publicId);
    }

    public function open(TenantContext $context, string $publicId): HumanTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertManagePermission($context);

        $reference = $this->humanTaskPort->open(
            $this->scope($context),
            $publicId,
            $context->user->id,
            $context->membership->id,
        );

        $this->integrate($context, $publicId);

        return $reference;
    }

    public function complete(TenantContext $context, string $publicId, ?array $result = null): HumanTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertManagePermission($context);

        $reference = $this->humanTaskPort->complete(
            $this->scope($context),
            $publicId,
            $context->user->id,
            $context->membership->id,
            $result,
        );

        $this->completionHandler->afterCompleted($reference);
        $this->integrate($context, $publicId);

        return $reference;
    }

    public function cancel(TenantContext $context, string $publicId): HumanTaskReference
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertManagePermission($context);

        $reference = $this->humanTaskPort->cancel(
            $this->scope($context),
            $publicId,
            $context->user->id,
            $context->membership->id,
        );

        $this->integrate($context, $publicId);

        return $reference;
    }

    public function addComment(TenantContext $context, string $publicId, string $body): TaskComment
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->addComment(
            $this->scope($context),
            $publicId,
            $body,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @return list<TaskComment>
     */
    public function listComments(TenantContext $context, string $publicId): array
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->listComments($this->scope($context), $publicId);
    }

    /**
     * @return list<\App\Modules\Sdk\Workflow\Human\Data\TaskHistory>
     */
    public function history(TenantContext $context, string $publicId): array
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->history($this->scope($context), $publicId);
    }

    public function statistics(TenantContext $context): TaskStatistics
    {
        $this->runtimeBridge->requireCapability($context, 'human_tasks');
        $this->assertReadPermission($context);

        return $this->humanTaskPort->statistics($this->scope($context));
    }

    private function integrate(TenantContext $context, string $taskPublicId): void
    {
        $task = \App\Models\WorkflowHumanTask::query()
            ->with('workflowInstance.definition')
            ->where('public_id', $taskPublicId)
            ->first();

        if ($task === null) {
            return;
        }

        $this->integrations->indexTaskBestEffort($context, $task);
        $this->integrations->notifyTaskEventBestEffort($context, 'human_task.updated', $task);
    }

    private function scope(TenantContext $context): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'task.read')) {
            abort(403, 'You are not allowed to read tasks.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'task.manage')) {
            abort(403, 'You are not allowed to manage tasks.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
