<?php

namespace App\Services\Module;

use App\Enums\ApplicationStatus;
use App\Enums\SettingDefinitionScope;
use App\Enums\SettingDefinitionStatus;
use App\Enums\WorkspaceSettingType;
use App\Models\Application;
use App\Models\ApplicationSettingDefinition;
use App\Models\Permission;
use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Contracts\ModuleRegistryReader;
use App\Modules\Sdk\Contracts\ModuleSyncPort;
use App\Modules\Sdk\Data\ModuleDependency;
use App\Modules\Sdk\Data\ModulePermission;
use App\Modules\Sdk\Data\ModuleSettingDefinition;
use App\Modules\Sdk\Data\ModuleSyncChange;
use App\Modules\Sdk\Data\ModuleSyncError;
use App\Modules\Sdk\Data\ModuleSyncOptions;
use App\Modules\Sdk\Data\ModuleSyncResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModuleSyncService implements ModuleSyncPort
{
    private const ROLE_ASSIGNMENT_DEFERRED_NOTE = 'Module permission role assignment is deferred to a future slice.';

    public function sync(ModuleRegistryReader $registry, ModuleSyncOptions $options): ModuleSyncResult
    {
        $validation = $registry->validate();

        if (! $validation->isValid()) {
            $errors = array_map(
                fn ($issue) => new ModuleSyncError(
                    code: $issue->code,
                    message: $issue->message,
                    moduleKey: $issue->moduleKey,
                ),
                $validation->issues,
            );

            return ModuleSyncResult::failed($errors);
        }

        $modules = $this->resolveModules($registry, $options);

        if ($modules === null) {
            return ModuleSyncResult::failed([
                new ModuleSyncError(
                    code: 'unknown_module',
                    message: sprintf('Module "%s" is not registered.', $options->moduleKey),
                    moduleKey: $options->moduleKey,
                ),
            ]);
        }

        $changes = [];
        $errors = [];
        $notes = [self::ROLE_ASSIGNMENT_DEFERRED_NOTE];
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $skipped = 0;

        $sync = function () use (
            $modules,
            $options,
            &$changes,
            &$created,
            &$updated,
            &$unchanged,
            &$skipped,
            &$notes,
        ): void {
            foreach ($modules as $module) {
                $this->syncModule(
                    module: $module,
                    dryRun: $options->dryRun,
                    changes: $changes,
                    created: $created,
                    updated: $updated,
                    unchanged: $unchanged,
                    skipped: $skipped,
                    notes: $notes,
                );
            }
        };

        if ($options->dryRun) {
            $sync();
        } else {
            DB::transaction($sync);
        }

        return new ModuleSyncResult(
            modulesScanned: count($modules),
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
            skipped: $skipped,
            changes: $changes,
            errors: $errors,
            notes: $notes,
            success: $errors === [],
        );
    }

    /**
     * @return list<ApplicationModule>|null
     */
    private function resolveModules(ModuleRegistryReader $registry, ModuleSyncOptions $options): ?array
    {
        if ($options->moduleKey === null) {
            return $this->topologicalSort($registry->all());
        }

        $module = $registry->findByKey($options->moduleKey);

        if ($module === null) {
            return null;
        }

        return [$module];
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @return list<ApplicationModule>
     */
    private function topologicalSort(array $modules): array
    {
        $byKey = [];

        foreach ($modules as $module) {
            $byKey[$module->key()] = $module;
        }

        $sorted = [];
        $visited = [];

        $visit = function (ApplicationModule $module) use (&$visit, &$sorted, &$visited, $byKey): void {
            $key = $module->key();

            if (isset($visited[$key])) {
                return;
            }

            $visited[$key] = true;

            foreach ($module->manifest()->dependencies as $dependency) {
                if (isset($byKey[$dependency->key])) {
                    $visit($byKey[$dependency->key]);
                }
            }

            $sorted[] = $module;
        };

        foreach ($modules as $module) {
            $visit($module);
        }

        return $sorted;
    }

    /**
     * @param  list<ModuleSyncChange>  $changes
     * @param  list<string>  $notes
     */
    private function syncModule(
        ApplicationModule $module,
        bool $dryRun,
        array &$changes,
        int &$created,
        int &$updated,
        int &$unchanged,
        int &$skipped,
        array &$notes,
    ): void {
        $application = $this->syncApplication($module, $dryRun, $changes, $created, $updated, $unchanged);

        if ($application === null && ! $dryRun) {
            return;
        }

        $applicationId = $dryRun
            ? Application::query()->where('key', $module->key())->value('id')
            : $application?->id;

        if ($applicationId === null && $dryRun) {
            $applicationId = 'dry-run';
        }

        if ($applicationId !== null) {
            $this->syncSettingDefinitions(
                module: $module,
                applicationId: (string) $applicationId,
                dryRun: $dryRun,
                changes: $changes,
                created: $created,
                updated: $updated,
                unchanged: $unchanged,
                notes: $notes,
            );
        }

        $this->syncPermissions(
            module: $module,
            dryRun: $dryRun,
            changes: $changes,
            created: $created,
            updated: $updated,
            unchanged: $unchanged,
        );
    }

    /**
     * @param  list<ModuleSyncChange>  $changes
     */
    private function syncApplication(
        ApplicationModule $module,
        bool $dryRun,
        array &$changes,
        int &$created,
        int &$updated,
        int &$unchanged,
    ): ?Application {
        $manifest = $module->manifest();
        $payload = $this->applicationPayload($module);
        $existing = Application::query()->where('key', $manifest->key)->first();

        if ($existing === null) {
            $changes[] = new ModuleSyncChange('application', 'created', $manifest->key, $manifest->key);
            $created++;

            if ($dryRun) {
                return null;
            }

            return Application::query()->create([
                'id' => (string) Str::uuid7(),
                'public_id' => (string) Str::uuid7(),
                'key' => $manifest->key,
                ...$payload,
            ]);
        }

        if ($this->applicationNeedsUpdate($existing, $payload)) {
            $changes[] = new ModuleSyncChange('application', 'updated', $manifest->key, $manifest->key);
            $updated++;

            if (! $dryRun) {
                $existing->update($payload);
            }
        } else {
            $changes[] = new ModuleSyncChange('application', 'unchanged', $manifest->key, $manifest->key);
            $unchanged++;
        }

        return $existing;
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationPayload(ApplicationModule $module): array
    {
        $manifest = $module->manifest();

        return [
            'name' => $manifest->name,
            'description' => $manifest->description,
            'version' => $manifest->version,
            'status' => ApplicationStatus::Active,
            'is_core' => $manifest->isCore,
            'icon' => $manifest->icon,
            'category' => $manifest->category,
            'capabilities' => $manifest->capabilities === [] ? null : $manifest->capabilities,
            'dependencies' => $this->normalizeDependencies($manifest->dependencies),
            'module_uuid' => $manifest->moduleUuid,
            'manifest_version' => $manifest->manifestVersion,
        ];
    }

    /**
     * @param  list<ModuleDependency>  $dependencies
     * @return list<string>|null
     */
    private function normalizeDependencies(array $dependencies): ?array
    {
        if ($dependencies === []) {
            return null;
        }

        return array_values(array_map(
            fn (ModuleDependency $dependency) => $dependency->key,
            $dependencies,
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applicationNeedsUpdate(Application $application, array $payload): bool
    {
        foreach ($payload as $field => $value) {
            $current = $application->getAttribute($field);

            if ($field === 'capabilities' || $field === 'dependencies') {
                $current = is_array($current) ? array_values($current) : $current;
                $value = is_array($value) ? array_values($value) : $value;
            }

            if ($current != $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ModuleSyncChange>  $changes
     * @param  list<string>  $notes
     */
    private function syncSettingDefinitions(
        ApplicationModule $module,
        string $applicationId,
        bool $dryRun,
        array &$changes,
        int &$created,
        int &$updated,
        int &$unchanged,
        array &$notes,
    ): void {
        if ($dryRun && $applicationId === 'dry-run') {
            $existingApplication = Application::query()->where('key', $module->key())->first();
            $applicationId = $existingApplication?->id ?? 'dry-run';
        }

        if ($applicationId === 'dry-run') {
            foreach ($module->settingDefinitions() as $definition) {
                $changes[] = new ModuleSyncChange(
                    'setting_definition',
                    'created',
                    $definition->settingKey,
                    $module->key(),
                );
                $created++;
            }

            return;
        }

        $manifestKeys = [];

        foreach ($module->settingDefinitions() as $definition) {
            $manifestKeys[$definition->settingKey] = true;
            $scope = $this->resolveScope($definition);
            $payload = $this->settingDefinitionPayload($definition, $applicationId);
            $existing = ApplicationSettingDefinition::query()
                ->where('application_id', $applicationId)
                ->where('setting_key', $definition->settingKey)
                ->where('scope', $scope)
                ->whereNull('deleted_at')
                ->first();

            if ($existing === null) {
                $changes[] = new ModuleSyncChange(
                    'setting_definition',
                    'created',
                    $definition->settingKey,
                    $module->key(),
                );
                $created++;

                if (! $dryRun) {
                    ApplicationSettingDefinition::query()->create([
                        'id' => (string) Str::uuid7(),
                        'public_id' => (string) Str::uuid7(),
                        ...$payload,
                    ]);
                }

                continue;
            }

            if ($this->settingDefinitionNeedsUpdate($existing, $payload)) {
                $changes[] = new ModuleSyncChange(
                    'setting_definition',
                    'updated',
                    $definition->settingKey,
                    $module->key(),
                );
                $updated++;

                if (! $dryRun) {
                    $existing->update($payload);
                }
            } else {
                $changes[] = new ModuleSyncChange(
                    'setting_definition',
                    'unchanged',
                    $definition->settingKey,
                    $module->key(),
                );
                $unchanged++;
            }
        }

        $existingDefinitions = ApplicationSettingDefinition::query()
            ->where('application_id', $applicationId)
            ->where('scope', SettingDefinitionScope::Workspace)
            ->whereNull('deleted_at')
            ->get();

        foreach ($existingDefinitions as $existingDefinition) {
            if (isset($manifestKeys[$existingDefinition->setting_key])) {
                continue;
            }

            $notes[] = sprintf(
                'Drift detected: setting definition "%s" exists for module "%s" but is not declared in code.',
                $existingDefinition->setting_key,
                $module->key(),
            );
            $changes[] = new ModuleSyncChange(
                'setting_definition',
                'drift',
                $existingDefinition->setting_key,
                $module->key(),
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function settingDefinitionPayload(ModuleSettingDefinition $definition, string $applicationId): array
    {
        return [
            'application_id' => $applicationId,
            'setting_key' => $definition->settingKey,
            'label' => $definition->label,
            'description' => $definition->description,
            'setting_type' => WorkspaceSettingType::from($definition->settingType),
            'default_value' => $definition->defaultValue,
            'is_required' => $definition->isRequired,
            'is_sensitive' => $definition->isSensitive,
            'is_encrypted' => $definition->isEncrypted,
            'scope' => $this->resolveScope($definition),
            'category' => $definition->category,
            'sort_order' => $definition->sortOrder,
            'validation_rules' => $definition->validationRules,
            'status' => SettingDefinitionStatus::Active,
        ];
    }

    private function resolveScope(ModuleSettingDefinition $definition): SettingDefinitionScope
    {
        return SettingDefinitionScope::from($definition->scope);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function settingDefinitionNeedsUpdate(ApplicationSettingDefinition $existing, array $payload): bool
    {
        foreach ($payload as $field => $value) {
            if ($field === 'application_id') {
                continue;
            }

            $current = $existing->getAttribute($field);

            if ($current != $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<ModuleSyncChange>  $changes
     */
    private function syncPermissions(
        ApplicationModule $module,
        bool $dryRun,
        array &$changes,
        int &$created,
        int &$updated,
        int &$unchanged,
    ): void {
        foreach ($module->permissions() as $permission) {
            $this->syncPermission($module, $permission, $dryRun, $changes, $created, $updated, $unchanged);
        }
    }

    /**
     * @param  list<ModuleSyncChange>  $changes
     */
    private function syncPermission(
        ApplicationModule $module,
        ModulePermission $permission,
        bool $dryRun,
        array &$changes,
        int &$created,
        int &$updated,
        int &$unchanged,
    ): void {
        $existing = Permission::query()->where('key', $permission->key)->first();
        $payload = [
            'name' => $permission->label,
            'description' => $permission->description,
            'domain' => $module->key(),
        ];

        if ($existing === null) {
            $changes[] = new ModuleSyncChange('permission', 'created', $permission->key, $module->key());
            $created++;

            if (! $dryRun) {
                Permission::query()->create([
                    'id' => (string) Str::uuid7(),
                    'public_id' => (string) Str::uuid7(),
                    'key' => $permission->key,
                    ...$payload,
                ]);
            }

            return;
        }

        if ($this->permissionNeedsUpdate($existing, $payload)) {
            $changes[] = new ModuleSyncChange('permission', 'updated', $permission->key, $module->key());
            $updated++;

            if (! $dryRun) {
                $existing->update($payload);
            }
        } else {
            $changes[] = new ModuleSyncChange('permission', 'unchanged', $permission->key, $module->key());
            $unchanged++;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function permissionNeedsUpdate(Permission $permission, array $payload): bool
    {
        foreach ($payload as $field => $value) {
            if ($permission->getAttribute($field) != $value) {
                return true;
            }
        }

        return false;
    }
}
