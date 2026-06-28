<?php

namespace App\Services\Ui;

use App\Services\Dashboard\DynamicDashboardRegistryService;

class UiDashboardBridge
{
    public function __construct(
        private readonly DynamicDashboardRegistryService $dashboardRegistry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function resolveReferenceBestEffort(?string $moduleKey, ?string $resourceKey, array $config = []): ?array
    {
        try {
            $publicId = (string) ($config['public_id'] ?? '');

            if ($publicId !== '') {
                $definition = $this->dashboardRegistry->findByPublicId($publicId);

                return $definition?->toArray();
            }

            $moduleKey = $moduleKey ?? (string) ($config['module_key'] ?? '');
            $dashboardKey = $resourceKey ?? (string) ($config['dashboard_key'] ?? $config['resource_key'] ?? '');

            if ($moduleKey === '' || $dashboardKey === '') {
                return null;
            }

            $definition = $this->dashboardRegistry->find($moduleKey, $dashboardKey);

            return $definition?->toArray();
        } catch (\Throwable) {
            return null;
        }
    }
}
