<?php

namespace App\Modules\Sdk\Development\Contracts;

use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Data\BusinessModuleReference;

interface BusinessModuleProvider
{
    /**
     * @return list<BusinessModuleReference>
     */
    public function all(): array;

    public function findByKey(string $key): ?BusinessModule;

    public function findByPublicId(string $publicId): ?BusinessModule;

    public function normalizeManifest(BusinessModuleManifest $manifest): BusinessModuleManifest;

    public function register(
        BusinessModuleManifest $manifest,
        ?string $userId = null,
        ?string $membershipId = null,
    ): BusinessModuleReference;
}
