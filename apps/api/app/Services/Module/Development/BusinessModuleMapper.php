<?php

namespace App\Services\Module\Development;

use App\Models\BusinessModule;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleReference;
use App\Modules\Sdk\Development\Enums\BusinessModuleStatus;
use App\Modules\Sdk\Development\Enums\BusinessModuleType;

class BusinessModuleMapper
{
    public static function toReference(BusinessModule $model): BusinessModuleReference
    {
        return new BusinessModuleReference(
            publicId: $model->public_id,
            moduleKey: $model->module_key,
            name: $model->name,
            status: $model->status->value,
            type: $model->type->value,
            version: $model->version,
        );
    }

    public static function toManifest(BusinessModule $model): BusinessModuleManifest
    {
        return BusinessModuleManifest::fromArray($model->manifest_json ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public static function manifestArray(BusinessModuleManifest $manifest): array
    {
        return $manifest->toArray();
    }

    public static function applyManifest(BusinessModule $model, BusinessModuleManifest $manifest): void
    {
        $model->fill([
            'module_key' => $manifest->moduleKey,
            'name' => $manifest->name,
            'description' => $manifest->description,
            'type' => BusinessModuleType::tryFrom($manifest->type) ?? BusinessModuleType::Business,
            'version' => $manifest->version,
            'manifest_json' => $manifest->toArray(),
            'capabilities' => array_map(fn ($c) => $c->toArray(), $manifest->capabilities),
            'permissions' => array_map(fn ($p) => $p->toArray(), $manifest->permissions),
            'routes' => array_map(fn ($r) => $r->toArray(), $manifest->routes),
            'dependencies' => $manifest->dependencies,
            'metadata' => $manifest->metadata,
        ]);
    }
}
