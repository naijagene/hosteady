<?php

namespace App\Services\Ui;

use App\Services\Report\DynamicReportRegistryService;

class UiReportBridge
{
    public function __construct(
        private readonly DynamicReportRegistryService $reportRegistry,
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
                $definition = $this->reportRegistry->findByPublicId($publicId);

                return $definition?->toArray();
            }

            $moduleKey = $moduleKey ?? (string) ($config['module_key'] ?? '');
            $reportKey = $resourceKey ?? (string) ($config['report_key'] ?? $config['resource_key'] ?? '');

            if ($moduleKey === '' || $reportKey === '') {
                return null;
            }

            $definition = $this->reportRegistry->find($moduleKey, $reportKey);

            return $definition?->toArray();
        } catch (\Throwable) {
            return null;
        }
    }
}
