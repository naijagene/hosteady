<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowPackageValidator;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;
use App\Modules\Sdk\Workflow\Marketplace\Exceptions\WorkflowPackageException;

class WorkflowPackageValidatorService implements WorkflowPackageValidator
{
    /**
     * @return list<string>
     */
    public function validate(WorkflowPackageManifest $manifest): array
    {
        $errors = [];

        if ($manifest->key === '' || ! preg_match('/^[a-z0-9][a-z0-9._-]{1,126}[a-z0-9]$/', $manifest->key)) {
            $errors[] = 'Package key must be lowercase alphanumeric with dots, dashes, or underscores.';
        }

        if ($manifest->name === '') {
            $errors[] = 'Package name is required.';
        }

        if (! preg_match('/^\d+\.\d+\.\d+([-.+][\w.-]+)?$/', $manifest->version)) {
            $errors[] = 'Package version must be a semantic version.';
        }

        if ($manifest->workflow === [] && $manifest->canvas === []) {
            $errors[] = 'Workflow payload is required in package manifest.';
        }

        if ($manifest->engine !== 'heos') {
            $errors[] = sprintf('Unsupported package engine [%s].', $manifest->engine);
        }

        foreach ($manifest->requires as $dependency) {
            if ($dependency->key === '' || $dependency->type === '') {
                $errors[] = 'Each dependency must include key and type.';
            }
        }

        return $errors;
    }

    public function assertValid(WorkflowPackageManifest $manifest): void
    {
        $errors = $this->validate($manifest);

        if ($errors !== []) {
            throw new WorkflowPackageException(implode(' ', $errors));
        }
    }
}
