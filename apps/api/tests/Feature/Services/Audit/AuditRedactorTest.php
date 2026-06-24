<?php

namespace Tests\Feature\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Models\AuditLog;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\AuditRedactor;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Http\RequestContext;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class AuditRedactorTest extends TestCase
{
    use RefreshDatabase;

    private AuditRedactor $redactor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redactor = app(AuditRedactor::class);
    }

    public function test_redacts_organization_application_snapshot_fields(): void
    {
        $redacted = $this->redactor->redact([
            'snapshot' => [
                'status' => 'active',
                'installed_version' => '1.0.0',
                'password' => 'secret',
                'token_hash' => 'abc',
            ],
        ], AuditEntityType::OrganizationApplication);

        $this->assertSame([
            'snapshot' => [
                'status' => 'active',
                'installed_version' => '1.0.0',
            ],
        ], $redacted);
    }

    public function test_redacts_field_diffs_using_allowlist(): void
    {
        $redacted = $this->redactor->redact([
            'fields' => [
                'status' => ['from' => 'active', 'to' => 'disabled'],
                'password' => ['from' => 'old', 'to' => 'new'],
            ],
        ], AuditEntityType::OrganizationApplication);

        $this->assertSame([
            'fields' => [
                'status' => ['from' => 'active', 'to' => 'disabled'],
            ],
            'snapshot' => null,
        ], $redacted);
    }

    public function test_returns_null_for_null_state(): void
    {
        $this->assertNull($this->redactor->redact(null, AuditEntityType::OrganizationApplication));
    }
}
