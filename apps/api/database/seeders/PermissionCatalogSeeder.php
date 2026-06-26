<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionCatalogSeeder extends Seeder
{
    /**
     * @var list<array{key: string, name: string, description: string|null, domain: string}>
     */
    private const PERMISSIONS = [
        ['key' => 'organization.read', 'name' => 'Read Organization', 'description' => null, 'domain' => 'organization'],
        ['key' => 'organization.update', 'name' => 'Update Organization', 'description' => null, 'domain' => 'organization'],
        ['key' => 'organization.archive', 'name' => 'Archive Organization', 'description' => null, 'domain' => 'organization'],
        ['key' => 'members.read', 'name' => 'Read Members', 'description' => null, 'domain' => 'organization'],
        ['key' => 'members.invite', 'name' => 'Invite Members', 'description' => null, 'domain' => 'organization'],
        ['key' => 'members.update', 'name' => 'Update Members', 'description' => null, 'domain' => 'organization'],
        ['key' => 'members.remove', 'name' => 'Remove Members', 'description' => null, 'domain' => 'organization'],
        ['key' => 'roles.read', 'name' => 'Read Roles', 'description' => null, 'domain' => 'organization'],
        ['key' => 'roles.manage', 'name' => 'Manage Roles', 'description' => null, 'domain' => 'organization'],
        ['key' => 'workspace.read', 'name' => 'Read Workspaces', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.create', 'name' => 'Create Workspaces', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.update', 'name' => 'Update Workspaces', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.archive', 'name' => 'Archive Workspaces', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.applications.read', 'name' => 'Read Workspace Applications', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.applications.enable', 'name' => 'Enable Workspace Applications', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.applications.configure', 'name' => 'Configure Workspace Applications', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'workspace.applications.manage', 'name' => 'Manage Workspace Applications', 'description' => null, 'domain' => 'workspace'],
        ['key' => 'applications.read', 'name' => 'Read Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'applications.install', 'name' => 'Install Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'applications.configure', 'name' => 'Configure Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'applications.uninstall', 'name' => 'Uninstall Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'audit.read', 'name' => 'Read Audit Events', 'description' => null, 'domain' => 'audit'],
        ['key' => 'notifications.read', 'name' => 'Read Notifications', 'description' => null, 'domain' => 'notifications'],
        ['key' => 'notifications.manage', 'name' => 'Manage Notifications', 'description' => null, 'domain' => 'notifications'],
        ['key' => 'reference.read', 'name' => 'Read Reference Data', 'description' => null, 'domain' => 'reference'],
        ['key' => 'files.read', 'name' => 'Read Files', 'description' => null, 'domain' => 'files'],
        ['key' => 'files.upload', 'name' => 'Upload Files', 'description' => null, 'domain' => 'files'],
        ['key' => 'files.manage', 'name' => 'Manage Files', 'description' => null, 'domain' => 'files'],
        ['key' => 'jobs.read', 'name' => 'Read Jobs', 'description' => null, 'domain' => 'jobs'],
        ['key' => 'jobs.dispatch', 'name' => 'Dispatch Jobs', 'description' => null, 'domain' => 'jobs'],
        ['key' => 'jobs.manage', 'name' => 'Manage Jobs', 'description' => null, 'domain' => 'jobs'],
        ['key' => 'scheduler.read', 'name' => 'Read Scheduled Tasks', 'description' => null, 'domain' => 'scheduler'],
        ['key' => 'scheduler.manage', 'name' => 'Manage Scheduled Tasks', 'description' => null, 'domain' => 'scheduler'],
        ['key' => 'search.read', 'name' => 'Read Search', 'description' => null, 'domain' => 'search'],
        ['key' => 'search.manage', 'name' => 'Manage Search', 'description' => null, 'domain' => 'search'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            $existingPermission = Permission::query()
                ->where('key', $permission['key'])
                ->first();

            if ($existingPermission) {
                $existingPermission->update([
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                    'domain' => $permission['domain'],
                ]);

                continue;
            }

            Permission::query()->create([
                'id' => (string) Str::uuid7(),
                'public_id' => (string) Str::uuid7(),
                'key' => $permission['key'],
                'name' => $permission['name'],
                'description' => $permission['description'],
                'domain' => $permission['domain'],
            ]);
        }
    }
}