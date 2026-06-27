<?php

namespace App\Services\Enterprise\Workflow\Marketplace;

use App\Models\WorkflowPackage;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackage as WorkflowPackageDto;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageReference;
use App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowPackageVersion as WorkflowPackageVersionDto;

class WorkflowPackageMapper
{
    public static function toReference(WorkflowPackage $model): WorkflowPackageReference
    {
        $latest = $model->versions->sortByDesc('published_at')->first()
            ?? $model->versions->sortByDesc('created_at')->first();

        return new WorkflowPackageReference(
            publicId: $model->public_id,
            packageKey: $model->package_key,
            name: $model->name,
            status: $model->status->value,
            visibility: $model->visibility->value,
            type: $model->type->value,
            moduleKey: $model->module_key,
            latestVersion: $latest?->version,
        );
    }

    public static function toDto(WorkflowPackage $model): WorkflowPackageDto
    {
        $versions = $model->versions
            ->sortByDesc('published_at')
            ->values()
            ->map(fn ($version) => new WorkflowPackageVersionDto(
                publicId: $version->public_id,
                packagePublicId: $model->public_id,
                version: $version->version,
                status: $version->status->value,
                manifest: $version->manifest_json,
                checksum: $version->checksum,
                publishedAt: $version->published_at?->toIso8601String(),
            ))
            ->all();

        $latest = $versions[0] ?? null;

        return new WorkflowPackageDto(
            publicId: $model->public_id,
            packageKey: $model->package_key,
            name: $model->name,
            status: $model->status->value,
            visibility: $model->visibility->value,
            type: $model->type->value,
            description: $model->description,
            author: $model->author,
            license: $model->license,
            moduleKey: $model->module_key,
            tags: $model->tags ?? [],
            metadata: $model->metadata ?? [],
            dependencies: $model->dependencies->map(fn ($dep) => new \App\Modules\Sdk\Workflow\Marketplace\Data\WorkflowDependency(
                key: $dep->dependency_key,
                type: $dep->dependency_type,
                versionConstraint: $dep->version_constraint,
                required: (bool) $dep->required,
                metadata: $dep->metadata ?? [],
            ))->all(),
            versions: $versions,
            latestVersion: $latest,
        );
    }
}
