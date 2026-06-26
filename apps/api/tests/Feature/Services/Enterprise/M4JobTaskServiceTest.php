<?php

namespace Tests\Feature\Services\Enterprise;

use App\Enums\AuditAction;
use App\Enums\PlatformJobStatus;
use App\Enums\ScheduledTaskStatus;
use App\Models\AuditLog;
use App\Models\PlatformJob;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskRun;
use App\Modules\Sdk\Enterprise\Contracts\PlatformJobPort;
use App\Modules\Sdk\Enterprise\Contracts\SchedulerPort;
use App\Modules\Sdk\Enterprise\Data\EnterpriseScope;
use App\Modules\Sdk\Enterprise\Data\PlatformJobDispatchRequest;
use App\Modules\Sdk\Enterprise\Data\PlatformJobReference;
use App\Modules\Sdk\Enterprise\Data\ScheduledTaskRequest;
use App\Services\Enterprise\Jobs\PlatformJobHandlerRegistry;
use App\Services\Enterprise\Jobs\PlatformJobService;
use App\Services\Enterprise\Jobs\PlatformJobTracker;
use App\Services\Enterprise\Runtime\EnterpriseRuntimeBridge;
use App\Services\Enterprise\Scheduler\SchedulerService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeProvider;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class M4JobTaskServiceTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PlatformJobHandlerRegistry::class)->register('platform.test', function () {
            return ['status' => 'ok'];
        });
    }

    public function test_platform_job_reference_serializes_to_array(): void
    {
        $reference = new PlatformJobReference(
            publicId: '01900000-0000-7000-8000-000000000099',
            jobType: 'platform.test',
            status: 'succeeded',
            priority: 'normal',
            displayName: 'Test Job',
        );

        $this->assertSame('platform.test', $reference->toArray()['job_type']);
    }

    public function test_job_dispatch_creates_record_and_queues_execution(): void
    {
        $context = $this->tenantContext();

        $result = app(PlatformJobService::class)->dispatch($context, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            jobType: 'platform.test',
            displayName: 'Demo Job',
        ));

        $this->assertTrue($result->queued);
        $this->assertSame('succeeded', $result->job->status);
        $this->assertTrue(PlatformJob::query()->where('public_id', $result->job->publicId)->exists());
    }

    public function test_job_dispatch_records_audit_event(): void
    {
        $context = $this->tenantContext();

        app(PlatformJobService::class)->dispatch($context, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            jobType: 'platform.test',
        ));

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::PlatformJobDispatched->value)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::PlatformJobCompleted->value)->exists());
    }

    public function test_job_tracker_marks_failed_when_handler_throws(): void
    {
        app(PlatformJobHandlerRegistry::class)->register('platform.fail', fn () => throw new \RuntimeException('boom'));

        $context = $this->tenantContext();

        $job = PlatformJob::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'job_type' => 'platform.fail',
            'display_name' => 'Fail Job',
            'status' => PlatformJobStatus::Queued,
            'priority' => 'normal',
            'attempts' => 0,
            'max_attempts' => 1,
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        try {
            app(PlatformJobTracker::class)->execute($job->public_id);
        } catch (\RuntimeException) {
        }

        $job->refresh();
        $this->assertSame(PlatformJobStatus::Failed, $job->status);
        $this->assertSame('boom', $job->error_message);
    }

    public function test_job_cancel_updates_status(): void
    {
        config(['queue.default' => 'sync']);
        app(PlatformJobHandlerRegistry::class)->register('platform.slow', function () {
            sleep(0);
            return ['ok' => true];
        });

        $context = $this->tenantContext();
        $port = app(PlatformJobPort::class);

        $pending = PlatformJob::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'job_type' => 'platform.pending',
            'display_name' => 'Pending',
            'status' => PlatformJobStatus::Queued,
            'priority' => 'normal',
            'attempts' => 0,
            'max_attempts' => 3,
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        $cancelled = $port->cancel(
            new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            $pending->public_id,
        );

        $this->assertSame('cancelled', $cancelled->status);
    }

    public function test_job_list_is_scoped_to_tenant(): void
    {
        $contextA = $this->tenantContext();
        $contextB = $this->tenantContext();

        app(PlatformJobService::class)->dispatch($contextA, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($contextA->organizationPublicId, $contextA->workspacePublicId),
            jobType: 'platform.test',
        ));

        $jobsB = app(PlatformJobService::class)->list($contextB);

        $this->assertCount(0, $jobsB);
    }

    public function test_runtime_bridge_enables_jobs_and_scheduler_capabilities(): void
    {
        $context = $this->tenantContext();
        $runtime = app(EnterpriseRuntimeBridge::class)->resolve($context);

        $this->assertTrue($runtime->capabilityEnabled('jobs'));
        $this->assertTrue($runtime->capabilityEnabled('scheduler'));
    }

    public function test_dispatch_rejects_when_jobs_capability_disabled(): void
    {
        config([
            'heos.enterprise.runtime_aware' => false,
            'heos.enterprise.jobs.enabled' => false,
        ]);

        $context = $this->tenantContext();

        $this->expectException(\App\Exceptions\Enterprise\EnterpriseCapabilityDisabledException::class);

        app(PlatformJobService::class)->dispatch($context, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            jobType: 'platform.test',
        ));
    }

    public function test_platform_job_port_binding_uses_laravel_adapter(): void
    {
        $this->assertInstanceOf(
            \App\Services\Enterprise\Jobs\LaravelPlatformJobAdapter::class,
            app(PlatformJobPort::class),
        );
    }

    public function test_scheduler_port_binding_uses_laravel_adapter(): void
    {
        $this->assertInstanceOf(
            \App\Services\Enterprise\Scheduler\LaravelSchedulerAdapter::class,
            app(SchedulerPort::class),
        );
    }

    public function test_jobs_api_dispatches_job(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->postJson('/api/v1/tenant/jobs', [
                'job_type' => 'platform.test',
                'display_name' => 'API Job',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'succeeded')
            ->assertJsonPath('data.job_type', 'platform.test');
    }

    public function test_jobs_api_lists_jobs(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        app(PlatformJobService::class)->dispatch($context, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            jobType: 'platform.test',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/jobs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_jobs_api_cancels_queued_job(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $job = PlatformJob::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'job_type' => 'platform.pending',
            'display_name' => 'Cancel me',
            'status' => PlatformJobStatus::Queued,
            'priority' => 'normal',
            'attempts' => 0,
            'max_attempts' => 3,
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->patchJson('/api/v1/tenant/jobs/'.$job->public_id.'/cancel')
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_scheduler_create_persists_task(): void
    {
        $context = $this->tenantContext();

        $task = app(SchedulerService::class)->create($context, new ScheduledTaskRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId, 'demo'),
            taskType: 'platform.test',
            displayName: 'Daily Sync',
            cronExpression: '@daily',
        ));

        $this->assertSame('active', $task->status);
        $this->assertTrue(ScheduledTask::query()->where('public_id', $task->publicId)->exists());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::SchedulerTaskCreated->value)->exists());
    }

    public function test_scheduler_pause_and_resume(): void
    {
        $context = $this->tenantContext();
        $service = app(SchedulerService::class);

        $task = $service->create($context, new ScheduledTaskRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            taskType: 'platform.test',
            displayName: 'Pause me',
            cronExpression: '@hourly',
        ));

        $paused = $service->pause($context, $task->publicId);
        $this->assertSame('paused', $paused->status);

        $resumed = $service->resume($context, $task->publicId);
        $this->assertSame('active', $resumed->status);
    }

    public function test_scheduler_delete_cancels_task(): void
    {
        $context = $this->tenantContext();
        $service = app(SchedulerService::class);

        $task = $service->create($context, new ScheduledTaskRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            taskType: 'platform.test',
            displayName: 'Delete me',
            runAt: now()->addDay()->toIso8601String(),
        ));

        $service->cancel($context, $task->publicId);

        $model = ScheduledTask::query()->where('public_id', $task->publicId)->firstOrFail();
        $this->assertSame(ScheduledTaskStatus::Cancelled, $model->status);
    }

    public function test_scheduler_run_command_executes_due_task(): void
    {
        $context = $this->tenantContext();

        ScheduledTask::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'module_key' => 'demo',
            'task_type' => 'platform.test',
            'display_name' => 'Due now',
            'run_at' => now()->subMinute(),
            'timezone' => 'UTC',
            'status' => ScheduledTaskStatus::Active,
            'enabled' => true,
            'next_run_at' => now()->subMinute(),
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        Artisan::call('heos:scheduler:run');

        $this->assertSame(1, ScheduledTaskRun::query()->count());
        $this->assertTrue(AuditLog::query()->where('action', AuditAction::SchedulerTaskExecuted->value)->exists());
    }

    public function test_scheduled_task_runs_are_listed_via_api(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $task = app(SchedulerService::class)->create($context, new ScheduledTaskRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            taskType: 'platform.test',
            displayName: 'Runs',
            runAt: now()->subMinute()->toIso8601String(),
        ));

        ScheduledTask::query()->where('public_id', $task->publicId)->update([
            'next_run_at' => now()->subMinute(),
        ]);

        Artisan::call('heos:scheduler:run');

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/scheduled-tasks/'.$task->publicId.'/runs')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_runtime_metadata_includes_jobs_and_scheduler(): void
    {
        $context = $this->tenantContext();
        $runtime = app(WorkspaceRuntimeProvider::class)->resolve($context);

        $this->assertArrayHasKey('jobs', $runtime->runtimeMetadata['enterprise']);
        $this->assertArrayHasKey('scheduler', $runtime->runtimeMetadata['enterprise']);
        $this->assertArrayHasKey('pending_count', $runtime->runtimeMetadata['enterprise']['jobs']);
    }

    public function test_doctor_includes_jobs_and_scheduler_health(): void
    {
        Artisan::call('heos:doctor', ['--json' => true]);

        $payload = json_decode(Artisan::output(), true);

        $this->assertTrue($payload['platform_summary']['enterprise']['jobs']['enabled']);
        $this->assertArrayHasKey('pending_count', $payload['platform_summary']['enterprise']['jobs']);
        $this->assertArrayHasKey('due_count', $payload['platform_summary']['enterprise']['scheduler']);
    }

    public function test_member_cannot_dispatch_jobs(): void
    {
        $ownerContext = $this->tenantContext();
        $memberContext = $this->memberContext($ownerContext);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(PlatformJobService::class)->dispatch($memberContext, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($memberContext->organizationPublicId, $memberContext->workspacePublicId),
            jobType: 'platform.test',
        ));
    }

    public function test_scheduler_api_creates_task(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->postJson('/api/v1/tenant/scheduled-tasks', [
                'task_type' => 'platform.test',
                'display_name' => 'API Task',
                'cron_expression' => '@hourly',
            ])
            ->assertCreated()
            ->assertJsonPath('data.display_name', 'API Task');
    }

    public function test_jobs_api_show_returns_job(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        $result = app(PlatformJobService::class)->dispatch($context, new PlatformJobDispatchRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            jobType: 'platform.test',
            displayName: 'Show me',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/jobs/'.$result->job->publicId)
            ->assertOk()
            ->assertJsonPath('data.public_id', $result->job->publicId);
    }

    public function test_job_cancel_records_audit_event(): void
    {
        $context = $this->tenantContext();

        $job = PlatformJob::query()->create([
            'organization_id' => $context->organization->id,
            'workspace_id' => $context->workspace->id,
            'job_type' => 'platform.pending',
            'display_name' => 'Audit cancel',
            'status' => PlatformJobStatus::Queued,
            'priority' => 'normal',
            'attempts' => 0,
            'max_attempts' => 3,
            'created_by_user_id' => $context->user->id,
            'created_membership_id' => $context->membership->id,
        ]);

        app(PlatformJobService::class)->cancel($context, $job->public_id);

        $this->assertTrue(AuditLog::query()->where('action', AuditAction::PlatformJobCancelled->value)->exists());
    }

    public function test_schedule_helper_supports_simple_expressions(): void
    {
        $helper = app(\App\Services\Enterprise\Scheduler\ScheduleExpressionHelper::class);

        $next = $helper->calculateNextRun('@hourly', null, 'UTC');

        $this->assertNotNull($next);
        $this->assertTrue($next->greaterThan(now()));
    }

    public function test_scheduler_list_api_returns_tasks(): void
    {
        $context = $this->tenantContext();
        $token = $this->issueToken($context->user);

        app(SchedulerService::class)->create($context, new ScheduledTaskRequest(
            scope: new EnterpriseScope($context->organizationPublicId, $context->workspacePublicId),
            taskType: 'platform.test',
            displayName: 'Listed Task',
            cronExpression: '@daily',
        ));

        $this->withBearerToken($token)
            ->withTenantHeaders($context->organizationPublicId, $context->workspacePublicId)
            ->getJson('/api/v1/tenant/scheduled-tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    private function tenantContext(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'jobs-'.uniqid()]);

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
}
