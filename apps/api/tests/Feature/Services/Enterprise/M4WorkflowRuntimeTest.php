<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformSearchIndex;
use App\Models\WorkflowExecutionLog;
use App\Models\WorkflowExecutionStep;
use App\Models\WorkflowInstance;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Data\WorkflowVariableData;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowInstanceReference;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeHealthService;
use App\Services\Enterprise\Workflow\Runtime\WorkflowRuntimeService;
use App\Services\Enterprise\Workflow\WorkflowDefinitionService;
use App\Services\Module\ModuleDoctorService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4WorkflowRuntimeTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_workflow_instance_reference_serializes_to_array(): void
    {
        $reference = new WorkflowInstanceReference(
            publicId: '01900000-0000-7000-8000-000000000300',
            status: 'completed',
            definitionPublicId: '01900000-0000-7000-8000-000000000301',
            definitionName: 'Runtime Workflow',
        );

        $this->assertSame('completed', $reference->toArray()['status']);
    }

    public function test_execution_result_serializes_to_array(): void
    {
        $result = new WorkflowExecutionResult(
            instance: new WorkflowInstanceReference(
                publicId: 'inst-1',
                status: 'completed',
                definitionPublicId: 'def-1',
                definitionName: 'Test',
            ),
        );

        $this->assertSame('inst-1', $result->toArray()['instance']['public_id']);
    }

    public function test_execute_completes_linear_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.linear']);

        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId, [
            'comment' => 'go',
        ]);

        $this->assertSame('completed', $result->instance->status);
        $this->assertNotEmpty($result->steps);
    }

    public function test_execute_records_execution_steps(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.steps']);

        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $this->assertTrue(
            WorkflowExecutionStep::query()->where('workflow_instance_id', WorkflowInstance::query()->where('public_id', $result->instance->publicId)->value('id'))->exists(),
        );
    }

    public function test_execute_records_audit_events(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.audit']);

        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExecutionStarted->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExecutionCompleted->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExecutionNodeExecuted->value)->exists());
    }

    public function test_execute_records_execution_logs(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.logs']);

        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $instanceId = WorkflowInstance::query()->where('public_id', $result->instance->publicId)->value('id');
        $this->assertTrue(WorkflowExecutionLog::query()->where('workflow_instance_id', $instanceId)->exists());
    }

    public function test_execute_snapshots_variables(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.vars']);

        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId, [
            'comment' => 'snapshot-me',
        ]);

        $this->assertNotEmpty($result->variables);
        $this->assertSame('snapshot-me', collect($result->variables)->firstWhere('key', 'comment')?->value);
    }

    public function test_condition_routes_to_matching_branch(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'runtime.condition',
            'definition' => $this->conditionDefinition('runtime.condition'),
        ]);

        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId, [
            'approved' => 'true',
        ]);

        $this->assertSame('completed', $result->instance->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExecutionCondition->value)->exists());
    }

    public function test_cancel_marks_instance_cancelled(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'runtime.wait.cancel',
            'definition' => $this->waitDefinition('runtime.wait.cancel'),
        ]);

        $runtime = app(WorkflowRuntimeService::class);
        $executed = $runtime->execute($context, $definition->publicId);
        $this->assertSame('waiting', $executed->instance->status);

        $cancelled = $runtime->cancel($context, $executed->instance->publicId);
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExecutionCancelled->value)->exists());
    }

    public function test_resume_completes_waiting_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'runtime.wait.resume',
            'definition' => $this->waitDefinition('runtime.wait.resume'),
        ]);

        $runtime = app(WorkflowRuntimeService::class);
        $executed = $runtime->execute($context, $definition->publicId);
        $this->assertSame('waiting', $executed->instance->status);

        $resumed = $runtime->resume($context, $executed->instance->publicId);
        $this->assertSame('completed', $resumed->instance->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowExecutionResumed->value)->exists());
    }

    public function test_list_and_show_instances(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.list']);
        $runtime = app(WorkflowRuntimeService::class);
        $executed = $runtime->execute($context, $definition->publicId);

        $listed = $runtime->list($context);
        $shown = $runtime->show($context, $executed->instance->publicId);

        $this->assertCount(1, $listed);
        $this->assertSame($executed->instance->publicId, $shown->publicId);
    }

    public function test_history_returns_steps_and_logs(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.history']);
        $executed = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $history = app(WorkflowRuntimeService::class)->history($context, $executed->instance->publicId);

        $this->assertNotEmpty($history['steps']);
        $this->assertNotEmpty($history['logs']);
    }

    public function test_runtime_statistics_report_counts(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.stats']);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $stats = app(WorkflowRuntimeService::class)->statistics($context);

        $this->assertSame(0, $stats->runningInstances);
        $this->assertSame(1, $stats->completedToday);
    }

    public function test_runtime_health_service_reports_metrics(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.health']);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $health = app(WorkflowRuntimeHealthService::class)->assess($context);

        $this->assertArrayHasKey('running_instances', $health);
        $this->assertArrayHasKey('status', $health);
    }

    public function test_doctor_exposes_workflow_runtime_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('runtime', $report->platformSummary['enterprise']['workflow']);
    }

    public function test_runtime_metadata_includes_runtime_section(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertArrayHasKey('runtime', $runtime->runtimeMetadata['enterprise']['workflow']);
    }

    public function test_search_indexing_is_best_effort_for_instances(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.search']);
        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $this->assertTrue(PlatformSearchIndex::query()->where('entity_public_id', $result->instance->publicId)->exists());
    }

    public function test_tenant_isolation_prevents_cross_organization_access(): void
    {
        $ownerA = $this->tenantContext();
        $definition = $this->publishedWorkflow($ownerA, ['workflow_key' => 'runtime.tenant']);
        $executed = app(WorkflowRuntimeService::class)->execute($ownerA, $definition->publicId);

        $ownerB = $this->tenantContext();

        $this->expectException(\App\Modules\Sdk\Workflow\Runtime\Exceptions\WorkflowInstanceNotFoundException::class);
        app(WorkflowRuntimeService::class)->show($ownerB, $executed->instance->publicId);
    }

    public function test_runtime_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.workflow.enabled' => false]);
        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(WorkflowRuntimeService::class)->list($context);
    }

    public function test_api_execute_and_list_instances(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.api']);
        $token = $this->issueToken($context->user);
        $headers = [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/workflows/definitions/'.$definition->publicId.'/execute', [
                'input' => ['comment' => 'api-run'],
            ])
            ->assertOk()
            ->assertJsonPath('data.instance.status', 'completed');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/workflow-instances')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_api_show_cancel_and_history(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'runtime.api.cancel',
            'definition' => $this->waitDefinition('runtime.api.cancel'),
        ]);
        $token = $this->issueToken($context->user);
        $headers = [
            'Authorization' => 'Bearer '.$token,
            \App\Http\Middleware\ResolveTenantContext::ORGANIZATION_HEADER => $context->organizationPublicId,
            \App\Http\Middleware\ResolveTenantContext::WORKSPACE_HEADER => $context->workspacePublicId,
        ];

        $execute = $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/workflows/definitions/'.$definition->publicId.'/execute')
            ->assertOk();

        $publicId = $execute->json('data.instance.public_id');

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/workflow-instances/'.$publicId)
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/v1/tenant/workflow-instances/'.$publicId.'/history')
            ->assertOk()
            ->assertJsonStructure(['data' => ['steps', 'logs']]);

        $this->withHeaders($headers)
            ->postJson('/api/v1/tenant/workflow-instances/'.$publicId.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_member_cannot_execute_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.member']);
        $member = $this->memberContext($context);
        $token = $this->issueToken($member->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($member->organizationPublicId, $member->workspacePublicId)
            ->postJson('/api/v1/tenant/workflows/definitions/'.$definition->publicId.'/execute')
            ->assertForbidden();
    }

    public function test_member_can_read_workflow_runtime(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.member.read']);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $member = $this->memberContext($context);
        $listed = app(WorkflowRuntimeService::class)->list($member);

        $this->assertCount(1, $listed);
    }

    public function test_execute_resolves_context_variables(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.context']);
        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $organizationVar = collect($result->variables)->firstWhere('key', 'organization_public_id');
        $this->assertSame($context->organizationPublicId, $organizationVar?->value);
    }

    public function test_completed_instance_has_duration(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, ['workflow_key' => 'runtime.duration']);
        $result = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $instance = WorkflowInstance::query()->where('public_id', $result->instance->publicId)->firstOrFail();
        $this->assertNotNull($instance->duration_ms);
        $this->assertSame(WorkflowInstanceStatus::Completed, $instance->status);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'workflow-runtime-'.uniqid()]);

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
     * @param  array<string, mixed>  $overrides
     */
    private function publishedWorkflow(TenantContext $context, array $overrides = []): \App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference
    {
        $service = app(WorkflowDefinitionService::class);
        $data = $overrides['definition'] ?? $this->linearDefinition($overrides['workflow_key'] ?? 'runtime.default');
        $created = $service->create($context, $data);

        return $service->publish($context, $created->publicId)->definition;
    }

    private function linearDefinition(string $workflowKey): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Runtime Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('task1', 'task', 'Process'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'task1'),
                new WorkflowTransitionData('t2', 'task1', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
            variables: [new WorkflowVariableData('comment', 'Comment', 'string')],
        );
    }

    private function conditionDefinition(string $workflowKey): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Condition Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('check', 'condition'),
                new WorkflowNodeData('approved', 'task'),
                new WorkflowNodeData('rejected', 'task'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'check'),
                new WorkflowTransitionData('t2', 'check', 'approved', condition: 'approved==true'),
                new WorkflowTransitionData('t3', 'check', 'rejected', condition: 'approved==false'),
                new WorkflowTransitionData('t4', 'approved', 'end'),
                new WorkflowTransitionData('t5', 'rejected', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
            variables: [new WorkflowVariableData('approved', 'Approved', 'string')],
        );
    }

    private function waitDefinition(string $workflowKey): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Wait Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('wait1', 'wait'),
                new WorkflowNodeData('end', 'end'),
            ],
            transitions: [
                new WorkflowTransitionData('t1', 'start', 'wait1'),
                new WorkflowTransitionData('t2', 'wait1', 'end'),
            ],
            triggers: [new WorkflowTriggerData('manual')],
        );
    }
}
