<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowPackageVersion implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(
        public string $publicId,
        public string $packagePublicId,
        public string $version,
        public string $status,
        public array $manifest,
        public ?string $checksum = null,
        public ?string $publishedAt = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            packagePublicId: (string) ($data['package_public_id'] ?? ''),
            version: (string) ($data['version'] ?? ''),
            status: (string) ($data['status'] ?? 'draft'),
            manifest: is_array($data['manifest'] ?? null) ? $data['manifest'] : [],
            checksum: isset($data['checksum']) ? (string) $data['checksum'] : null,
            publishedAt: isset($data['published_at']) ? (string) $data['published_at'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'package_public_id' => $this->packagePublicId,
            'version' => $this->version,
            'status' => $this->status,
            'manifest' => $this->manifest,
            'checksum' => $this->checksum,
            'published_at' => $this->publishedAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
