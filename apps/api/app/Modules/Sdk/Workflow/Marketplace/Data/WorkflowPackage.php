<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowPackage implements \JsonSerializable
{
    /**
     * @param  list<string>  $tags
     * @param  array<string, mixed>  $metadata
     * @param  list<WorkflowDependency>  $dependencies
     * @param  list<WorkflowPackageVersion>  $versions
     */
    public function __construct(
        public string $publicId,
        public string $packageKey,
        public string $name,
        public string $status,
        public string $visibility,
        public string $type,
        public ?string $description = null,
        public ?string $author = null,
        public ?string $license = null,
        public ?string $moduleKey = null,
        public array $tags = [],
        public array $metadata = [],
        public array $dependencies = [],
        public array $versions = [],
        public ?WorkflowPackageVersion $latestVersion = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $dependencies = [];
        foreach (is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [] as $dependency) {
            if (is_array($dependency)) {
                $dependencies[] = WorkflowDependency::fromArray($dependency);
            }
        }

        $versions = [];
        foreach (is_array($data['versions'] ?? null) ? $data['versions'] : [] as $version) {
            if (is_array($version)) {
                $versions[] = WorkflowPackageVersion::fromArray($version);
            }
        }

        return new self(
            publicId: (string) ($data['public_id'] ?? ''),
            packageKey: (string) ($data['package_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            status: (string) ($data['status'] ?? 'draft'),
            visibility: (string) ($data['visibility'] ?? 'organization'),
            type: (string) ($data['type'] ?? 'solution'),
            description: isset($data['description']) ? (string) $data['description'] : null,
            author: isset($data['author']) ? (string) $data['author'] : null,
            license: isset($data['license']) ? (string) $data['license'] : null,
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : null,
            tags: is_array($data['tags'] ?? null) ? array_values(array_map('strval', $data['tags'])) : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            dependencies: $dependencies,
            versions: $versions,
            latestVersion: isset($data['latest_version']) && is_array($data['latest_version'])
                ? WorkflowPackageVersion::fromArray($data['latest_version'])
                : null,
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
            'description' => $this->description,
            'author' => $this->author,
            'license' => $this->license,
            'module_key' => $this->moduleKey,
            'tags' => $this->tags,
            'metadata' => $this->metadata,
            'dependencies' => array_map(fn (WorkflowDependency $d) => $d->toArray(), $this->dependencies),
            'versions' => array_map(fn (WorkflowPackageVersion $v) => $v->toArray(), $this->versions),
            'latest_version' => $this->latestVersion?->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
