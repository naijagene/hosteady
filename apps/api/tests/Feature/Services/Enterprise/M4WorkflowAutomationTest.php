<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\PlatformJob;
use App\Models\PlatformSearchIndex;
use App\Models\ScheduledTask;
use App\Models\WorkflowAutomationRule;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTimer;
use App\Models\WorkflowTriggerExecution;
use App\Models\WorkflowTriggerSubscription;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformEventRequest;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowAutomationRule as WorkflowAutomationRuleData;
use App\Modules\Sdk\Workflow\Automation\Data\WorkflowTimerReference;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowNodeData;
use App\Modules\Sdk\Workflow\Data\WorkflowTransitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowTriggerData;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Services\Enterprise\EventBus\EventBusService;
use App\Services\Enterprise\Jobs\PlatformJobHandlerRegistry;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationHealthService;
use App\Services\Enterprise\Workflow\Automation\WorkflowAutomationService;
use App\Services\Enterprise\Workflow\Automation\WorkflowEventTriggerService;
use App\Services\Enterprise\Workflow\Automation\WorkflowScheduledTriggerService;
use App\Services\Enterprise\Workflow\Automation\WorkflowTimerRunner;
use App\Services\Enterprise\Workflow\Automation\WorkflowTimerService;
use App\Services\Enterprise\Workflow\Automation\WorkflowTriggerService;
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

