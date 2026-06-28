<?php

namespace App\Services\Authorization;

use App\Enums\RoleStatus;
use App\Exceptions\Organization\OrganizationProvisioningException;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class SystemRoleProvisioner
{
    private const EXPECTED_PERMISSION_COUNT = 118;

    /**
     * @var array<string, string>
     */
    private const SYSTEM_ROLE_NAMES = [
        'owner' => 'Owner',
        'administrator' => 'Administrator',
        'manager' => 'Manager',
        'member' => 'Member',
        'viewer' => 'Viewer',
    ];

    /**
     * @var list<string>
     */
    private const MEMBER_PERMISSIONS = [
        'organization.read',
        'workspace.read',
        'workspace.applications.read',
        'applications.read',
        'notifications.read',
        'reference.read',
        'files.read',
        'files.upload',
        'jobs.read',
        'search.read',
        'workflow.read',
        'workflow.runtime.read',
        'task.read',
        'approval.read',
        'approval.decide',
        'workflow.automation.read',
        'workflow.designer.read',
        'workflow.designer.export',
        'workflow.marketplace.read',
        'workflow.marketplace.install',
        'workflow.marketplace.export',
        'business.modules.read',
        'entities.read',
        'entities.comment',
        'entities.tag',
        'forms.read',
        'forms.submit',
        'forms.draft',
        'tables.read',
        'tables.query',
        'tables.export',
        'dashboards.read',
        'dashboards.render',
        'reports.read',
        'reports.run',
        'reports.export',
        'data.records.read',
        'data.records.create',
        'data.records.update',
        'data.records.link',
        'documents.read',
        'documents.upload',
        'documents.update',
        'documents.attach',
        'rules.read',
        'rules.evaluate',
        'integrations.read',
        'integrations.publish',
        'application.read',
        'navigation.read',
        'ui.personalize',
        'ui.read',
        'ui.render',
    ];

    /**
     * @var list<string>
     */
    private const VIEWER_PERMISSIONS = [
        'organization.read',
        'workspace.read',
        'workspace.applications.read',
        'applications.read',
        'members.read',
        'roles.read',
        'notifications.read',
        'reference.read',
        'files.read',
        'jobs.read',
        'search.read',
        'workflow.read',
        'workflow.runtime.read',
        'task.read',
        'approval.read',
        'workflow.automation.read',
        'workflow.designer.read',
        'workflow.marketplace.read',
        'business.modules.read',
        'entities.read',
        'forms.read',
        'tables.read',
        'dashboards.read',
        'reports.read',
        'documents.read',
        'rules.read',
        'integrations.read',
        'application.read',
        'navigation.read',
        'ui.read',
        'ui.render',
    ];

    /**
     * @var list<string>
     */
    private const MANAGER_EXCLUDED = [
        'organization.archive',
        'roles.manage',
        'members.remove',
        'jobs.manage',
        'scheduler.manage',
        'workflow.publish',
        'business.modules.manage',
        'business.modules.develop',
        'rules.admin',
        'integrations.admin',
    ];

    /**
     * @var list<string>
     */
    private const ADMINISTRATOR_EXCLUDED = [
        'organization.archive',
    ];

    public function provisionForOrganization(Organization $organization, int $actorUserId): void
    {
        if (Role::query()
            ->where('organization_id', $organization->id)
            ->where('is_system', true)
            ->whereNull('deleted_at')
            ->exists()) {
            throw new OrganizationProvisioningException('System roles already exist for this organization.');
        }

        $permissions = Permission::query()
            ->orderBy('key')
            ->get()
            ->keyBy('key');

        if ($permissions->count() !== self::EXPECTED_PERMISSION_COUNT) {
            throw new OrganizationProvisioningException('Permission catalog is incomplete.');
        }

        $allPermissionKeys = $permissions->keys()->all();

        foreach (self::SYSTEM_ROLE_NAMES as $key => $name) {
            $role = new Role([
                'organization_id' => $organization->id,
                'key' => $key,
                'name' => $name,
                'is_system' => true,
                'status' => RoleStatus::Active,
            ]);
            $role->applyAuditActor($actorUserId)->save();

            $permissionKeys = match ($key) {
                'owner' => $allPermissionKeys,
                'administrator' => array_values(array_diff($allPermissionKeys, self::ADMINISTRATOR_EXCLUDED)),
                'manager' => array_values(array_diff($allPermissionKeys, self::MANAGER_EXCLUDED)),
                'member' => self::MEMBER_PERMISSIONS,
                'viewer' => self::VIEWER_PERMISSIONS,
                default => throw new OrganizationProvisioningException("Unknown system role key [{$key}]."),
            };

            $rows = [];

            foreach ($permissionKeys as $permissionKey) {
                $permission = $permissions->get($permissionKey);

                if ($permission === null) {
                    throw new OrganizationProvisioningException("Permission [{$permissionKey}] is missing from the catalog.");
                }

                $rows[] = [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'created_by_user_id' => $actorUserId,
                ];
            }

            DB::table('role_permissions')->insert($rows);
        }
    }
}
