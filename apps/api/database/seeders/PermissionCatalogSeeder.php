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
        ['key' => 'applications.read', 'name' => 'Read Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'applications.install', 'name' => 'Install Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'applications.configure', 'name' => 'Configure Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'applications.uninstall', 'name' => 'Uninstall Applications', 'description' => null, 'domain' => 'application'],
        ['key' => 'audit.read', 'name' => 'Read Audit Events', 'description' => null, 'domain' => 'audit'],
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