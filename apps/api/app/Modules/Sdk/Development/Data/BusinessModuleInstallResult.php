<?php

namespace App\Modules\Sdk\Development\Data;

use App\Modules\Sdk\Development\Enums\BusinessModuleInstallStatus;

readonly class BusinessModuleInstallResult implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $settings
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $installationPublicId,
        public string $modulePublicId,
        public string $moduleKey,
        public string $installedVersion,
        public string $status,
        public array $settings = [],
        public array $warnings = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            installationPublicId: (string) ($data['installation_public_id'] ?? ''),
            modulePublicId: (string) ($data['module_public_id'] ?? ''),
            moduleKey: (string) ($data['module_key'] ?? ''),
            installedVersion: (string) ($data['installed_version'] ?? ''),
            status: (string) ($data['status'] ?? BusinessModuleInstallStatus::Installed->value),
            settings: is_array($data['settings'] ?? null) ? $data['settings'] : [],
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'installation_public_id' => $this->installationPublicId,
            'module_public_id' => $this->modulePublicId,
            'module_key' => $this->moduleKey,
            'installed_version' => $this->installedVersion,
            'status' => $this->status,
            'settings' => $this->settings,
            'warnings' => $this->warnings,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
