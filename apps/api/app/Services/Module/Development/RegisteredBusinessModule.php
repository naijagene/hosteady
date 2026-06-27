<?php

namespace App\Services\Module\Development;

use App\Models\BusinessModule as BusinessModuleModel;
use App\Modules\Sdk\Development\Contracts\BusinessModule;
use App\Modules\Sdk\Development\Data\BusinessModuleCapabilityDefinition;
use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModulePermissionDefinition;
use App\Modules\Sdk\Development\Data\BusinessModuleRouteDefinition;

class RegisteredBusinessModule implements BusinessModule
{
    public function __construct(
        private readonly BusinessModuleModel $model,
    ) {
    }

    public function key(): string
    {
        return $this->model->module_key;
    }

    public function name(): string
    {
        return $this->model->name;
    }

    public function version(): string
    {
        return $this->model->version;
    }

    public function manifest(): BusinessModuleManifest
    {
        return BusinessModuleMapper::toManifest($this->model);
    }

    /**
     * @return list<BusinessModuleCapabilityDefinition>
     */
    public function capabilities(): array
    {
        return $this->manifest()->capabilities;
    }

    /**
     * @return list<BusinessModulePermissionDefinition>
     */
    public function permissions(): array
    {
        return $this->manifest()->permissions;
    }

    /**
     * @return list<BusinessModuleRouteDefinition>
     */
    public function routes(): array
    {
        return $this->manifest()->routes;
    }

    /**
     * @return list<string>
     */
    public function dependencies(): array
    {
        return $this->manifest()->dependencies;
    }

    public function boot(): void
    {
    }
}
