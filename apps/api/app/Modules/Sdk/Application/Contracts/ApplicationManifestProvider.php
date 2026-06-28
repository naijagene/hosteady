<?php

namespace App\Modules\Sdk\Application\Contracts;

interface ApplicationManifestProvider
{
    public function manifest(string $applicationKey): \App\Modules\Sdk\Application\Data\ApplicationManifest;
}
