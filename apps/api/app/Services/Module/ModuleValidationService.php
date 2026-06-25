<?php

namespace App\Services\Module;

use App\Modules\Sdk\Contracts\ApplicationModule;
use App\Modules\Sdk\Data\ModuleValidationIssue;
use App\Modules\Sdk\Data\ModuleValidationReport;
use App\Modules\Sdk\ModuleManifestValidator;
use App\Modules\Sdk\ModuleRegistry;

class ModuleValidationService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleManifestValidator $validator,
        private readonly ModuleDeveloperAuditRecorder $auditRecorder,
    ) {
    }

    public function validate(): ModuleValidationReport
    {
        $issues = $this->registry->validate()->issues;
        $modules = $this->registry->all();

        $issues = array_merge($issues, $this->detectDuplicateKeys($modules));

        foreach ($modules as $module) {
            $issues = array_merge($issues, $this->validateModuleExtensions($module));
        }

        $report = new ModuleValidationReport($this->deduplicateIssues($issues));
        $this->auditRecorder->recordValidationExecuted($report);

        return $report;
    }

    public function validateModule(ApplicationModule $module): ModuleValidationReport
    {
        $registeredKeys = array_map(
            fn (ApplicationModule $registered) => $registered->key(),
            $this->registry->all(),
        );

        $issues = $this->validator->validateModule($module, $registeredKeys)->issues;
        $issues = array_merge($issues, $this->validateModuleExtensions($module));

        return new ModuleValidationReport($this->deduplicateIssues($issues));
    }

    /**
     * @param  list<ApplicationModule>  $modules
     * @return list<ModuleValidationIssue>
     */
    private function detectDuplicateKeys(array $modules): array
    {
        $issues = [];
        $seen = [];

        foreach ($modules as $module) {
            $key = $module->key();

            if (isset($seen[$key])) {
                $issues[] = new ModuleValidationIssue(
                    'duplicate_module_key',
                    sprintf('Duplicate module key "%s".', $key),
                    $key,
                );
            }

            $seen[$key] = true;
        }

        return $issues;
    }

    /**
     * @return list<ModuleValidationIssue>
     */
    private function validateModuleExtensions(ApplicationModule $module): array
    {
        $issues = [];
        $manifest = $module->manifest();

        $permissionKeys = [];

        foreach ($manifest->permissions as $permission) {
            if (isset($permissionKeys[$permission->key])) {
                $issues[] = new ModuleValidationIssue(
                    'duplicate_permission_key',
                    sprintf('Duplicate permission key "%s".', $permission->key),
                    $module->key(),
                );
            }

            $permissionKeys[$permission->key] = true;
        }

        $navigationIds = [];

        foreach ($manifest->navigation as $navigationItem) {
            if (isset($navigationIds[$navigationItem->publicId])) {
                $issues[] = new ModuleValidationIssue(
                    'duplicate_navigation_id',
                    sprintf('Duplicate navigation id "%s".', $navigationItem->publicId),
                    $module->key(),
                );
            }

            $navigationIds[$navigationItem->publicId] = true;
        }

        $routeNames = [];

        foreach ($manifest->routes->routes as $route) {
            if (isset($routeNames[$route->name])) {
                $issues[] = new ModuleValidationIssue(
                    'duplicate_route_name',
                    sprintf('Duplicate route name "%s".', $route->name),
                    $module->key(),
                );
            }

            $routeNames[$route->name] = true;
        }

        if ($manifest->manifestVersion !== \App\Modules\Sdk\Data\ModuleManifest::CURRENT_MANIFEST_VERSION) {
            $issues[] = new ModuleValidationIssue(
                'manifest_version_mismatch',
                sprintf(
                    'Manifest version %d does not match current platform version %d.',
                    $manifest->manifestVersion,
                    \App\Modules\Sdk\Data\ModuleManifest::CURRENT_MANIFEST_VERSION,
                ),
                $module->key(),
            );
        }

        return $issues;
    }

    /**
     * @param  list<ModuleValidationIssue>  $issues
     * @return list<ModuleValidationIssue>
     */
    private function deduplicateIssues(array $issues): array
    {
        $unique = [];

        foreach ($issues as $issue) {
            $key = implode('|', [$issue->code, $issue->message, $issue->moduleKey ?? '']);

            if (isset($unique[$key])) {
                continue;
            }

            $unique[$key] = $issue;
        }

        return array_values($unique);
    }
}
