<?php

namespace App\Modules\Sdk\Development\Traits;

use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Support\BusinessModuleConventionResolver;

trait LoadsBusinessModuleManifest
{
    /** @var array<string, mixed>|null */
    private ?array $loadedManifestData = null;

    private bool $manifestLoaded = false;

    /**
     * @return array<string, mixed>
     */
    protected function loadManifestData(): array
    {
        if ($this->manifestLoaded) {
            return $this->loadedManifestData ?? [];
        }

        $this->manifestLoaded = true;
        $this->loadedManifestData = [];

        $conventions = app(BusinessModuleConventionResolver::class)->resolveFromClass(static::class);
        $manifestPath = $conventions['manifest_path'];

        if (! is_file($manifestPath)) {
            return $this->loadedManifestData;
        }

        try {
            /** @var mixed $data */
            $data = require $manifestPath;
            $this->loadedManifestData = is_array($data) ? $data : [];
        } catch (\Throwable) {
            $this->loadedManifestData = [];
        }

        return $this->loadedManifestData;
    }

    protected function buildManifest(): BusinessModuleManifest
    {
        $manifestData = $this->loadManifestData();

        $merged = array_merge($manifestData, array_filter([
            'module_key' => $this->resolvedModuleKey(),
            'name' => $this->resolvedName(),
            'description' => $this->resolvedDescription(),
            'type' => $this->resolvedType(),
            'version' => $this->resolvedVersion(),
        ], fn (mixed $value) => $value !== null && $value !== ''));

        if (($merged['module_key'] ?? '') === '') {
            $merged['module_key'] = app(BusinessModuleConventionResolver::class)
                ->resolveFromClass(static::class)['module_key'];
        }

        if (($merged['name'] ?? '') === '') {
            $merged['name'] = app(BusinessModuleConventionResolver::class)
                ->resolveFromClass(static::class)['studly_name'];
        }

        return BusinessModuleManifest::fromArray($merged);
    }

    protected function resolvedModuleKey(): ?string
    {
        return property_exists($this, 'moduleKey') && $this->moduleKey !== ''
            ? $this->moduleKey
            : null;
    }

    protected function resolvedName(): ?string
    {
        return property_exists($this, 'name') && $this->name !== null && $this->name !== ''
            ? $this->name
            : null;
    }

    protected function resolvedDescription(): ?string
    {
        return property_exists($this, 'description') && $this->description !== null && $this->description !== ''
            ? $this->description
            : null;
    }

    protected function resolvedVersion(): ?string
    {
        return property_exists($this, 'version') && $this->version !== null && $this->version !== ''
            ? $this->version
            : null;
    }

    protected function resolvedType(): ?string
    {
        return property_exists($this, 'type') && $this->type !== null && $this->type !== ''
            ? $this->type
            : null;
    }
}
