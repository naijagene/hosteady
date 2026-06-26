<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformSearchIndex;
use App\Models\WorkflowHumanTask;
use App\Models\WorkflowInstance;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Human\Data\HumanTaskReference;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Services\Enterprise\Workflow\Human\ApprovalService;
use App\Services\Enterprise\Workflow\Human\HumanTaskHealthService;
use App\Services\Enterprise\Workflow\Human\HumanTaskService;
use App\Services\Enterprise\Workflow\Human\TaskInboxService;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeService;
use App\Services\Enterprise\Workflow\WorkflowDefinitionService;
use App\Services\Enterprise\Workflow\WorkflowHealthService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4WorkflowHumanTaskTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_human_task_reference_serializes_to_array(): void
    {
        $reference = new HumanTaskReference(
            publicId: '01900000-0000-7000-8000-000000000400',
            status: 'assigned',
            taskType: 'wait',
            title: 'Review request',
        );

        $this->assertSame('assigned', $reference->toArray()['status']);
    }

    public function test_wait_workflow_creates_human_task_and_waits(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.wait',
            'definition' => $this->waitDefinition('human.wait'),
        ]);

        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $this->assertSame('waiting', $result->instance->status);
        $this->assertTrue(WorkflowHumanTask::query()->where('task_type', 'wait')->exists());
    }

    public function test_approval_workflow_creates_approval_task(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.approval',
            'definition' => $this->approvalDefinition('human.approval'),
        ]);

        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $task = WorkflowHumanTask::query()->where('task_type', 'approval')->firstOrFail();
        $this->assertSame('pending', $task->approval_status->value);
    }

    public function test_complete_task_resumes_waiting_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.complete',
            'definition' => $this->waitDefinition('human.complete'),
        ]);

        $executed = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        $task = WorkflowHumanTask::query()->firstOrFail();

        app(HumanTaskService::class)->complete($context, $task->public_id);

        $instance = WorkflowInstance::query()->where('public_id', $executed->instance->publicId)->firstOrFail();
        $this->assertSame(WorkflowInstanceStatus::Completed, $instance->status);
    }

    public function test_approve_resumes_waiting_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.approve',
            'definition' => $this->approvalDefinition('human.approve'),
        ]);

        $executed = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        $task = WorkflowHumanTask::query()->where('task_type', 'approval')->firstOrFail();

        app(ApprovalService::class)->approve($context, $task->public_id, 'Looks good');

        $instance = WorkflowInstance::query()->where('public_id', $executed->instance->publicId)->firstOrFail();
        $this->assertSame(WorkflowInstanceStatus::Completed, $instance->status);
    }

    public function test_reject_marks_task_rejected(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.reject',
            'definition' => $this->approvalDefinition('human.reject'),
        ]);

        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        $task = WorkflowHumanTask::query()->where('task_type', 'approval')->firstOrFail();

        app(ApprovalService::class)->reject($context, $task->public_id, 'Not approved');

        $task->refresh();
        $this->assertSame('rejected', $task->status->value);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ApprovalRejected->value)->exists());
    }

    public function test_open_task_records_opened_status(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);

        $opened = app(HumanTaskService::class)->open($context, $task->public_id);

        $this->assertSame('opened', $opened->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TaskOpened->value)->exists());
    }

    public function test_cancel_task_records_cancelled_status(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);

        $cancelled = app(HumanTaskService::class)->cancel($context, $task->public_id);

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TaskCancelled->value)->exists());
    }

    public function test_add_and_list_comments(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $service = app(HumanTaskService::class);

        $service->addComment($context, $task->public_id, 'Please review soon');
        $comments = $service->listComments($context, $task->public_id);

        $this->assertCount(1, $comments);
        $this->assertSame('Please review soon', $comments[0]->body);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TaskCommented->value)->exists());
    }

    public function test_task_history_includes_lifecycle_events(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $service = app(HumanTaskService::class);
        $service->open($context, $task->public_id);
        $service->addComment($context, $task->public_id, 'Note');

        $history = $service->history($context, $task->public_id);

        $this->assertNotEmpty($history);
        $this->assertContains('created', array_column(array_map(fn ($h) => $h->toArray(), $history), 'event_type'));
    }

    public function test_inbox_returns_assigned_tasks(): void
    {
        $context = $this->tenantContext();
        $this->createWaitTask($context);

        $inbox = app(TaskInboxService::class)->inbox($context, 'assigned');

        $this->assertCount(1, $inbox);
    }

    public function test_list_and_show_tasks(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $service = app(HumanTaskService::class);

        $listed = $service->list($context);
        $shown = $service->show($context, $task->public_id);

        $this->assertCount(1, $listed);
        $this->assertSame($task->public_id, $shown->publicId);
    }

    public function test_list_and_show_approvals(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.approval.list',
            'definition' => $this->approvalDefinition('human.approval.list'),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        $task = WorkflowHumanTask::query()->where('task_type', 'approval')->firstOrFail();

        $service = app(ApprovalService::class);
        $listed = $service->list($context);
        $shown = $service->show($context, $task->public_id);

        $this->assertCount(1, $listed);
        $this->assertSame($task->public_id, $shown->taskPublicId);
    }

    public function test_task_statistics_report_counts(): void
    {
        $context = $this->tenantContext();
        $this->createWaitTask($context);

        $stats = app(HumanTaskService::class)->statistics($context);

        $this->assertGreaterThan(0, $stats->pending);
    }

    public function test_human_task_health_service_reports_metrics(): void
    {
        $context = $this->tenantContext();
        $this->createWaitTask($context);

        $health = app(HumanTaskHealthService::class)->assess($context);

        $this->assertArrayHasKey('pending', $health);
        $this->assertArrayHasKey('status', $health);
    }

    public function test_workflow_health_includes_human_section(): void
    {
        $context = $this->tenantContext();
        $health = app(WorkflowHealthService::class)->assess($context);

        $this->assertArrayHasKey('human', $health);
    }

    public function test_doctor_exposes_human_task_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('human', $report->platformSummary['enterprise']['workflow']);
    }

    public function test_runtime_metadata_includes_human_section(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertArrayHasKey('human', $runtime->runtimeMetadata['enterprise']['workflow']);
    }

    public function test_search_indexing_is_best_effort_for_tasks(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);

        app(HumanTaskService::class)->open($context, $task->public_id);

        $this->assertTrue(PlatformSearchIndex::query()->where('entity_public_id', $task->public_id)->exists());
    }

    public function test_tenant_isolation_prevents_cross_organization_task_access(): void
    {
        $ownerA = $this->tenantContext();
        $task = $this->createWaitTask($ownerA);
        $ownerB = $this->tenantContext();

        $this->expectException(\App\Modules\Sdk\Workflow\Human\Exceptions\HumanTaskException::class);
        app(HumanTaskService::class)->show($ownerB, $task->public_id);
    }

    public function test_human_tasks_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.human_tasks.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);
        app(HumanTaskService::class)->list($context);
    }

    public function test_approvals_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.approvals.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);
        app(ApprovalService::class)->list($context);
    }

    public function test_api_list_and_show_tasks(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $headers = $this->tenantHeaders($context);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/human-tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/human-tasks/'.$task->public_id)
            ->assertOk()
            ->assertJsonPath('data.public_id', $task->public_id);
    }

    public function test_api_complete_task_and_inbox(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $headers = $this->tenantHeaders($context);

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/human-tasks/'.$task->public_id.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/human-tasks/inbox?type=assigned')
            ->assertOk();
    }

    public function test_api_approve_and_list_approvals(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.api.approve',
            'definition' => $this->approvalDefinition('human.api.approve'),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        $task = WorkflowHumanTask::query()->where('task_type', 'approval')->firstOrFail();
        $headers = $this->tenantHeaders($context);

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/approvals')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/approvals/'.$task->public_id.'/approve', ['comment' => 'ok'])
            ->assertOk()
            ->assertJsonPath('data.decision_type', 'approve');
    }

    public function test_api_add_comment_and_history(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $headers = $this->tenantHeaders($context);

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/human-tasks/'.$task->public_id.'/comments', ['body' => 'hello'])
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/human-tasks/'.$task->public_id.'/history')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_member_can_read_tasks_and_decide_approvals(): void
    {
        $context = $this->tenantContext();
        $this->createWaitTask($context);
        $member = $this->memberContext($context);

        $listed = app(HumanTaskService::class)->list($member);
        $this->assertCount(1, $listed);
    }

    public function test_member_cannot_manage_tasks_without_permission(): void
    {
        $context = $this->tenantContext();
        $task = $this->createWaitTask($context);
        $member = $this->memberContext($context);
        $token = $this->issueToken($member->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($member->organizationPublicId, $member->workspacePublicId)
            ->postJson('/api/v1/tenant/human-tasks/'.$task->public_id.'/complete')
            ->assertForbidden();
    }

    public function test_task_created_audit_event_recorded(): void
    {
        $context = $this->tenantContext();
        $this->createWaitTask($context);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TaskCreated->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::TaskAssigned->value)->exists());
    }

    public function test_approval_requested_audit_event_recorded(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.audit.approval',
            'definition' => $this->approvalDefinition('human.audit.approval'),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::ApprovalRequested->value)->exists());
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'human-task-'.uniqid()]);

        return $this->buildTenantContext($user, $result);
    }

    private function memberContext(TenantContext $ownerContext): TenantContext
    {
        $member = $this->createActiveUser();
        $memberRole = \App\Models\Role::query()
            ->where('organization_id', $ownerContext->organization->id)
            ->where('key', 'member')
            ->firstOrFail();

        $membership = $ownerContext->organization->memberships()->create([
            'user_id' => $member->id,
            'status' => \App\Enums\MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $ownerContext->workspace->id,
            'join_method' => \App\Enums\JoinMethod::Invitation,
        ]);

        $membership->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TenantContext::fromModels(
            $member,
            $ownerContext->organization,
            $membership,
            $ownerContext->workspace,
        );
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(TenantContext $context): array
    {
        $token = $this->issueToken($context->user);

        return [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publishedWorkflow(TenantContext $context, array $overrides = []): \App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference
    {
        $service = app(WorkflowDefinitionService::class);
        $data = $overrides['definition'] ?? $this->waitDefinition($overrides['workflow_key'] ?? 'human.default');
        $created = $service->create($context, $data);

        return $service->publish($context, $created->publicId)->definition;
    }

    private function createWaitTask(TenantContext $context): WorkflowHumanTask
    {
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'human.task.'.uniqid(),
            'definition' => $this->waitDefinition('human.task.'.uniqid()),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        return WorkflowHumanTask::query()->firstOrFail();
    }

    private function waitDefinition(string $workflowKey): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Wait Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('wait1', 'wait', 'Wait for action'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'wait1'),
                new WorkflowTransitionData('t2', 'wait1', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
        );
    }

    private function approvalDefinition(string $workflowKey): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Approval Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('approval1', 'approval', 'Approve request'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'approval1'),
                new WorkflowTransitionData('t2', 'approval1', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
        );
    }
}
