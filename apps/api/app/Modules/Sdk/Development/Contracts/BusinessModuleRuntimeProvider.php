<?php

namespace App\Modules\Sdk\Development\Contracts;

interface BusinessModuleRuntimeProvider
{
    public function moduleKey(): string;

    /**
     * @return array<string, mixed>
     */
    public function runtimeMetadata(): array;
}
