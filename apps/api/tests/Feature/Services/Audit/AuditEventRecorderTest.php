<?php

namespace Tests\Feature\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Models\AuditLog;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Http\RequestContext;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class AuditEventRecorderTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    private AuditEventRecorder $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recorder = app(AuditEventRecorder::class);
    }

    public function test_records_organization_scoped_event_with_actor_and_request_context(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-recorder-org']);
        $context = $this->buildTenantContext($user, $result);

        app()->instance(TenantContext::class, $context);
        app()->instance(RequestContext::class, new RequestContext(
            requestId: (string) Str::uuid7(),
            ipAddress: '203.0.113.10',
            userAgent: 'PHPUnit Agent',
        ));

        $log = $this->recorder->record(new AuditEventData(
            action: AuditAction::ApplicationInstalled,
            summary: 'Demo Application installed',
            entityType: AuditEntityType::OrganizationApplication,
            entityPublicId: (string) Str::uuid7(),
            entityLabel: 'Demo Application',
            afterState: [
                'snapshot' => [
                    'status' => 'active',
                    'installed_version' => '1.0.0',
                ],
            ],
            eventVersion: 1,
        ));

        $this->assertNotNull($log);
        $this->assertSame(AuditAction::ApplicationInstalled, $log->action);
        $this->assertSame(AuditScope::Organization, $log->scope);
        $this->assertSame(AuditActorType::User, $log->actor_type);
        $this->assertSame($user->id, $log->actor_user_id);
        $this->assertSame($result->membershipPublicId, $log->actorMembership->public_id);
        $this->assertSame(1, $log->event_version);
        $this->assertSame(AuditRetentionClass::Permanent, $log->retention_class);
        $this->assertNull($log->expires_at);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('PHPUnit Agent', $log->user_agent);
        $this->assertNotNull($log->request_id);
        $this->assertSame([
            'snapshot' => [
                'status' => 'active',
                'installed_version' => '1.0.0',
            ],
        ], $log->after_state);
    }

    public function test_sets_standard_retention_expiry_to_five_years(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-retention-org']);
        $context = $this->buildTenantContext($user, $result);

        app()->instance(TenantContext::class, $context);

        $log = $this->recorder->record(new AuditEventData(
            action: AuditAction::AuthLoginSucceeded,
            summary: 'User logged in',
        ));

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Standard, $log->retention_class);
        $this->assertNotNull($log->expires_at);
        $this->assertSame(
            now()->addDays(1825)->toDateString(),
            $log->expires_at->toDateString(),
        );
    }

    public function test_sets_ephemeral_retention_expiry_to_one_hundred_eighty_days(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'audit-ephemeral-org']);
        $context = $this->buildTenantContext($user, $result);

        app()->instance(TenantContext::class, $context);

        $log = $this->recorder->record(new AuditEventData(
            action: AuditAction::TenantContextSelected,
            summary: 'Tenant context selected',
        ));

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Ephemeral, $log->retention_class);
        $this->assertSame(
            now()->addDays(180)->toDateString(),
            $log->expires_at->toDateString(),
        );
    }

    public function test_does_not_block_when_persist_fails(): void
    {
        $log = $this->recorder->record(new AuditEventData(
            action: AuditAction::OrganizationCreated,
            summary: 'Missing organization scope',
        ));

        $this->assertNull($log);
        $this->assertSame(0, AuditLog::query()->count());
    }

    private function buildTenantContext(
        \App\Models\User $user,
        \App\Services\Organization\Data\ProvisionedOrganizationResult $result,
    ): TenantContext {
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
