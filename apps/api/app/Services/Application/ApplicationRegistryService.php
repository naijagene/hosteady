<?php

namespace App\Services\Application;

use App\Enums\ApplicationStatus;
use App\Exceptions\Application\ApplicationNotFoundException;
use App\Models\Application;
use Illuminate\Support\Collection;

class ApplicationRegistryService
{
    /**
     * @return Collection<int, Application>
     */
    public function listPlatformCatalog(): Collection
    {
        return Application::query()
            ->where('status', ApplicationStatus::Active)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Application>
     */
    public function listAvailableForInstall(): Collection
    {
        return Application::query()
            ->where('status', ApplicationStatus::Active)
            ->orderBy('name')
            ->get();
    }

    public function findByPublicId(string $publicId): Application
    {
        $application = Application::query()
            ->where('public_id', $publicId)
            ->first();

        if ($application === null) {
            throw new ApplicationNotFoundException;
        }

        return $application;
    }

    public function findByKey(string $key): Application
    {
        $application = Application::query()
            ->where('key', $key)
            ->first();

        if ($application === null) {
            throw new ApplicationNotFoundException;
        }

        return $application;
    }
}
