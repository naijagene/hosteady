<?php

namespace Tests\Feature\Enums;

use App\Enums\AuditAction;
use App\Enums\AuditCategory;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditSeverity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_tenant_context_selected_event(): void
    {
        $action = AuditAction::TenantContextSelected;

        $this->assertSame('tenant.context.selected', $action->value);
        $this->assertSame(AuditCategory::Tenant, $action->category());
        $this->assertSame(AuditRetentionClass::Ephemeral, $action->defaultRetention());
    }

    public function test_includes_expanded_security_events(): void
    {
        $this->assertSame(
            AuditCategory::Security,
            AuditAction::SecurityTenantInvalidHeader->category(),
        );
        $this->assertSame(
            AuditCategory::Security,
            AuditAction::SecurityAuthUnauthenticated->category(),
        );
        $this->assertSame(
            AuditCategory::Security,
            AuditAction::SecurityPermissionDenied->category(),
        );
        $this->assertSame(
            AuditCategory::Security,
            AuditAction::SecurityInvalidToken->category(),
        );
        $this->assertSame(
            AuditRetentionClass::Ephemeral,
            AuditAction::SecurityAccessDenied->defaultRetention(),
        );
        $this->assertSame(
            AuditRetentionClass::Standard,
            AuditAction::SecurityRoleEscalationAttempt->defaultRetention(),
        );
        $this->assertSame(
            AuditSeverity::Critical,
            AuditAction::SecurityRoleEscalationAttempt->defaultSeverity(),
        );
    }

    public function test_includes_reserved_workspace_runtime_events(): void
    {
        $this->assertSame('workspace.runtime.generated', AuditAction::WorkspaceRuntimeGenerated->value);
        $this->assertSame('workspace.runtime.failed', AuditAction::WorkspaceRuntimeFailed->value);
        $this->assertSame(AuditCategory::Workspace, AuditAction::WorkspaceRuntimeGenerated->category());
        $this->assertSame(AuditRetentionClass::Ephemeral, AuditAction::WorkspaceRuntimeGenerated->defaultRetention());
        $this->assertSame(AuditSeverity::Warning, AuditAction::WorkspaceRuntimeFailed->defaultSeverity());
    }
}
