<?php

namespace App\Modules\Sdk;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Data\ModuleManifest;
use App\Modules\Sdk\Data\ModuleValidationIssue;
use App\Modules\Sdk\Data\ModuleValidationReport;

class ModuleManifestValidator
{
    private const KEY_PATTERN = '/^[a-z][a-z0-9-]*$/';

    private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private const SEMVER_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * @var list<string>
     */
    private const ALLOWED_SETTING_TYPES = [
        'string',
        'boolean',
        'integer',
        'float',
        'array',
        'json',
    ];

    public function validateModule(ApplicationModule $module, ?array $registeredKeys = null): ModuleValidationReport
    {
        $issues = [];

        $issues = array_merge($issues, $this->validateManifest($module->manifest(), $registeredKeys));
        $issues = array_merge($issues, $this->validateModuleConsistency($module));

        return new ModuleValidationReport($issues);
    }

    /**
     * @param  list<string>|null  $registeredKeys
     * @return list<ModuleValidationIssue>
     */
    public function validateManifest(ModuleManifest $manifest, ?array $registeredKeys = null): array
    {
        $issues = [];

        if ($manifest->manifestVersion < 1) {
            $issues[] = new ModuleValidationIssue(
                'invalid_manifest_version',
                'Manifest version must be at least 1.',
                $manifest->key,
            );
        }

        if (! preg_match(self::UUID_PATTERN, $manifest->moduleUuid)) {
            $issues[] = new ModuleValidationIssue(
                'invalid_module_uuid',
                'Module UUID must be a valid UUID.',
                $manifest->key,
            );
        }

        if (! preg_match(self::KEY_PATTERN, $manifest->key)) {
            $issues[] = new ModuleValidationIssue(
                'invalid_module_key',
                'Module key must match [a-z][a-z0-9-]*.',
                $manifest->key,
            );
        }

        if (! preg_match(self::SEMVER_PATTERN, $manifest->version)) {
            $issues[] = new ModuleValidationIssue(
                'invalid_version',
                'Module version must be valid semver.',
                $manifest->key,
            );
        }

        $settingKeys = [];

        foreach ($manifest->settings as $setting) {
            if (isset($settingKeys[$setting->settingKey])) {
                $issues[] = new ModuleValidationIssue(
                    'duplicate_setting_key',
                    sprintf('Duplicate setting key "%s".', $setting->settingKey),
                    $manifest->key,
                );
            }

            $settingKeys[$setting->settingKey] = true;

            if (! in_array($setting->settingType, self::ALLOWED_SETTING_TYPES, true)) {
                $issues[] = new ModuleValidationIssue(
                    'invalid_setting_type',
                    sprintf('Setting "%s" has invalid type "%s".', $setting->settingKey, $setting->settingType),
                    $manifest->key,
                );
            }
        }

        foreach ($manifest->permissions as $permission) {
            $expectedPrefix = $manifest->key.'.';

            if (! str_starts_with($permission->key, $expectedPrefix)) {
                $issues[] = new ModuleValidationIssue(
                    'invalid_permission_key',
                    sprintf('Permission "%s" must be prefixed with "%s".', $permission->key, $expectedPrefix),
                    $manifest->key,
                );
            }
        }

        if ($registeredKeys !== null) {
            foreach ($manifest->dependencies as $dependency) {
                if (! in_array($dependency->key, $registeredKeys, true)) {
                    $issues[] = new ModuleValidationIssue(
                        'unknown_dependency',
                        sprintf('Dependency "%s" is not registered.', $dependency->key),
                        $manifest->key,
                    );
                }
            }
        }

        return $issues;
    }

    /**
     * @param  list<ApplicationModule>  $modules
     */
    public function validateRegistry(array $modules): ModuleValidationReport
    {
        $issues = [];
        $registeredKeys = array_map(fn (ApplicationModule $module) => $module->key(), $modules);
        $uuids = [];

        foreach ($modules as $module) {
            $manifest = $module->manifest();

            if (isset($uuids[$manifest->moduleUuid])) {
                $issues[] = new ModuleValidationIssue(
                    'duplicate_module_uuid',
                    sprintf('Module UUID "%s" is already registered.', $manifest->moduleUuid),
                    $module->key(),
                );
            }

            $uuids[$manifest->moduleUuid] = $module->key();

            $report = $this->validateModule($module, $registeredKeys);

            foreach ($report->issues as $issue) {
                $issues[] = $issue;
            }
        }

        $issues = array_merge($issues, $this->detectCircularDependencies($modules));

        return new ModuleValidationReport($issues);
    }

    /**
     * @return list<ModuleValidationIssue>
     */
    private function validateModuleConsistency(ApplicationModule $module): array
    {
        $issues = [];

        if ($module->key() !== $module->manifest()->key) {
            $issues[] = new ModuleValidationIssue(
                'key_mismatch',
                'Module key() must match manifest key.',
                $module->key(),
            );
        }

        if ($module->name() !== $module->manifest()->name) {
            $issues[] = new ModuleValidationIssue(
                'name_mismatch',
                'Module name() must match manifest name.',
                $module->key(),
            );
        }

        if ($module->version() !== $module->manifest()->version) {
            $issues[] = new ModuleValidationIssue(
                'version_mismatch',
                'Module version() must match manifest version.',
                $module->key(),
            );
        }

        return $issues;
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @return list<ModuleValidationIssue>
     */
    private function detectCircularDependencies(array $modules): array
    {
        $issues = [];
        $graph = [];

        foreach ($modules as $module) {
            $graph[$module->key()] = array_map(
                fn ($dependency) => $dependency->key,
                $module->manifest()->dependencies,
            );
        }

        foreach ($graph as $moduleKey => $dependencies) {
            if ($this->hasCycle($moduleKey, $graph, [])) {
                $issues[] = new ModuleValidationIssue(
                    'circular_dependency',
                    sprintf('Module "%s" participates in a circular dependency.', $moduleKey),
                    $moduleKey,
                );
            }
        }

        return $issues;
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @param  list<string>  $visited
     */
    private function hasCycle(string $node, array $graph, array $visited): bool
    {
        if (in_array($node, $visited, true)) {
            return true;
        }

        $visited[] = $node;

        foreach ($graph[$node] ?? [] as $dependency) {
            if ($this->hasCycle($dependency, $graph, $visited)) {
                return true;
            }
        }

        return false;
    }
}
