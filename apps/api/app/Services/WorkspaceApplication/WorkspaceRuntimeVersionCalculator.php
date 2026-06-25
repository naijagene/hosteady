<?php

namespace App\Services\WorkspaceApplication;

class WorkspaceRuntimeVersionCalculator
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function calculate(array $manifest): string
    {
        $normalized = $this->normalizeManifest($manifest);

        return hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function normalizeManifest(array $manifest): array
    {
        $applications = $manifest['applications'] ?? [];

        usort($applications, fn (array $left, array $right) => strcmp($left['key'], $right['key']));

        foreach ($applications as &$application) {
            $settings = $application['settings'] ?? [];
            usort($settings, fn (array $left, array $right) => strcmp($left['setting_key'], $right['setting_key']));
            $application['settings'] = $settings;
        }
        unset($application);

        return [
            'applications' => $applications,
        ];
    }
}
