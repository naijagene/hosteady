<?php

namespace Tests\Feature\Services\Runtime;

use App\Enums\RuntimeHealthStatus;
use App\Models\Application;
use App\Models\WorkspaceApplicationSetting;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Runtime\RuntimeDependencyValidator;
use App\Services\Runtime\RuntimeIntegrityValidator;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class RuntimeIntegrityValidatorTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_validates_runtime_fingerprint(): void
    {
        $report = app(RuntimeIntegrityValidator::class)->validate($this->tenantContextWithDemo());

        $this->assertTrue($report->fingerprintValid);
    }

    public function test_integrity_report_is_healthy_for_active_runtime(): void
    {
        $report = app(RuntimeIntegrityValidator::class)->validate($this->tenantContextWithDemo());

        $this->assertTrue($report->fingerprintValid);
        $this->assertSame(RuntimeHealthStatus::Healthy, $report->status);
    }

    public function test_warns_on_orphan_workspace_settings(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        WorkspaceApplicationSetting::query()->create([
            'id' => (string) \Illuminate\Support\Str::uuid7(),
            'public_id' => (string) \Illuminate\Support\Str::uuid7(),
            'workspace_application_id' => $demo->id,
            'setting_key' => 'legacy.custom',
            'setting_value' => 'value',
            'setting_type' => 'string',
            'version' => 1,
            'is_sensitive' => false,
            'is_encrypted' => false,
        ]);

        $report = app(RuntimeIntegrityValidator::class)->validate($context);

        $this->assertNotEmpty($report->warnings);
    }

    public function test_dependency_validator_detects_missing_dependency(): void
    {
        $context = $this->tenantContextWithDemo();
        Application::query()->where('key', 'demo')->update([
            'dependencies' => ['missing-app'],
        ]);

        $report = app(RuntimeDependencyValidator::class)->validate($context);

        $this->assertNotEmpty($report->missingDependencies);
        $this->assertSame(RuntimeHealthStatus::Warning, $report->status);
    }

    public function test_dependency_validator_detects_duplicate_dependency_declaration(): void
    {
        $context = $this->tenantContextWithDemo();
        Application::query()->where('key', 'demo')->update([
            'dependencies' => ['core', 'core'],
        ]);

        $report = app(RuntimeDependencyValidator::class)->validate($context);

        $this->assertNotEmpty($report->duplicateDependencies);
        $this->assertSame(RuntimeHealthStatus::Critical, $report->status);
    }

    public function test_dependency_validator_detects_circular_dependency(): void
    {
        $context = $this->tenantContextWithDemo();

        Application::query()->where('key', 'core')->update(['dependencies' => ['demo']]);
        Application::query()->where('key', 'demo')->update(['dependencies' => ['core']]);

        $report = app(RuntimeDependencyValidator::class)->validate($context);

        $this->assertNotEmpty($report->circularDependencies);
        $this->assertSame(RuntimeHealthStatus::Critical, $report->status);
    }

    public function test_dependency_validator_detects_disabled_dependency(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        app(WorkspaceApplicationService::class)->disable($context, $demo->public_id);

        Application::query()->where('key', 'workspace')->update([
            'dependencies' => ['demo'],
        ]);

        $report = app(RuntimeDependencyValidator::class)->validate($context);

        $this->assertNotEmpty($report->disabledDependencies);
    }

    public function test_settings_validation_accepts_definition_backed_values(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        app(WorkspaceSettingsService::class)->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $report = app(RuntimeIntegrityValidator::class)->validate($context);

        $this->assertSame([], array_filter($report->errors, fn ($error) => str_contains($error, 'type mismatch')));
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'runtime-integrity-org']);
        $organization = $this->findProvisionedOrganization($result);
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();
        $context = TenantContext::fromModels($user, $organization, $membership, $workspace);

        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $installation = app(ApplicationInstallationService::class)->install($context, $demo->public_id);
        app(WorkspaceApplicationService::class)->enable($context, $installation->public_id);

        return $context;
    }

    private function demoWorkspaceApplication(TenantContext $context): \App\Models\WorkspaceApplication
    {
        $demo = Application::query()->where('key', 'demo')->firstOrFail();

        return \App\Models\WorkspaceApplication::query()
            ->where('workspace_id', $context->workspace->id)
            ->where('application_id', $demo->id)
            ->firstOrFail();
    }
}
