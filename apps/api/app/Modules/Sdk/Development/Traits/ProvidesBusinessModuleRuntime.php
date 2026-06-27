<?php

namespace App\Modules\Sdk\Development\Traits;

trait ProvidesBusinessModuleRuntime
{
    /**
     * @return array<string, mixed>
     */
    public function runtimeMetadata(): array
    {
        return [
            'module_key' => $this->moduleKey(),
            'name' => $this->name(),
            'version' => $this->version(),
            'type' => $this->type(),
            'capabilities' => array_map(
                fn ($capability) => $capability->toArray(),
                $this->capabilities(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtime(): array
    {
        return $this->runtimeMetadata();
    }
}
