<?php

namespace App\Services\Enterprise\FileMedia;

use App\Enums\FileCategory;
use App\Models\PlatformFile;
use App\Support\Tenant\TenantContext;

class EnterpriseStorageHealthService
{
    public function __construct(
        private readonly LaravelStorageAdapter $storageAdapter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assess(?TenantContext $context = null): array
    {
        $defaultDisk = (string) config('heos.enterprise.files.default_disk', 'local');
        $publicDisk = (string) config('heos.enterprise.files.public_disk', 'public');
        $configuredDisks = $this->storageAdapter->configuredDisks();
        $warnings = [];

        if (! in_array($defaultDisk, $configuredDisks, true)) {
            $warnings[] = sprintf('Default storage disk [%s] is not configured.', $defaultDisk);
        }

        if (! in_array($publicDisk, $configuredDisks, true)) {
            $warnings[] = sprintf('Public storage disk [%s] is not configured.', $publicDisk);
        }

        $defaultWritable = $this->storageAdapter->isWritable($defaultDisk);
        $publicWritable = $this->storageAdapter->isWritable($publicDisk);

        if (! $defaultWritable) {
            $warnings[] = sprintf('Default storage disk [%s] is not writable.', $defaultDisk);
        }

        if (! $publicWritable) {
            $warnings[] = sprintf('Public storage disk [%s] is not writable.', $publicDisk);
        }

        $quota = (int) config('heos.enterprise.files.quota_bytes', 1073741824);
        $used = $context !== null
            ? (int) PlatformFile::query()
                ->where('organization_id', $context->organization->id)
                ->whereNull('deleted_at')
                ->sum('size_bytes')
            : 0;

        return [
            'enabled' => (bool) config('heos.enterprise.files.enabled', true),
            'default_disk' => $defaultDisk,
            'public_disk' => $publicDisk,
            'configured_disks' => $configuredDisks,
            'default_disk_writable' => $defaultWritable,
            'public_disk_writable' => $publicWritable,
            'runtime_capabilities' => [
                'storage' => (bool) config('heos.enterprise.files.enabled', true),
                'media' => (bool) config('heos.enterprise.files.enabled', true),
            ],
            'quota_bytes' => $quota,
            'used_bytes' => $used,
            'remaining_bytes' => max(0, $quota - $used),
            'visibility_modes' => config('heos.enterprise.files.visibility_modes', []),
            'supported_types' => config('heos.enterprise.files.allowed_mime_types', []),
            'supported_categories' => FileCategory::values(),
            'maximum_upload_size' => (int) config('heos.enterprise.files.max_upload_size', 10485760),
            'warnings' => $warnings,
            'status' => $warnings === [] ? 'healthy' : 'warning',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function runtimeContribution(?TenantContext $context = null): array
    {
        $assessment = $this->assess($context);

        return [
            'enabled' => $assessment['enabled'],
            'quota' => $assessment['quota_bytes'],
            'used' => $assessment['used_bytes'],
            'remaining' => $assessment['remaining_bytes'],
            'visibility_modes' => $assessment['visibility_modes'],
            'supported_types' => $assessment['supported_types'],
            'supported_categories' => $assessment['supported_categories'],
            'maximum_upload_size' => $assessment['maximum_upload_size'],
        ];
    }
}
