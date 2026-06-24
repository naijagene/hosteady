<?php

namespace Tests\Feature\Services\WorkspaceApplication;

use App\Enums\WorkspaceApplicationStatus;
use App\Models\Application;
use App\Models\WorkspaceApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InteractsWithHeosPlatform;
use Tests\TestCase;

class WorkspaceApplicationBootstrapServiceTest extends TestCase
{
    use InteractsWithHeosPlatform;
    use RefreshDatabase;

    public function test_provisions_org_installs_and_default_workspace_activations(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'bootstrap-wa-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $this->assertSame(2, $organization->organizationApplications()->count());

        $workspaceApplications = WorkspaceApplication::query()
            ->where('workspace_id', $workspace->id)
            ->get();

        $this->assertCount(2, $workspaceApplications);
        $this->assertTrue($workspaceApplications->every(
            fn (WorkspaceApplication $workspaceApplication) => $workspaceApplication->status === WorkspaceApplicationStatus::Active
                && $workspaceApplication->is_bootstrap === true,
        ));

        $keys = $workspaceApplications->load('application')->pluck('application.key')->all();
        $this->assertEqualsCanonicalizing(['core', 'workspace'], $keys);
    }

    public function test_bootstrap_is_idempotent_when_provision_runs_once(): void
    {
        $this->seedHeosPlatform();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'bootstrap-idempotent-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $this->assertSame(
            2,
            WorkspaceApplication::query()->where('workspace_id', $workspace->id)->count(),
        );
    }

    public function test_skips_bootstrap_when_application_catalog_is_missing(): void
    {
        $this->seedHeosPermissions();

        $user = $this->createActiveUser();
        $result = $this->provisionTestOrganization($user, ['slug' => 'bootstrap-no-catalog-org']);
        $organization = $this->findProvisionedOrganization($result);
        $workspace = $organization->workspaces()->where('public_id', $result->workspacePublicId)->firstOrFail();

        $this->assertSame(0, $organization->organizationApplications()->count());
        $this->assertSame(0, WorkspaceApplication::query()->where('workspace_id', $workspace->id)->count());
    }
}
