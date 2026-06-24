<?php

namespace Tests\Feature\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditSeverity;
use App\Enums\InvitationStatus;
use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Invitation\Data\AcceptInvitationData;
use App\Services\Invitation\Data\CreateInvitationData;
use App\Services\Invitation\InvitationService;
use App\Services\Organization\Data\CreateOrganizationData;
use App\Services\Organization\OrganizationProvisioningService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\InteractsWithHeosApi;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class DomainAuditInstrumentationTest extends TestCase
{
    use InteractsWithHeosApi;
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_login_success_records_audit_event(): void
    {
        $user = User::factory()->create([
            'email' => 'audit-login@example.com',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'audit-login@example.com',
            'password' => 'secret',
        ])->assertOk();

        $log = AuditLog::query()->where('action', AuditAction::AuthLoginSucceeded)->first();

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Standard, $log->retention_class);
        $this->assertSame($user->public_id, $log->entity_public_id);
    }

    public function test_login_failure_records_audit_event(): void
    {
        User::factory()->create([
            'email' => 'audit-fail@example.com',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'audit-fail@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $log = AuditLog::query()->where('action', AuditAction::AuthLoginFailed)->first();

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Ephemeral, $log->retention_class);
        $this->assertArrayHasKey('email_hash', $log->metadata ?? []);
    }

    public function test_logout_records_audit_event(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertNotNull(
            AuditLog::query()->where('action', AuditAction::AuthLogoutSucceeded)->first(),
        );
    }

    public function test_invalid_token_records_security_event(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        $log = AuditLog::query()->where('action', AuditAction::SecurityInvalidToken)->first();

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Ephemeral, $log->retention_class);
    }

    public function test_organization_provisioning_records_audit_events(): void
    {
        $this->seedHeosPermissions();

        $creator = $this->createActiveUser();

        app(OrganizationProvisioningService::class)->provision(new CreateOrganizationData(
            creatorUserId: $creator->id,
            name: 'Audit Org',
            slug: 'audit-org',
        ));

        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::OrganizationCreated)->first());
        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::OrganizationStatusChanged)->first());
        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::WorkspaceCreated)->first());
        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::MembershipCreated)->first());
    }

    public function test_invitation_create_records_audit_event(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'invite-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        app(InvitationService::class)->create(new CreateInvitationData(
            organizationPublicId: $result->organizationPublicId,
            email: 'invitee@example.com',
            invitedByUserId: $owner->id,
            rolePublicIds: [$memberRole->public_id],
        ));

        $log = AuditLog::query()->where('action', AuditAction::InvitationCreated)->first();

        $this->assertNotNull($log);
        $this->assertSame('invitee@example.com', $log->entity_label);
        $this->assertArrayNotHasKey('token_hash', $log->after_state['snapshot'] ?? []);
    }

    public function test_invitation_accept_records_audit_event(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'invite-accept-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        $created = app(InvitationService::class)->create(new CreateInvitationData(
            organizationPublicId: $result->organizationPublicId,
            email: 'accepted@example.com',
            invitedByUserId: $owner->id,
            rolePublicIds: [$memberRole->public_id],
        ));

        $invitee = $this->createActiveUser(['email' => 'accepted@example.com']);

        app(InvitationService::class)->accept(new AcceptInvitationData(
            plainToken: $created->plainToken,
            acceptingUserId: $invitee->id,
        ));

        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::InvitationAccepted)->first());
    }

    public function test_invitation_revoke_records_audit_event(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'invite-revoke-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        $created = app(InvitationService::class)->create(new CreateInvitationData(
            organizationPublicId: $result->organizationPublicId,
            email: 'revoked@example.com',
            invitedByUserId: $owner->id,
            rolePublicIds: [$memberRole->public_id],
        ));

        app(InvitationService::class)->revoke($created->invitationPublicId, $owner->id);

        $log = AuditLog::query()->where('action', AuditAction::InvitationRevoked)->first();

        $this->assertNotNull($log);
        $this->assertSame(InvitationStatus::Revoked->value, $log->after_state['snapshot']['status'] ?? null);
    }

    public function test_invitation_expiry_records_audit_event(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'invite-expired-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        $created = app(InvitationService::class)->create(new CreateInvitationData(
            organizationPublicId: $result->organizationPublicId,
            email: 'expired@example.com',
            invitedByUserId: $owner->id,
            rolePublicIds: [$memberRole->public_id],
            expiresInDays: 1,
        ));

        Invitation::query()
            ->where('public_id', $created->invitationPublicId)
            ->update(['expires_at' => now()->subDay()]);

        $invitee = $this->createActiveUser(['email' => 'expired@example.com']);

        try {
            app(InvitationService::class)->accept(new AcceptInvitationData(
                plainToken: $created->plainToken,
                acceptingUserId: $invitee->id,
            ));
        } catch (\App\Exceptions\Invitation\InvitationExpiredException) {
            // expected
        }

        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::InvitationExpired)->first());
    }

    public function test_application_install_records_audit_event(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'app-install-audit-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        app()->instance(TenantContext::class, $context);

        app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        $log = AuditLog::query()->where('action', AuditAction::ApplicationInstalled)->first();

        $this->assertNotNull($log);
        $this->assertSame('organization_application', $log->entity_type);
    }

    public function test_application_enable_disable_and_uninstall_record_audit_events(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'app-lifecycle-audit-org']);
        $context = $this->buildTenantContext($user, $result);
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $service = app(ApplicationInstallationService::class);

        app()->instance(TenantContext::class, $context);

        $installation = $service->install($context, $demo->public_id);
        $service->disable($context, $installation->public_id);
        $service->enable($context, $installation->public_id);
        $service->uninstall($context, $installation->public_id);

        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::ApplicationDisabled)->first());
        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::ApplicationEnabled)->first());
        $this->assertNotNull(AuditLog::query()->where('action', AuditAction::ApplicationUninstalled)->first());
    }

    public function test_core_application_block_records_security_event(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'core-block-audit-org']);
        $context = $this->buildTenantContext($user, $result);
        $core = Application::query()->where('key', 'core')->firstOrFail();
        $service = app(ApplicationInstallationService::class);

        app()->instance(TenantContext::class, $context);

        $installation = $service->install($context, $core->public_id);

        try {
            $service->disable($context, $installation->public_id);
        } catch (\App\Exceptions\Application\CoreApplicationProtectedException) {
            // expected
        }

        $log = AuditLog::query()->where('action', AuditAction::SecurityCoreActionBlocked)->first();

        $this->assertNotNull($log);
        $this->assertSame('disable', $log->metadata['attempted_action'] ?? null);
    }

    public function test_permission_denied_records_security_event(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'permission-denied-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $member = $this->createActiveUser();
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        $organization->memberships()->create([
            'user_id' => $member->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'join_method' => JoinMethod::Invitation,
        ])->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->issueToken($member);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events')
            ->assertForbidden();

        $this->assertNotNull(
            AuditLog::query()->where('action', AuditAction::SecurityPermissionDenied)->first(),
        );
    }

    public function test_tenant_rejection_records_security_event(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $token = $this->issueToken($user);

        $this->withBearerToken($token)
            ->withTenantHeaders('01999999-9999-7999-8999-999999999999')
            ->getJson('/api/v1/tenant/context')
            ->assertNotFound();

        $this->assertNotNull(
            AuditLog::query()->where('action', AuditAction::SecurityTenantRejected)->first(),
        );
    }

    public function test_role_escalation_attempt_records_security_event(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'role-escalation-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $ownerRole = Role::query()->where('organization_id', $organization->id)->where('key', 'owner')->firstOrFail();

        try {
            app(InvitationService::class)->create(new CreateInvitationData(
                organizationPublicId: $result->organizationPublicId,
                email: 'owner-invite@example.com',
                invitedByUserId: $owner->id,
                rolePublicIds: [$ownerRole->public_id],
            ));
        } catch (\App\Exceptions\Invitation\InvitationException) {
            // expected
        }

        $log = AuditLog::query()->where('action', AuditAction::SecurityRoleEscalationAttempt)->first();

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Standard, $log->retention_class);
    }

    public function test_recorder_failure_does_not_break_login(): void
    {
        $this->mock(AuditEventRecorder::class, function ($mock) {
            $mock->shouldReceive('record')->andThrow(new \RuntimeException('audit unavailable'));
        });

        $user = User::factory()->create([
            'email' => 'resilient@example.com',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'resilient@example.com',
            'password' => 'secret',
        ])->assertOk()
            ->assertJsonPath('data.user.public_id', $user->public_id);
    }

    public function test_audit_snapshots_do_not_leak_sensitive_fields(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'sensitive-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        app(InvitationService::class)->create(new CreateInvitationData(
            organizationPublicId: $result->organizationPublicId,
            email: 'safe@example.com',
            invitedByUserId: $owner->id,
            rolePublicIds: [$memberRole->public_id],
        ));

        AuditLog::query()->each(function (AuditLog $log) {
            $encoded = json_encode([
                $log->before_state,
                $log->after_state,
                $log->metadata,
            ]);

            $this->assertStringNotContainsString('password', strtolower($encoded));
            $this->assertStringNotContainsString('token_hash', strtolower($encoded));
            $this->assertStringNotContainsString('plain_text_token', strtolower($encoded));
        });
    }

    public function test_auth_events_apply_retention_and_severity_defaults(): void
    {
        $user = User::factory()->create([
            'email' => 'severity@example.com',
            'password' => Hash::make('secret'),
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'severity@example.com',
            'password' => 'secret',
        ])->assertOk();

        $success = AuditLog::query()->where('action', AuditAction::AuthLoginSucceeded)->first();
        $this->assertNotNull($success);
        $this->assertSame(AuditRetentionClass::Standard, $success->retention_class);
        $this->assertSame(AuditSeverity::Info, $success->severity);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'severity@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $failure = AuditLog::query()->where('action', AuditAction::AuthLoginFailed)->first();
        $this->assertNotNull($failure);
        $this->assertSame(AuditRetentionClass::Ephemeral, $failure->retention_class);
        $this->assertSame(AuditSeverity::Warning, $failure->severity);
    }

    public function test_organization_events_use_permanent_retention(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();

        app(OrganizationProvisioningService::class)->provision(new CreateOrganizationData(
            name: 'Permanent Audit Org',
            slug: 'permanent-audit-org',
            creatorUserId: $owner->id,
        ));

        $logs = AuditLog::query()
            ->whereIn('action', [
                AuditAction::OrganizationCreated,
                AuditAction::OrganizationStatusChanged,
                AuditAction::WorkspaceCreated,
                AuditAction::MembershipCreated,
            ])
            ->get();

        $this->assertCount(4, $logs);

        foreach ($logs as $log) {
            $this->assertSame(AuditRetentionClass::Permanent, $log->retention_class);
            $this->assertSame(AuditSeverity::Info, $log->severity);
        }
    }

    public function test_invitation_audit_events_exclude_token_fields(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'invitation-token-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        app(InvitationService::class)->create(new CreateInvitationData(
            organizationPublicId: $result->organizationPublicId,
            email: 'token-safe@example.com',
            invitedByUserId: $owner->id,
            rolePublicIds: [$memberRole->public_id],
        ));

        $invitation = Invitation::query()->where('email', 'token-safe@example.com')->firstOrFail();

        AuditLog::query()
            ->where('action', AuditAction::InvitationCreated)
            ->each(function (AuditLog $log) use ($invitation) {
                $encoded = json_encode([
                    $log->before_state,
                    $log->after_state,
                    $log->metadata,
                ]);

                $this->assertStringNotContainsString($invitation->token_hash, $encoded);
                $this->assertStringNotContainsString('token_hash', strtolower($encoded));
                $this->assertStringNotContainsString('plain_text_token', strtolower($encoded));
            });
    }

    public function test_security_permission_denied_uses_ephemeral_retention_and_warning_severity(): void
    {
        $this->seedHeosPermissions();

        $owner = $this->createActiveUser();
        $result = $this->provisionTestOrganization($owner, ['slug' => 'permission-retention-audit-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $member = $this->createActiveUser();
        $memberRole = Role::query()->where('organization_id', $organization->id)->where('key', 'member')->firstOrFail();

        $organization->memberships()->create([
            'user_id' => $member->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'join_method' => JoinMethod::Invitation,
        ])->memberRoles()->create([
            'role_id' => $memberRole->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->issueToken($member);

        $this->withBearerToken($token)
            ->withTenantHeaders($result->organizationPublicId)
            ->getJson('/api/v1/tenant/audit/events')
            ->assertForbidden();

        $log = AuditLog::query()->where('action', AuditAction::SecurityPermissionDenied)->first();

        $this->assertNotNull($log);
        $this->assertSame(AuditRetentionClass::Ephemeral, $log->retention_class);
        $this->assertSame(AuditSeverity::Warning, $log->severity);
    }

    private function buildTenantContext(
        User $user,
        \App\Services\Organization\Data\ProvisionedOrganizationResult $result,
    ): TenantContext {
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        return TenantContext::fromModels($user, $organization, $membership, $workspace);
    }
}
