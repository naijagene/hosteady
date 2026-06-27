<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowPackageReference implements \JsonSerializable
{
    public function __construct(
        public string $publicId,
        public string $packageKey,
        public string $name,
        public string $status,
        public string $visibility,
        public string $type,
        public ?string $moduleKey = null,
        public ?string $latestVersion = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            packageKey: (string) ($data['package_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            status: (string) ($data['status'] ?? 'draft'),
            visibility: (string) ($data['visibility'] ?? 'organization'),
            type: (string) ($data['type'] ?? 'solution'),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : null,
            latestVersion: isset($data['latest_version']) ? (string) $data['latest_version'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'package_key' => $this->packageKey,
            'name' => $this->name,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'type' => $this->type,
            'module_key' => $this->moduleKey,
            'latest_version' => $this->latestVersion,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
