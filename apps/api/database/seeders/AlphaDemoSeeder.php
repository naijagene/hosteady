<?php

namespace Database\Seeders;

use App\Enums\JoinMethod;
use App\Enums\MembershipStatus;
use App\Models\Application;
use App\Models\Organization;
use App\Models\OrganizationMemberRole;
use App\Models\OrganizationMembership;
use App\Models\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Application\ApplicationInstallationService;
use App\Services\Organization\Data\CreateOrganizationData;
use App\Services\Organization\OrganizationProvisioningService;
use App\Services\WorkspaceApplication\WorkspaceApplicationService;
use App\Support\Tenant\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AlphaDemoSeeder extends Seeder
{
    public const ORGANIZATION_SLUG = 'moondew-group';

    public const ORGANIZATION_NAME = 'Moondew Group';

    public const WORKSPACE_NAME = 'Production';

    public const WORKSPACE_SLUG = 'production';

    public const SAMPLE_APPLICATION_KEY = 'demo';

    public const SAMPLE_APPLICATION_DISPLAY_NAME = 'Hosteady Platform Preview';

    /**
     * @var array<string, array{display_name: string, email: string, role_key: string}>
     */
    private const DEMO_USERS = [
        'admin' => [
            'display_name' => 'BIGJYDE',
            'email' => 'bigjyde@alpha.demo.local',
            'role_key' => 'administrator',
        ],
        'manager' => [
            'display_name' => 'Alpha Manager',
            'email' => 'manager@alpha.demo.local',
            'role_key' => 'manager',
        ],
        'viewer' => [
            'display_name' => 'Alpha Viewer',
            'email' => 'viewer@alpha.demo.local',
            'role_key' => 'viewer',
        ],
    ];

    public function run(): void
    {
        $password = env('ALPHA_DEMO_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->command?->error('Set ALPHA_DEMO_PASSWORD in apps/api/.env before running AlphaDemoSeeder.');
            $this->command?->line('Example: ALPHA_DEMO_PASSWORD=your-local-placeholder');

            return;
        }

        if (Organization::query()->where('slug', self::ORGANIZATION_SLUG)->whereNull('deleted_at')->exists()) {
            $this->command?->info('Alpha demo tenant already provisioned (Moondew Group). Skipping.');

            return;
        }

        $this->call(PlatformBootstrapSeeder::class);
        $this->renameSampleApplication();

        DB::transaction(function () use ($password) {
            $adminUser = $this->createDemoUser(self::DEMO_USERS['admin'], $password);
            $provisioned = app(OrganizationProvisioningService::class)->provision(new CreateOrganizationData(
                creatorUserId: $adminUser->id,
                name: self::ORGANIZATION_NAME,
                slug: self::ORGANIZATION_SLUG,
                timezone: 'UTC',
                locale: 'en',
                planTier: 'business',
                organizationCode: 'MOONDEW',
            ));

            $organization = Organization::query()->where('public_id', $provisioned->organizationPublicId)->firstOrFail();
            $workspace = Workspace::query()->where('public_id', $provisioned->workspacePublicId)->firstOrFail();

            $workspace->fill([
                'name' => self::WORKSPACE_NAME,
                'slug' => self::WORKSPACE_SLUG,
            ]);
            $workspace->applyAuditActor($adminUser->id)->save();

            $adminMembership = $organization->memberships()->where('user_id', $adminUser->id)->firstOrFail();
            $this->assignRole($organization, $adminMembership, 'administrator', $adminUser->id);

            $managerUser = $this->createDemoUser(self::DEMO_USERS['manager'], $password);
            $viewerUser = $this->createDemoUser(self::DEMO_USERS['viewer'], $password);

            $managerMembership = $this->createMembership($organization, $managerUser, $workspace, $adminUser->id);
            $viewerMembership = $this->createMembership($organization, $viewerUser, $workspace, $adminUser->id);

            $this->assignRole($organization, $managerMembership, 'manager', $adminUser->id);
            $this->assignRole($organization, $viewerMembership, 'viewer', $adminUser->id);

            $context = TenantContext::fromModels($adminUser, $organization, $adminMembership, $workspace);
            app()->instance(TenantContext::class, $context);

            $this->installSampleApplication($context);

            $this->command?->info('Alpha demo tenant provisioned.');
            $this->command?->line('Organization: '.self::ORGANIZATION_NAME.' ('.$organization->public_id.')');
            $this->command?->line('Workspace: '.self::WORKSPACE_NAME.' ('.$workspace->public_id.')');
            $this->command?->line('Administrator: '.self::DEMO_USERS['admin']['email'].' (BIGJYDE)');
            $this->command?->warn('Metadata, workflow, notification, and audit samples require follow-up steps in HEOS_ALPHA_PROVISIONING_PLAN.md.');
        });

        $this->seedOptionalExperienceMetadata();
    }

    private function renameSampleApplication(): void
    {
        Application::query()
            ->where('key', self::SAMPLE_APPLICATION_KEY)
            ->update(['name' => self::SAMPLE_APPLICATION_DISPLAY_NAME]);
    }

    /**
     * @param  array{display_name: string, email: string, role_key: string}  $definition
     */
    private function createDemoUser(array $definition, string $password): User
    {
        $existing = User::query()->where('email', $definition['email'])->first();

        if ($existing !== null) {
            return $existing;
        }

        return User::query()->create([
            'public_id' => (string) Str::uuid7(),
            'name' => $definition['display_name'],
            'display_name' => $definition['display_name'],
            'email' => $definition['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'status' => 'active',
        ]);
    }

    private function createMembership(
        Organization $organization,
        User $user,
        Workspace $workspace,
        int $actorUserId,
    ): OrganizationMembership {
        $membership = new OrganizationMembership([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'default_workspace_id' => $workspace->id,
            'invited_by_user_id' => $actorUserId,
            'join_method' => JoinMethod::System,
        ]);
        $membership->applyAuditActor($actorUserId)->save();

        return $membership;
    }

    private function assignRole(
        Organization $organization,
        OrganizationMembership $membership,
        string $roleKey,
        int $actorUserId,
    ): void {
        $role = Role::query()
            ->where('organization_id', $organization->id)
            ->where('key', $roleKey)
            ->where('is_system', true)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $exists = OrganizationMemberRole::query()
            ->where('organization_membership_id', $membership->id)
            ->where('role_id', $role->id)
            ->exists();

        if ($exists) {
            return;
        }

        OrganizationMemberRole::query()->create([
            'organization_membership_id' => $membership->id,
            'role_id' => $role->id,
            'created_at' => now(),
            'created_by_user_id' => $actorUserId,
            'updated_at' => now(),
            'updated_by_user_id' => $actorUserId,
        ]);
    }

    private function installSampleApplication(TenantContext $context): void
    {
        $application = Application::query()->where('key', self::SAMPLE_APPLICATION_KEY)->firstOrFail();

        $organizationApplication = app(ApplicationInstallationService::class)->install(
            $context,
            $application->public_id,
        );

        app(WorkspaceApplicationService::class)->enable(
            $context,
            $organizationApplication->public_id,
            isBootstrap: false,
        );
    }

    private function seedOptionalExperienceMetadata(): void
    {
        if (! Schema::hasTable('ui_pages')) {
            $this->command?->warn('UI metadata tables missing. Run migrations before optional Alpha metadata seeding.');

            return;
        }

        $organization = Organization::query()->where('slug', self::ORGANIZATION_SLUG)->first();

        if ($organization === null) {
            return;
        }

        $adminUser = User::query()->where('email', self::DEMO_USERS['admin']['email'])->first();

        if ($adminUser === null) {
            return;
        }

        $workspace = $organization->workspaces()->where('slug', self::WORKSPACE_SLUG)->first();
        $membership = $organization->memberships()->where('user_id', $adminUser->id)->first();

        if ($workspace === null || $membership === null) {
            return;
        }

        $context = TenantContext::fromModels($adminUser, $organization, $membership, $workspace);
        app()->instance(TenantContext::class, $context);

        try {
            app(\App\Services\Theme\ThemeDevelopmentService::class)->registerDefinition($context, [
                'theme_key' => 'alpha-default',
                'name' => 'Alpha Default Theme',
            ]);
        } catch (\Throwable $exception) {
            $this->command?->warn('Theme seed skipped: '.$exception->getMessage());
        }

        try {
            app(\App\Services\Navigation\NavigationDevelopmentService::class)->registerDefinition($context, [
                'navigation_key' => 'alpha-primary',
                'name' => 'Alpha Primary Navigation',
                'navigation_type' => 'primary',
            ]);
        } catch (\Throwable $exception) {
            $this->command?->warn('Navigation seed skipped: '.$exception->getMessage());
        }

        try {
            app(\App\Services\Ui\UiDevelopmentService::class)->registerPage($context, [
                'module_key' => 'alpha.preview',
                'page_key' => 'home',
                'name' => 'Alpha Preview Home',
                'page_type' => 'module_home',
                'route_path' => '/pages/alpha-preview-home',
            ]);
        } catch (\Throwable $exception) {
            $this->command?->warn('UI page seed skipped: '.$exception->getMessage());
        }

        $this->registerMinimalDynamicArtifact($context, 'form', \App\Services\Form\DynamicFormDevelopmentService::class, 'registerDefinition', [
            'module_key' => 'alpha.preview',
            'form_key' => 'sample',
            'name' => 'Alpha Sample Form',
            'type' => 'create',
            'fields' => [['key' => 'name', 'label' => 'Name', 'type' => 'string', 'required' => true]],
        ]);

        $this->registerMinimalDynamicArtifact($context, 'table', \App\Services\Table\DynamicTableDevelopmentService::class, 'registerDefinition', [
            'module_key' => 'alpha.preview',
            'table_key' => 'sample',
            'name' => 'Alpha Sample Table',
            'columns' => [['key' => 'name', 'label' => 'Name', 'type' => 'string']],
        ]);

        $this->registerMinimalDynamicArtifact($context, 'dashboard', \App\Services\Dashboard\DynamicDashboardDevelopmentService::class, 'registerDefinition', [
            'module_key' => 'alpha.preview',
            'dashboard_key' => 'sample',
            'name' => 'Alpha Sample Dashboard',
            'widgets' => [['widget_key' => 'metric', 'widget_type' => 'metric', 'title' => 'Sample Metric']],
        ]);

        $this->registerMinimalDynamicArtifact($context, 'report', \App\Services\Report\DynamicReportDevelopmentService::class, 'registerDefinition', [
            'module_key' => 'alpha.preview',
            'report_key' => 'sample',
            'name' => 'Alpha Sample Report',
            'columns' => [['key' => 'name', 'label' => 'Name', 'type' => 'string']],
        ]);
    }

    /**
     * @param  class-string  $serviceClass
     * @param  array<string, mixed>  $payload
     */
    private function registerMinimalDynamicArtifact(
        TenantContext $context,
        string $label,
        string $serviceClass,
        string $method,
        array $payload,
    ): void {
        try {
            app($serviceClass)->{$method}($context, $payload);
        } catch (\Throwable $exception) {
            $this->command?->warn(ucfirst($label).' seed skipped: '.$exception->getMessage());
        }
    }
}