class M4WorkflowAutomationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_automation_rule_serializes_to_array(): void
    {
        $rule = new WorkflowAutomationRuleData(
            publicId: '01900000-0000-7000-8000-000000000500',
            triggerType: 'platform_event',
            status: 'active',
            workflowDefinitionPublicId: '01900000-0000-7000-8000-000000000501',
        );

        $this->assertSame('platform_event', $rule->toArray()['trigger_type']);
    }

    public function test_timer_reference_serializes_to_array(): void
    {
        $timer = new WorkflowTimerReference(
            publicId: '01900000-0000-7000-8000-000000000502',
            timerType: 'delay',
            status: 'active',
            nodeId: 'wait1',
            workflowInstancePublicId: '01900000-0000-7000-8000-000000000503',
            dueAt: now()->toIso8601String(),
        );

        $this->assertSame('delay', $timer->toArray()['timer_type']);
    }

    public function test_create_automation_rule_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.create');

        $rule = app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
            'trigger_config' => [],
        ]);

        $this->assertSame('manual', $rule->triggerType);
        $this->assertTrue(WorkflowAutomationRule::query()->where('public_id', $rule->publicId)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowAutomationRuleCreated->value)->exists());
    }

    public function test_platform_event_rule_creates_subscription(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.event');

        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'platform_event',
            'trigger_config' => ['event_name' => 'demo.order.created'],
        ]);

        $this->assertTrue(
            WorkflowTriggerSubscription::query()->where('event_name', 'demo.order.created')->exists()
        );
    }

    public function test_schedule_rule_creates_scheduled_task(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.schedule');

        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'schedule',
            'trigger_config' => [
                'cron_expression' => '0 * * * *',
                'timezone' => 'UTC',
            ],
        ]);

        $this->assertTrue(
            ScheduledTask::query()->where('task_type', 'workflow.automation.trigger')->exists()
        );
    }

    public function test_disable_and_enable_rule(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.toggle');
        $service = app(WorkflowAutomationService::class);

        $created = $service->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $disabled = $service->disableRule($context, $created->publicId);
        $this->assertSame('disabled', $disabled->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowAutomationRuleDisabled->value)->exists());

        $enabled = $service->enableRule($context, $created->publicId);
        $this->assertSame('active', $enabled->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowAutomationRuleEnabled->value)->exists());
    }

    public function test_delete_rule_records_audit(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.delete');
        $service = app(WorkflowAutomationService::class);

        $created = $service->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $service->deleteRule($context, $created->publicId);

        $this->assertSoftDeleted('workflow_automation_rules', ['public_id' => $created->publicId]);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowAutomationRuleDeleted->value)->exists());
    }

    public function test_list_rules_and_statistics(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.list');
        $service = app(WorkflowAutomationService::class);

        $service->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $rules = $service->listRules($context);
        $stats = $service->statistics($context);

        $this->assertCount(1, $rules);
        $this->assertSame(1, $stats->activeRules);
    }

    public function test_platform_event_triggers_workflow_execution(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.trigger');
        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'platform_event',
            'trigger_config' => ['event_name' => 'demo.automation.trigger'],
        ]);

        app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            eventName: 'demo.automation.trigger',
            payload: ['source' => 'test'],
        ));

        $this->assertTrue(WorkflowTriggerExecution::query()->where('status', 'succeeded')->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowTriggerExecuted->value)->exists());
        $this->assertTrue(WorkflowInstance::query()->where('status', WorkflowInstanceStatus::Completed)->exists());
    }

    public function test_event_trigger_failure_does_not_break_event_processing(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.bad');
        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'platform_event',
            'trigger_config' => ['event_name' => 'demo.automation.fail'],
        ]);

        app(WorkflowDefinitionService::class)->archive($context, $definition->publicId);

        $result = app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            eventName: 'demo.automation.fail',
        ));

        $this->assertSame('processed', $result->status);
    }

    public function test_event_subscriber_lists_active_event_names(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.subscriber');
        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'platform_event',
            'trigger_config' => ['event_name' => 'demo.automation.subscriber'],
        ]);

        $events = app(WorkflowEventTriggerService::class)->subscribedEvents();

        $this->assertContains('demo.automation.subscriber', $events);
    }

    public function test_scheduled_job_handler_executes_rule(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.job');
        $rule = app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $job = PlatformJob::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'job_type' => 'workflow.automation.trigger',
            'display_name' => 'Automation trigger',
            'payload' => ['rule_public_id' => $rule->publicId],
            'status' => \App\Enums\PlatformJobStatus::Queued,
            'priority' => 'normal',
            'attempts' => 0,
            'max_attempts' => 1,
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        $result = app(PlatformJobHandlerRegistry::class)->execute($job);

        $this->assertSame('succeeded', $result['status']);
        $this->assertTrue(WorkflowTriggerExecution::query()->where('status', 'succeeded')->exists());
    }

    public function test_wait_node_with_timer_creates_workflow_timer(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'automation.timer.wait',
            'definition' => $this->timerWaitDefinition('automation.timer.wait'),
        ]);

        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);

        $timer = WorkflowTimer::query()->firstOrFail();
        $this->assertSame('delay', $timer->timer_type);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowTimerCreated->value)->exists());
    }

    public function test_timer_runner_resumes_waiting_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'automation.timer.run',
            'definition' => $this->timerWaitDefinition('automation.timer.run', 1),
        ]);

        $executed = app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        WorkflowTimer::query()->update(['due_at' => now()->subSecond()]);

        $result = app(WorkflowTimerRunner::class)->runDueTimers();

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['succeeded']);
        $instance = WorkflowInstance::query()->where('public_id', $executed->instance->publicId)->firstOrFail();
        $this->assertSame(WorkflowInstanceStatus::Completed, $instance->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowTimerExecuted->value)->exists());
    }

    public function test_list_triggers_and_timers(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.index');
        $service = app(WorkflowAutomationService::class);

        $service->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'platform_event',
            'trigger_config' => ['event_name' => 'demo.automation.index'],
        ]);

        app(EventBusService::class)->dispatch($context, new PlatformEventRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            eventName: 'demo.automation.index',
        ));

        $waitDefinition = $this->publishedWorkflow($context, [
            'workflow_key' => 'automation.timer.index',
            'definition' => $this->timerWaitDefinition('automation.timer.index', 3600),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $waitDefinition->publicId);

        $triggers = $service->listTriggers($context);
        $timers = $service->listTimers($context, 'active');

        $this->assertNotEmpty($triggers);
        $this->assertNotEmpty($timers);
    }

    public function test_automation_health_service_reports_metrics(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.health');
        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $health = app(WorkflowAutomationHealthService::class)->assess($context);

        $this->assertArrayHasKey('active_rules', $health);
        $this->assertArrayHasKey('status', $health);
        $this->assertTrue($health['enabled']);
    }

    public function test_workflow_health_includes_automation_section(): void
    {
        $context = $this->tenantContext();
        $health = app(WorkflowHealthService::class)->assess($context);

        $this->assertArrayHasKey('automation', $health);
    }

    public function test_runtime_includes_automation_capability(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertTrue($runtime->capabilities['automation']);
        $this->assertArrayHasKey('automation', $runtime->runtimeMetadata['enterprise']['workflow']);
    }

    public function test_automation_blocked_when_capability_disabled(): void
    {
        config(['heos.enterprise.automation.enabled' => false]);
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.disabled');

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);
    }

    public function test_member_can_read_but_not_manage_automation(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);
        $definition = $this->publishedEndWorkflow($ownerContext, 'automation.perm');

        app(WorkflowAutomationService::class)->createRule($ownerContext, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $listed = app(WorkflowAutomationService::class)->listRules($memberContext);
        $this->assertCount(1, $listed);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(WorkflowAutomationService::class)->createRule($memberContext, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);
    }

    public function test_create_rule_indexes_search_best_effort(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.search');

        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $this->assertTrue(
            PlatformSearchIndex::query()->where('entity_type', 'workflow_automation_rule')->exists()
        );
    }

    public function test_manual_trigger_service_executes_workflow(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.manual');
        $rule = app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        $result = app(WorkflowTriggerService::class)->executeRule(
            $rule,
            'manual',
        );

        $this->assertSame('succeeded', $result->status);
        $this->assertNotNull($result->workflowInstancePublicId);
    }

    public function test_api_routes_for_automation_rules(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.api');
        $headers = $this->tenantHeaders($context);

        $create = $this->postJson('/api/v1/tenant/workflows/automation/rules', [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ], $headers);

        $create->assertCreated();
        $publicId = $create->json('data.public_id');

        $this->getJson('/api/v1/tenant/workflows/automation/rules', $headers)->assertOk();
        $this->patchJson("/api/v1/tenant/workflows/automation/rules/{$publicId}/disable", [], $headers)->assertOk();
        $this->patchJson("/api/v1/tenant/workflows/automation/rules/{$publicId}/enable", [], $headers)->assertOk();
        $this->getJson('/api/v1/tenant/workflows/automation/triggers', $headers)->assertOk();
        $this->getJson('/api/v1/tenant/workflows/automation/timers', $headers)->assertOk();
        $this->deleteJson("/api/v1/tenant/workflows/automation/rules/{$publicId}", [], $headers)->assertNoContent();
    }

    public function test_doctor_exposes_automation_health(): void
    {
        $report = app(ModuleDoctorService::class)->diagnose();

        $this->assertArrayHasKey('automation', $report->platformSummary['enterprise']['workflow']);
    }

    public function test_timer_service_cancel_timer(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'automation.timer.cancel',
            'definition' => $this->timerWaitDefinition('automation.timer.cancel', 3600),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        $timer = WorkflowTimer::query()->firstOrFail();

        $cancelled = app(WorkflowTimerService::class)->cancelTimer(
            new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            $timer->public_id,
        );

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowTimerCancelled->value)->exists());
    }

    public function test_cli_timer_command_outputs_counts(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedWorkflow($context, [
            'workflow_key' => 'automation.cli',
            'definition' => $this->timerWaitDefinition('automation.cli', 1),
        ]);
        app(WorkflowRuntimeService::class)->execute($context, $definition->publicId);
        WorkflowTimer::query()->update(['due_at' => now()->subSecond()]);

        $this->artisan('heos:workflow:timers:run')
            ->expectsOutputToContain('Processed: 1')
            ->assertSuccessful();
    }

    public function test_cli_timer_command_does_not_crash_when_workflow_timers_table_is_missing(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('workflow_timers');

        $this->artisan('heos:workflow:timers:run')
            ->assertExitCode(1);
    }

    public function test_cli_timer_command_exits_with_missing_table_warning(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('workflow_timers');

        $this->artisan('heos:workflow:timers:run')
            ->expectsOutputToContain('Required table [workflow_timers] is missing. Run php artisan migrate.')
            ->assertExitCode(1);
    }

    public function test_timer_runner_returns_missing_tables_without_querying(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('workflow_timer_executions');

        $result = app(\App\Services\Enterprise\Workflow\Automation\WorkflowTimerRunner::class)->runDueTimers();

        $this->assertContains('workflow_timer_executions', $result['missing_tables']);
        $this->assertSame(0, $result['processed']);
    }

    public function test_scheduled_trigger_service_skips_inactive_rule(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.skip');
        $rule = app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);
        app(WorkflowAutomationService::class)->disableRule($context, $rule->publicId);

        $job = PlatformJob::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'job_type' => 'workflow.automation.trigger',
            'display_name' => 'Automation trigger',
            'payload' => ['rule_public_id' => $rule->publicId],
            'status' => \App\Enums\PlatformJobStatus::Queued,
            'priority' => 'normal',
            'attempts' => 0,
            'max_attempts' => 1,
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        $result = app(WorkflowScheduledTriggerService::class)->executeFromJob($job);

        $this->assertSame('skipped', $result['status']);
    }

    public function test_entity_created_rule_uses_default_event_name(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.entity');

        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'entity_created',
            'trigger_config' => [],
        ]);

        $this->assertTrue(
            WorkflowTriggerSubscription::query()->where('event_name', 'entity.created')->exists()
        );
    }

    public function test_automation_statistics_counts_disabled_rules(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.stats');
        $service = app(WorkflowAutomationService::class);

        $rule = $service->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);
        $service->disableRule($context, $rule->publicId);

        $stats = $service->statistics($context);

        $this->assertSame(0, $stats->activeRules);
        $this->assertSame(1, $stats->disabledRules);
    }

    public function test_trigger_handler_records_failed_execution(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.fail.exec');
        $rule = app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'manual',
        ]);

        app(WorkflowDefinitionService::class)->archive($context, $definition->publicId);

        try {
            app(WorkflowTriggerService::class)->executeRule($rule, 'manual');
            $this->fail('Expected trigger failure.');
        } catch (\Throwable) {
            $this->assertTrue(WorkflowTriggerExecution::query()->where('status', 'failed')->exists());
            $this->assertTrue(AuditLog::query()->where('action', AuditAction::WorkflowTriggerFailed->value)->exists());
        }
    }

    public function test_event_provider_returns_matching_subscriptions(): void
    {
        $context = $this->tenantContext();
        $definition = $this->publishedEndWorkflow($context, 'automation.provider');
        app(WorkflowAutomationService::class)->createRule($context, [
            'workflow_definition_public_id' => $definition->publicId,
            'trigger_type' => 'platform_event',
            'trigger_config' => ['event_name' => 'demo.provider.event'],
        ]);

        $event = new \App\Modules\Sdk\Enterprise\Data\PlatformEventData(
            eventPublicId: 'evt-1',
            eventName: 'demo.provider.event',
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            payload: [],
        );

        $subscriptions = app(WorkflowEventTriggerService::class)->subscriptionsForEvent($event);

        $this->assertCount(1, $subscriptions);
        $this->assertSame('demo.provider.event', $subscriptions[0]->eventName);
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'automation-'.uniqid()]);

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

    private function publishedEndWorkflow(TenantContext $context, string $workflowKey): \App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference
    {
        return $this->publishedWorkflow($context, [
            'workflow_key' => $workflowKey,
            'definition' => new WorkflowDefinitionData(
                workflowKey: $workflowKey,
                name: 'End Workflow',
                nodes: [
                    new WorkflowNodeData('start', 'start'),
                    new WorkflowNodeData('end', 'end'),
                ],
                transitions: [
                    new WorkflowTransitionData('t1', 'start', 'end'),
                ],
                triggers: [new WorkflowTriggerData('manual')],
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function publishedWorkflow(TenantContext $context, array $overrides = []): \App\Modules\Sdk\Workflow\Data\WorkflowDefinitionReference
    {
        $service = app(WorkflowDefinitionService::class);
        $data = $overrides['definition'] ?? $this->timerWaitDefinition($overrides['workflow_key'] ?? 'automation.default');
        $created = $service->create($context, $data);

        return $service->publish($context, $created->publicId)->definition;
    }

    private function timerWaitDefinition(string $workflowKey, int $delaySeconds = 60): WorkflowDefinitionData
    {
        return new WorkflowDefinitionData(
            workflowKey: $workflowKey,
            name: 'Timer Wait Workflow',
            nodes: [
                new WorkflowNodeData('start', 'start'),
                new WorkflowNodeData('wait1', 'wait', 'Wait with timer', metadata: [
                    'timer' => [
                        'type' => 'delay',
                        'delay_seconds' => $delaySeconds,
                    ],
                ]),
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
