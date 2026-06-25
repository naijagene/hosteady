<?php

namespace Tests\Feature\Services\WorkspaceApplication;

use App\Models\Application;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Runtime\Data\RuntimeManifest;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Services\WorkspaceApplication\WorkspaceRuntimeResolver;
use App\Services\WorkspaceApplication\WorkspaceSettingsService;
use App\Support\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceRuntimeManifestBuilderTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_includes_default_settings_when_no_workspace_row_exists(): void
    {
        $manifest = $this->buildManifest($this->tenantContextWithDemo());

        $demo = collect($manifest->applications)->firstWhere('key', 'demo');

        $this->assertArrayHasKey('feature.enabled', $demo->settings);
        $this->assertFalse($demo->settings['feature.enabled']->value);
        $this->assertTrue($demo->settings['feature.enabled']->isDefault);
        $this->assertSame(0, $demo->settings['feature.enabled']->version);
    }

    public function test_workspace_override_takes_precedence_over_default(): void
    {
        $context = $this->tenantContextWithDemo();
        $demo = $this->demoWorkspaceApplication($context);

        app(WorkspaceSettingsService::class)->bulkUpdate($context, $demo->public_id, [
            'feature.enabled' => ['value' => true, 'type' => 'boolean'],
        ]);

        $demoRuntime = collect($this->buildManifest($context)->applications)->firstWhere('key', 'demo');

        $this->assertTrue($demoRuntime->settings['feature.enabled']->value);
        $this->assertFalse($demoRuntime->settings['feature.enabled']->isDefault);
        $this->assertSame(1, $demoRuntime->settings['feature.enabled']->version);
    }

    public function test_includes_definition_metadata_on_runtime_settings(): void
    {
        $setting = collect($this->buildManifest($this->tenantContextWithDemo())->applications)
            ->firstWhere('key', 'demo')
            ->settings['feature.enabled'];

        $this->assertSame('Feature Enabled', $setting->label);
        $this->assertSame('features', $setting->category);
        $this->assertNotNull($setting->definitionPublicId);
    }

    public function test_includes_catalog_capabilities_and_dependencies(): void
    {
        $demo = collect($this->buildManifest($this->tenantContextWithDemo())->applications)
            ->firstWhere('key', 'demo');

        $this->assertEqualsCanonicalizing(['notifications', 'reporting'], $demo->capabilities);
        $this->assertEqualsCanonicalizing(['core', 'workspace'], $demo->dependencies);
    }

    public function test_fingerprint_includes_definition_and_effective_setting_sources(): void
    {
        $demoFingerprint = collect($this->buildManifest($this->tenantContextWithDemo())->fingerprintApplications)
            ->firstWhere('key', 'demo');

        $this->assertNotEmpty($demoFingerprint['definitions']);
        $this->assertSame('default', $demoFingerprint['settings'][0]['value_source']);
    }

    private function buildManifest(TenantContext $context): RuntimeManifest
    {
        $resolver = app(WorkspaceRuntimeResolver::class);
        $method = new \ReflectionMethod($resolver, 'buildManifest');
        $method->setAccessible(true);

        return $method->invoke($resolver, $context);
    }

    private function tenantContextWithDemo(): TenantContext
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'manifest-builder-org']);
        $context = $this->buildTenantContext($user, $result);
        $this->enableDemoApplication($context);

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

    private function enableDemoApplication(TenantContext $context): \App\Models\WorkspaceApplication
    {
        $demo = Application::query()->where('key', 'demo')->firstOrFail();
        $orgInstall = app(ApplicationInstallationService::class)->install($context, $demo->public_id);

        return app(WorkspaceApplicationService::class)->enable($context, $orgInstall->public_id);
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
