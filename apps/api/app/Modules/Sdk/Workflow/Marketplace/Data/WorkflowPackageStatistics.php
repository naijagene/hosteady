<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowPackageStatistics implements \JsonSerializable
{
    public function __construct(
        public int $packages,
        public int $versions,
        public int $installs,
        public int $updatesAvailable,
        public int $featured,
        public int $compatible,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            packages: (int) ($data['packages'] ?? 0),
            versions: (int) ($data['versions'] ?? 0),
            installs: (int) ($data['installs'] ?? 0),
            updatesAvailable: (int) ($data['updates_available'] ?? 0),
            featured: (int) ($data['featured'] ?? 0),
            compatible: (int) ($data['compatible'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'packages' => $this->packages,
            'versions' => $this->versions,
            'installs' => $this->installs,
            'updates_available' => $this->updatesAvailable,
            'featured' => $this->featured,
            'compatible' => $this->compatible,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
