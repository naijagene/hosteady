<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Modules\Sdk\Workflow\Marketplace\Contracts\WorkflowPackageProvider;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageManifest;

class WorkflowPackageProviderService implements WorkflowPackageProvider
{
    public function normalizeManifest(WorkflowPackageManifest $manifest): WorkflowPackageManifest
    {
        $workflow = $manifest->workflow;

        if ($workflow !== [] && ! isset($workflow['definition']) && isset($workflow['nodes'])) {
            $workflow = [
                'workflow_key' => $manifest->key,
                'name' => $manifest->name,
                'description' => $manifest->description,
                'module_key' => $manifest->moduleKey,
                'metadata' => $manifest->metadata,
                'definition' => [
                    'nodes' => $workflow['nodes'] ?? [],
                    'transitions' => $workflow['transitions'] ?? [],
                    'triggers' => $workflow['triggers'] ?? [],
                    'variables' => $manifest->variables,
                ],
                'variables' => $manifest->variables,
            ];
        }

        return new WorkflowPackageManifest(
            key: $manifest->key,
            name: $manifest->name,
            version: $manifest->version,
            moduleKey: $manifest->moduleKey,
            engine: $manifest->engine,
            engineVersion: $manifest->engineVersion,
            author: $manifest->author,
            license: $manifest->license,
            description: $manifest->description,
            tags: array_values(array_unique($manifest->tags)),
            requires: $manifest->requires,
            workflow: $workflow,
            canvas: $manifest->canvas,
            variables: $manifest->variables,
            metadata: $manifest->metadata,
        );
    }
}
