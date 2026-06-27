<?php

namespace App\Services\Module\Development;

use App\Modules\Sdk\Development\Data\BusinessModuleManifest;
use App\Modules\Sdk\Development\Exceptions\BusinessModuleValidationException;

class BusinessModuleManifestLoader
{
    public function __construct(
        private readonly BusinessModuleFilesystemService $filesystem,
    ) {
    }

    public function loadFromModulePath(string $modulePath): BusinessModuleManifest
    {
        $manifestPath = $modulePath.'/Config/manifest.php';

        if (! is_file($manifestPath)) {
            throw new BusinessModuleValidationException(sprintf('Manifest file not found at [%s].', $manifestPath));
        }

        /** @var array<string, mixed> $data */
        $data = require $manifestPath;

        return BusinessModuleManifest::fromArray($data);
    }

    public function loadByKey(string $moduleKey): BusinessModuleManifest
    {
        return $this->loadFromModulePath($this->filesystem->modulePathForKey($moduleKey));
    }
}
