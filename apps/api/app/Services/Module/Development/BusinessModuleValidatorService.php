<?php

namespace App\Services\Module\Development;

use App\Modules\Sdk\Development\Contracts\BusinessModuleValidator;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleValidationIssue;
use App\Modules\Sdk\Development\Data\BusinessModuleValidationReport;
use App\Modules\Sdk\Development\Enums\BusinessModuleValidationSeverity;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleValidationException;

class BusinessModuleValidatorService implements BusinessModuleValidator
{
    public function validate(BusinessModuleManifest $manifest): BusinessModuleValidationReport
    {
        $issues = [];

        if ($manifest->moduleKey === '' || ! preg_match('/^[a-z][a-z0-9._-]{1,126}[a-z0-9]$/', $manifest->moduleKey)) {
            $issues[] = new BusinessModuleValidationIssue(
                code: 'invalid_module_key',
                message: 'Module key must be lowercase alphanumeric with dots, dashes, or underscores.',
                severity: BusinessModuleValidationSeverity::Error->value,
                field: 'module_key',
            );
        }

        if ($manifest->name === '') {
            $issues[] = new BusinessModuleValidationIssue(
                code: 'missing_name',
                message: 'Module name is required.',
                severity: BusinessModuleValidationSeverity::Error->value,
                field: 'name',
            );
        }

        if (! preg_match('/^\d+\.\d+\.\d+([-.+][\w.-]+)?$/', $manifest->version)) {
            $issues[] = new BusinessModuleValidationIssue(
                code: 'invalid_version',
                message: 'Module version must be semantic.',
                severity: BusinessModuleValidationSeverity::Error->value,
                field: 'version',
            );
        }

        $permissionKeys = [];
        foreach ($manifest->permissions as $permission) {
            if ($permission->key === '') {
                $issues[] = new BusinessModuleValidationIssue(
                    code: 'invalid_permission',
                    message: 'Permission key is required.',
                    severity: BusinessModuleValidationSeverity::Error->value,
                    field: 'permissions',
                );
                continue;
            }

            if (isset($permissionKeys[$permission->key])) {
                $issues[] = new BusinessModuleValidationIssue(
                    code: 'duplicate_permission_key',
                    message: sprintf('Duplicate permission key [%s].', $permission->key),
                    severity: BusinessModuleValidationSeverity::Error->value,
                    field: 'permissions',
                );
            }

            $permissionKeys[$permission->key] = true;
        }

        $routeNames = [];
        foreach ($manifest->routes as $route) {
            if ($route->name === '' || $route->uri === '') {
                $issues[] = new BusinessModuleValidationIssue(
                    code: 'invalid_route',
                    message: 'Route name and URI are required.',
                    severity: BusinessModuleValidationSeverity::Error->value,
                    field: 'routes',
                );
                continue;
            }

            if (! preg_match('/^[a-z][a-z0-9._-]*$/', $route->name)) {
                $issues[] = new BusinessModuleValidationIssue(
                    code: 'invalid_route_name',
                    message: sprintf('Invalid route name [%s].', $route->name),
                    severity: BusinessModuleValidationSeverity::Error->value,
                    field: 'routes',
                );
            }

            if (isset($routeNames[$route->name])) {
                $issues[] = new BusinessModuleValidationIssue(
                    code: 'duplicate_route_name',
                    message: sprintf('Duplicate route name [%s].', $route->name),
                    severity: BusinessModuleValidationSeverity::Error->value,
                    field: 'routes',
                );
            }

            $routeNames[$route->name] = true;
        }

        foreach ($manifest->dependencies as $dependency) {
            if ($dependency === '') {
                $issues[] = new BusinessModuleValidationIssue(
                    code: 'invalid_dependency',
                    message: 'Dependency entries must be non-empty strings.',
                    severity: BusinessModuleValidationSeverity::Error->value,
                    field: 'dependencies',
                );
            }
        }

        $errors = array_filter($issues, fn (BusinessModuleValidationIssue $issue) => $issue->severity === BusinessModuleValidationSeverity::Error->value);

        return new BusinessModuleValidationReport(
            moduleKey: $manifest->moduleKey,
            valid: $errors === [],
            issues: $issues,
        );
    }

    public function assertValid(BusinessModuleManifest $manifest): BusinessModuleValidationReport
    {
        $report = $this->validate($manifest);

        if (! $report->valid) {
            $messages = array_map(fn (BusinessModuleValidationIssue $issue) => $issue->message, $report->issues);

            throw new BusinessModuleValidationException(implode(' ', $messages));
        }

        return $report;
    }
}
