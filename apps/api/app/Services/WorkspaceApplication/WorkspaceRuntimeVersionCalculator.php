<?php

namespace App\Services\WorkspaceApplication;

use App\Services\Runtime\Data\RuntimeManifest;

class WorkspaceRuntimeVersionCalculator
{
    public function calculate(RuntimeManifest $manifest): string
    {
        $normalized = $this->normalizeFingerprint($manifest->fingerprint());

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $fingerprint
     * @return array<string, mixed>
     */
    private function normalizeFingerprint(array $fingerprint): array
    {
        $applications = $fingerprint['applications'] ?? [];

        usort($applications, fn (array $left, array $right) => strcmp($left['key'], $right['key']));

        foreach ($applications as &$application) {
            foreach (['settings', 'definitions', 'capabilities', 'dependencies'] as $field) {
                $items = $application[$field] ?? [];
                if ($field === 'capabilities' || $field === 'dependencies') {
                    sort($items);
                } else {
                    usort($items, fn (array $left, array $right) => strcmp($left['setting_key'] ?? '', $right['setting_key'] ?? ''));
                }
                $application[$field] = $items;
            }
        }
        unset($application);

        return [
            'applications' => $applications,
        ];
    }
}
