<?php

namespace App\Services\Enterprise\Workflow;

use App\Models\WorkflowDefinition;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Workflow\Contracts\WorkflowPort;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference;
use App\Modules\Sdk\Workflow\Data\WorkflowPublishResult;
use App\Modules\Sdk\Workflow\Data\WorkflowStatistics;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Modules\Sdk\Workflow\Data\WorkflowVersionData;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Support\Tenant\TenantContext;

class WorkflowDefinitionService
{
    public function __construct(
        private readonly WorkflowPort $workflowPort,
        private readonly EnterpriseRuntimeBridge $runtimeBridge,
        private readonly WorkflowSearchIndexer $searchIndexer,
    ) {
    }

    /**
     * @return list<WorkflowDefinitionReference>
     */
    public function list(TenantContext $context, ?string $status = null): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertReadPermission($context);

        return $this->workflowPort->listDefinitions($this->scope($context), $status);
    }

    public function show(TenantContext $context, string $publicId): WorkflowDefinitionReference
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertReadPermission($context);

        return $this->workflowPort->getDefinition($this->scope($context), $publicId);
    }

    public function create(TenantContext $context, WorkflowDefinitionData $data): WorkflowDefinitionReference
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertManagePermission($context);

        $reference = $this->workflowPort->createDefinition(
            $this->scope($context, $data->moduleKey),
            $data,
            $context->user->id,
            $context->membership->id,
        );

        $this->indexBestEffort($context, $reference->publicId);

        return $reference;
    }

    public function update(TenantContext $context, string $publicId, WorkflowDefinitionData $data): WorkflowDefinitionReference
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertManagePermission($context);

        $reference = $this->workflowPort->updateDefinition(
            $this->scope($context, $data->moduleKey),
            $publicId,
            $data,
            $context->user->id,
            $context->membership->id,
        );

        $this->indexBestEffort($context, $reference->publicId);

        return $reference;
    }

    public function publish(TenantContext $context, string $publicId, ?string $versionPublicId = null): WorkflowPublishResult
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertPublishPermission($context);

        $result = $this->workflowPort->publishDefinition(
            $this->scope($context),
            $publicId,
            $versionPublicId,
            $context->user->id,
            $context->membership->id,
        );

        $this->indexBestEffort($context, $result->definition->publicId);

        return $result;
    }

    public function archive(TenantContext $context, string $publicId): WorkflowDefinitionReference
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertManagePermission($context);

        return $this->workflowPort->archiveDefinition(
            $this->scope($context),
            $publicId,
            $context->user->id,
            $context->membership->id,
        );
    }

    /**
     * @return list<WorkflowVersionData>
     */
    public function listVersions(TenantContext $context, string $publicId): array
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertReadPermission($context);

        return $this->workflowPort->listVersions($this->scope($context), $publicId);
    }

    public function validate(TenantContext $context, WorkflowDefinitionData $data): WorkflowValidationReport
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertReadPermission($context);

        return $this->workflowPort->validateDefinition($data);
    }

    public function statistics(TenantContext $context): WorkflowStatistics
    {
        $this->runtimeBridge->requireCapability($context, 'workflow');
        $this->assertReadPermission($context);

        return $this->workflowPort->statistics($this->scope($context));
    }

    private function indexBestEffort(TenantContext $context, string $publicId): void
    {
        $definition = WorkflowDefinition::query()
            ->where('public_id', $publicId)
            ->where('organization_id', $context->organization->id)
            ->first();

        if ($definition !== null) {
            $this->searchIndexer->indexBestEffort($context, $definition);
        }
    }

    private function scope(TenantContext $context, ?string $moduleKey = null): EnterpriseScope
    {
        return new EnterpriseScope(
            organizationPublicId: $context->organizationPublicId,
            workspacePublicId: $context->workspacePublicId,
            moduleKey: $moduleKey,
        );
    }

    private function assertReadPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.read')) {
            abort(403, 'You are not allowed to read workflows.');
        }
    }

    private function assertManagePermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.manage')) {
            abort(403, 'You are not allowed to manage workflows.');
        }
    }

    private function assertPublishPermission(TenantContext $context): void
    {
        if (! $this->allows($context, 'workflow.publish')) {
            abort(403, 'You are not allowed to publish workflows.');
        }
    }

    private function allows(TenantContext $context, string $permission): bool
    {
        return app(\App\Services\Authorization\TenantAuthorizationService::class)
            ->allows($context, $permission);
    }
}
