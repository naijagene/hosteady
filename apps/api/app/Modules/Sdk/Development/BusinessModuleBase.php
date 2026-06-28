<?php

namespace App\Modules\Sdk\Development;

use App\Modules\Sdk\Development\Contracts\BusinessModule;
use App\Modules\Sdk\Development\Contracts\BusinessModuleHealthProvider;
use App\Modules\Sdk\Development\Contracts\BusinessModuleMigrationProvider;
use App\Modules\Sdk\Development\Contracts\BusinessModulePermissionProvider;
use App\Modules\Sdk\Development\Contracts\BusinessModuleRouteProvider;
use App\Modules\Sdk\Development\Contracts\BusinessModuleRuntimeProvider;
use App\Modules\Sdk\Development\Contracts\BusinessModuleSeeder;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleValidationReport;
use App\Modules\Sdk\Development\Support\BusinessModuleConventionResolver;
use App\Modules\Sdk\Development\Traits\LoadsBusinessModuleManifest;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModuleAssets;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModuleCapabilities;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModuleHealth;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModuleLifecycle;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModulePermissions;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModuleRoutes;
use App\Modules\Sdk\Development\Traits\ProvidesBusinessModuleRuntime;
use App\Services\Module\Development\BusinessModuleValidatorService;

/**
 * Convention-based base class for HEOS business modules.
 *
 * Extend with a module key override:
 *
 * class BarSoftModule extends BusinessModuleBase
 * {
 *     protected string $moduleKey = 'barsoft';
 * }
 *
 * BusinessModuleProvider is intentionally not implemented here; use
 * BusinessModuleRegistryService for catalog registration operations.
 */
abstract class BusinessModuleBase implements
    BusinessModule,
    BusinessModulePermissionProvider,
    BusinessModuleRouteProvider,
    BusinessModuleRuntimeProvider,
    BusinessModuleHealthProvider,
    BusinessModuleMigrationProvider,
    BusinessModuleSeeder
{
    use LoadsBusinessModuleManifest;
    use ProvidesBusinessModuleAssets;
    use ProvidesBusinessModuleCapabilities;
    use ProvidesBusinessModuleHealth;
    use ProvidesBusinessModuleLifecycle;
    use ProvidesBusinessModulePermissions;
    use ProvidesBusinessModuleRoutes;
    use ProvidesBusinessModuleRuntime;

    protected string $moduleKey = '';

    protected ?string $name = null;

    protected ?string $description = null;

    protected ?string $version = null;

    protected ?string $type = null;

    public function key(): string
    {
        return $this->moduleKey();
    }

    public function moduleKey(): string
    {
        if ($this->moduleKey !== '') {
            return $this->moduleKey;
        }

        return app(BusinessModuleConventionResolver::class)
            ->resolveFromClass(static::class)['module_key'];
    }

    public function name(): string
    {
        if ($this->name !== null && $this->name !== '') {
            return $this->name;
        }

        $manifestName = $this->loadManifestData()['name'] ?? null;

        if (is_string($manifestName) && $manifestName !== '') {
            return $manifestName;
        }

        return app(BusinessModuleConventionResolver::class)
            ->resolveFromClass(static::class)['studly_name'];
    }

    public function description(): ?string
    {
        if ($this->description !== null && $this->description !== '') {
            return $this->description;
        }

        $manifestDescription = $this->loadManifestData()['description'] ?? null;

        return is_string($manifestDescription) && $manifestDescription !== ''
            ? $manifestDescription
            : sprintf('%s business module for HEOS.', $this->name());
    }

    public function version(): string
    {
        if ($this->version !== null && $this->version !== '') {
            return $this->version;
        }

        $manifestVersion = $this->loadManifestData()['version'] ?? null;

        return is_string($manifestVersion) && $manifestVersion !== ''
            ? $manifestVersion
            : '0.1.0';
    }

    public function type(): string
    {
        if ($this->type !== null && $this->type !== '') {
            return $this->type;
        }

        $manifestType = $this->loadManifestData()['type'] ?? null;

        return is_string($manifestType) && $manifestType !== ''
            ? $manifestType
            : 'business';
    }

    public function manifest(): BusinessModuleManifest
    {
        return $this->buildManifest();
    }

    /**
     * @return list<string>
     */
    public function dependencies(): array
    {
        return $this->manifest()->dependencies;
    }

    public function validate(): BusinessModuleValidationReport
    {
        return app(BusinessModuleValidatorService::class)->validateModule($this);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function navigation(): array
    {
        return [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function menus(): array
    {
        return $this->navigation();
    }

    /**
     * @return array<string, mixed>
     */
    public function workspace(): array
    {
        return [
            'module_key' => $this->moduleKey(),
            'name' => $this->name(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function application(): array
    {
        return [
            'application_key' => $this->moduleKey(),
            'name' => $this->name(),
            'type' => $this->type(),
            'version' => $this->version(),
        ];
    }
}
